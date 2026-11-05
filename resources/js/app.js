import Alpine from 'alpinejs';
import {
    Chart, LineController, BarController, ScatterController, LineElement, BarElement, PointElement,
    CategoryScale, LinearScale, Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(LineController, BarController, ScatterController, LineElement, BarElement, PointElement,
    CategoryScale, LinearScale, Tooltip, Legend, Filler);
Chart.defaults.color = '#8b93a3';
Chart.defaults.borderColor = '#262a33';
Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';

const COLORS = { kcal: '#60a5fa', accent: '#4ade80', warn: '#f59e0b', muted: '#8b93a3', bad: '#f87171' };

const METRIC_LABELS = {
    steps: 'Шаги', sleep_h: 'Сон, ч', bp_sys: 'Давление верхнее', bp_dia: 'Давление нижнее',
    pulse: 'Пульс', waist_cm: 'Талия, см',
};
const METRIC_HINTS = {
    steps: 'шагов за день', sleep_h: 'часов, например 7.5', bp_sys: 'мм рт. ст., например 125', bp_dia: 'мм рт. ст., например 80',
    pulse: 'ударов в минуту', waist_cm: 'см',
};

const FLAG_TEXT = {
    too_fast: 'Вес падает быстрее 1 кг в неделю две недели подряд. Стоит добавить еды.',
    stalled: 'Тренд стоит 3 недели при дефиците. Проверь точность записей.',
    low_logging: 'Меньше 2 приёмов пищи в день за неделю: данным пока нельзя доверять.',
    below_min: 'Среднее потребление ниже минимальной нормы.',
};

const SETTING_GROUPS = [
    {
        title: 'О себе',
        hint: 'Нужно для стартовой оценки расхода в первые 14 дней.',
        fields: [
            { key: 'height_cm', label: 'Рост, см', step: '1' },
            { key: 'age', label: 'Возраст, лет', step: '1' },
            { key: 'activity_factor', label: 'Активность', options: [[1.2, 'Сидячая работа'], [1.375, 'Лёгкая: прогулки'], [1.55, 'Средняя: тренировки 3–5 раз']] },
        ],
    },
    {
        title: 'Цель',
        hint: 'Норма = расход минус дефицит, но не ниже минимума.',
        fields: [
            { key: 'target_weight_kg', label: 'Целевой вес, кг', step: '0.1', optional: true },
            { key: 'deficit_pct', label: 'Дефицит', options: [[10, '10% мягко'], [15, '15% обычно'], [20, '20% быстрее']] },
            { key: 'min_kcal', label: 'Минимальная норма, ккал', step: '10' },
        ],
    },
    {
        title: 'Дополнительно',
        hint: '',
        fields: [
            { key: 'start_tdee', label: 'Свой стартовый расход, ккал (пусто = по формуле)', step: '10', optional: true },
            { key: 'timezone', label: 'Часовой пояс', type: 'text' },
        ],
    },
];
const SETTING_FIELDS = SETTING_GROUPS.flatMap((g) => g.fields);

class Unauthorized extends Error {}

async function api(path, { method = 'GET', body } = {}) {
    const res = await fetch('/api/' + path, {
        method,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', ...(body ? { 'Content-Type': 'application/json' } : {}) },
        body: body ? JSON.stringify(body) : undefined,
    });
    if (res.status === 401) throw new Unauthorized();
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || data.error || `Ошибка ${res.status}`);
    return data;
}

const pad = (n) => String(n).padStart(2, '0');
const isoDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const addDays = (date, n) => {
    const d = new Date(date + 'T12:00:00');
    d.setDate(d.getDate() + n);
    return isoDate(d);
};
const round = (v, digits = 0) => (v === null || v === undefined ? null : Number(Number(v).toFixed(digits)));

const charts = {};
function draw(id, config) {
    charts[id]?.destroy();
    const el = document.getElementById(id);
    if (el) charts[id] = new Chart(el, config);
}

Alpine.data('kkal', () => ({
    authed: null,
    token: '',
    tab: 'today',
    error: '',
    busy: false,
    toast: '',
    toastAction: null,

    date: isoDate(new Date()),
    day: null,
    openMeal: null,
    editing: null,
    mealForm: null,
    weightInput: '',
    products: [],
    productPick: { id: '', grams: '' },
    productForm: null,

    weightPeriod: 28,
    weights: { points: [], trend: [] },
    weightBrief: null,

    calPeriod: 28,
    calories: [],
    progress: null,

    metricTypes: [],
    metricType: 'steps',
    metricItems: [],
    metricForm: { type: 'steps', value: '', date: isoDate(new Date()) },

    notes: [],
    noteKind: '',
    noteText: '',

    settings: {},
    settingGroups: SETTING_GROUPS,
    flagText: FLAG_TEXT,
    metricLabels: METRIC_LABELS,
    metricHints: METRIC_HINTS,
    origin: location.origin,

    async init() {
        try {
            await this.loadToday();
            this.authed = true;
        } catch (e) {
            this.authed = e instanceof Unauthorized ? false : true;
            if (!(e instanceof Unauthorized)) this.error = e.message;
        }
        this.$watch('tab', (t) => this.openTab(t));
    },

    async run(fn) {
        this.error = '';
        this.busy = true;
        try {
            await fn();
        } catch (e) {
            if (e instanceof Unauthorized) this.authed = false;
            else this.error = e.message;
        } finally {
            this.busy = false;
        }
    },

    notify(text, action = null) {
        this.toast = text;
        this.toastAction = action;
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => { this.toast = ''; this.toastAction = null; }, 6000);
    },

    async login() {
        await this.run(async () => {
            await api('login', { method: 'POST', body: { token: this.token.trim() } }).catch((e) => {
                throw e instanceof Unauthorized ? new Error('Неверный токен') : e;
            });
            this.token = '';
            this.authed = true;
            await this.loadToday();
        });
    },

    async logout() {
        await api('logout', { method: 'POST' });
        this.authed = false;
    },

    openTab(t) {
        const loaders = {
            today: () => this.loadToday(), weight: () => this.loadWeights(), calories: () => this.loadCalories(),
            metrics: () => this.loadMetrics(), notes: () => this.loadNotes(), settings: () => this.loadSettings(),
        };
        this.run(loaders[t]);
    },

    // ---------- Today ----------
    async loadToday() {
        this.day = await api('day?date=' + this.date);
        if (!this.products.length) this.products = (await api('products')).products;
    },
    shiftDay(n) {
        this.date = addDays(this.date, n);
        this.run(() => this.loadToday());
    },
    goToDate(d) {
        if (!d) return;
        this.date = d > isoDate(new Date()) ? isoDate(new Date()) : d;
        if (this.tab === 'today') this.run(() => this.loadToday());
        else this.tab = 'today';
    },
    get todayIso() { return isoDate(new Date()); },
    get remainingText() {
        const r = this.day?.remaining_kcal;
        if (r === null || r === undefined) return '';
        return r >= 0 ? `Осталось ${Math.round(r)}` : `Сверх нормы ${Math.round(-r)}`;
    },
    get weighedToday() { return this.day?.weight?.measured_on === isoDate(new Date()); },
    signed(v, unit = '') {
        if (v === null || v === undefined) return '—';
        return (v > 0 ? '+' : '') + v + unit;
    },
    toTarget(brief) {
        if (!brief?.target_weight_kg || brief.trend_kg === null) return null;
        return round(brief.trend_kg - brief.target_weight_kg, 1);
    },
    confidenceLabel: (c) => ({ low: 'примерно', medium: 'средняя точность', high: 'точно' }[c] ?? c),
    sourceLabel: (s) => ({ photo: '📷 по фото', text: '✍️ по описанию', label: '🏷 по этикетке' }[s] ?? s),
    toggleMeal(m) {
        this.editing = null;
        this.openMeal = this.openMeal === m.id ? null : m.id;
    },
    get isToday() { return this.date === isoDate(new Date()); },
    get dayTitle() {
        if (this.isToday) return 'Сегодня';
        if (this.date === addDays(isoDate(new Date()), -1)) return 'Вчера';
        return new Date(this.date + 'T12:00:00').toLocaleDateString('ru-RU', { weekday: 'short', day: 'numeric', month: 'long' });
    },
    get eatenPct() {
        if (!this.day?.norm_kcal) return 0;
        return Math.min(100, Math.round((this.day.totals.kcal / this.day.norm_kcal) * 100));
    },
    time(iso) {
        return iso ? iso.slice(11, 16) : '';
    },
    round,

    newMeal() {
        const now = new Date();
        this.productPick = { id: '', grams: '' };
        this.mealForm = {
            title: '', kcal: '', protein_g: '', fat_g: '', carbs_g: '',
            time: `${pad(now.getHours())}:${pad(now.getMinutes())}`,
        };
    },
    applyProduct() {
        const p = this.products.find((x) => String(x.id) === String(this.productPick.id));
        if (!p) return;
        const g = Number(this.productPick.grams || p.default_portion_g || 100);
        this.productPick.grams = g;
        const k = g / 100;
        Object.assign(this.mealForm, {
            title: `${p.name}, ${g} г`, kcal: round(p.kcal_100g * k), protein_g: round(p.protein_100g * k, 1),
            fat_g: round(p.fat_100g * k, 1), carbs_g: round(p.carbs_100g * k, 1), fromLabel: true,
        });
    },
    async saveNewMeal() {
        const f = this.mealForm;
        await this.run(async () => {
            const kcal = Number(f.kcal);
            await api('meals', {
                method: 'POST',
                body: {
                    title: f.title || 'Без названия',
                    items: [{ name: f.title || 'Без названия', grams: this.productPick.grams ? Number(this.productPick.grams) : null, kcal }],
                    kcal_est: kcal, kcal_max: kcal,
                    protein_g: Number(f.protein_g || 0), fat_g: Number(f.fat_g || 0), carbs_g: Number(f.carbs_g || 0),
                    confidence: f.fromLabel ? 'high' : 'medium', source: f.fromLabel ? 'label' : 'text',
                    eaten_at: `${this.date}T${f.time || '12:00'}:00`,
                    notes: 'Введено вручную на сайте',
                },
            });
            this.mealForm = null;
            await this.loadToday();
            this.notify(`Записано: ${Math.round(kcal)} ккал`);
        });
    },
    editMeal(m) {
        this.editing = {
            id: m.id, title: m.title, kcal_est: m.kcal_est, kcal_max: m.kcal_max,
            protein_g: m.protein_g, fat_g: m.fat_g, carbs_g: m.carbs_g, time: this.time(m.eaten_at), notes: m.notes ?? '',
        };
    },
    async saveEdit() {
        const e = this.editing;
        await this.run(async () => {
            await api('meals/' + e.id, {
                method: 'PATCH',
                body: {
                    title: e.title, kcal_est: Number(e.kcal_est), kcal_max: Math.max(Number(e.kcal_max), Number(e.kcal_est)),
                    protein_g: Number(e.protein_g), fat_g: Number(e.fat_g), carbs_g: Number(e.carbs_g),
                    eaten_at: `${this.date}T${e.time}:00`, notes: e.notes || null,
                },
            });
            this.editing = null;
            await this.loadToday();
        });
    },
    async deleteMeal(m) {
        await this.run(async () => {
            await api('meals/' + m.id, { method: 'DELETE' });
            this.editing = null;
            await this.loadToday();
            this.notify(`Удалено: ${m.title}`, async () => {
                await api(`meals/${m.id}/restore`, { method: 'POST' });
                await this.loadToday();
            });
        });
    },
    async undo() {
        const action = this.toastAction;
        this.toast = '';
        this.toastAction = null;
        if (action) await this.run(action);
    },
    async quickWeight() {
        if (!this.weightInput) return;
        await this.run(async () => {
            const r = await api('weights', { method: 'POST', body: { weight_kg: Number(String(this.weightInput).replace(',', '.')) } });
            this.weightInput = '';
            const change = r.trend_change_week_kg;
            this.notify(`Вес ${r.weight.weight_kg} кг · тренд ${r.trend_kg}` + (change !== null ? ` (${change > 0 ? '+' : ''}${change} за неделю)` : ''));
            if (this.tab === 'today') await this.loadToday();
            if (this.tab === 'weight') await this.loadWeights();
        });
    },

    // ---------- Weight ----------
    async loadWeights() {
        const from = this.weightPeriod ? addDays(isoDate(new Date()), -(this.weightPeriod - 1)) : '2000-01-01';
        this.weights = await api(`weights?from=${from}`);
        this.weightBrief = this.weights.brief;
        const trend = this.weights.trend;
        const start = this.weightPeriod ? from : (trend[0]?.date ?? this.weights.points[0]?.measured_at.slice(0, 10) ?? isoDate(new Date()));
        const labels = [];
        for (let d = start; d <= this.weights.to; d = addDays(d, 1)) labels.push(d);
        const byDate = Object.fromEntries(trend.map((t) => [t.date, t.trend_kg]));
        const raw = {};
        this.weights.points.forEach((p) => { raw[p.measured_at.slice(0, 10)] = p.weight_kg; });
        this.$nextTick(() => draw('weightChart', {
            type: 'line',
            data: {
                labels: labels.map((d) => d.slice(5)),
                datasets: [
                    { label: 'Тренд', data: labels.map((d) => byDate[d] ?? null), borderColor: COLORS.accent, pointRadius: 0, tension: 0.3, spanGaps: true },
                    { label: 'Взвешивания', data: labels.map((d) => raw[d] ?? null), showLine: false, pointRadius: 3, pointBackgroundColor: COLORS.muted, borderColor: COLORS.muted },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { x: { ticks: { maxTicksLimit: 7 } } } },
        }));
    },
    get weightList() {
        return [...this.weights.points].reverse().slice(0, 30);
    },
    async deleteWeight(w) {
        await this.run(async () => {
            await api('weights/' + w.id, { method: 'DELETE' });
            await this.loadWeights();
        });
    },

    // ---------- Calories ----------
    async loadCalories() {
        const from = addDays(isoDate(new Date()), -(this.calPeriod - 1));
        const [cal, progress] = await Promise.all([api(`calories?from=${from}`), api('progress?days=' + Math.max(7, Math.min(this.calPeriod, 365)))]);
        this.calories = cal.days;
        this.progress = progress;
        this.$nextTick(() => draw('calChart', {
            type: 'bar',
            data: {
                labels: this.calories.map((d) => d.date.slice(5)),
                datasets: [
                    { type: 'bar', label: 'Съедено', data: this.calories.map((d) => d.kcal), backgroundColor: this.calories.map((d) => (d.norm_kcal && d.kcal > d.norm_kcal ? COLORS.warn : COLORS.kcal)), borderRadius: 4, order: 3 },
                    { type: 'line', label: 'Норма', data: this.calories.map((d) => d.norm_kcal), borderColor: COLORS.accent, pointRadius: 0, stepped: true, order: 1 },
                    { type: 'line', label: 'Расход', data: this.calories.map((d) => d.tdee_kcal), borderColor: COLORS.muted, borderDash: [4, 4], pointRadius: 0, stepped: true, order: 2 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { x: { ticks: { maxTicksLimit: 8 } } } },
        }));
    },
    get activeFlags() {
        if (!this.progress) return [];
        return Object.entries(this.progress.flags).filter(([, v]) => v).map(([k]) => k);
    },

    // ---------- Metrics ----------
    async loadMetrics() {
        const r = await api('metrics?from=' + addDays(isoDate(new Date()), -364));
        this.metricTypes = [...new Set([...Object.keys(METRIC_LABELS), ...r.types])];
        this.metricItems = r.items;
        this.drawMetric();
    },
    selectMetric(t) {
        this.metricType = t;
        this.metricForm.type = t === '__new' ? '' : t;
        if (t !== '__new') this.drawMetric();
    },
    get metricRows() {
        return this.metricItems.filter((m) => m.type === this.metricType).slice().reverse();
    },
    drawMetric() {
        const isBp = this.metricType === 'bp_sys' || this.metricType === 'bp_dia';
        const types = isBp ? ['bp_sys', 'bp_dia'] : [this.metricType];
        const dates = [...new Set(this.metricItems.filter((m) => types.includes(m.type)).map((m) => m.date))].sort();
        const series = (t) => dates.map((d) => {
            const v = this.metricItems.filter((m) => m.type === t && m.date === d);
            return v.length ? v[v.length - 1].value : null;
        });
        this.$nextTick(() => draw('metricChart', {
            type: 'line',
            data: {
                labels: dates.map((d) => d.slice(5)),
                datasets: types.map((t, i) => ({ label: METRIC_LABELS[t] ?? t, data: series(t), borderColor: [COLORS.kcal, COLORS.warn][i], pointRadius: 3, spanGaps: true, tension: 0.2 })),
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { ticks: { maxTicksLimit: 8 } } } },
        }));
    },
    async saveMetric() {
        const f = this.metricForm;
        await this.run(async () => {
            await api('metrics', { method: 'POST', body: { type: f.type.trim(), value: Number(String(f.value).replace(',', '.')), date: f.date } });
            this.metricForm.value = '';
            this.metricType = f.type.trim();
            await this.loadMetrics();
        });
    },
    async deleteMetric(m) {
        await this.run(async () => {
            await api('metrics/' + m.id, { method: 'DELETE' });
            await this.loadMetrics();
        });
    },

    // ---------- Notes ----------
    async loadNotes() {
        this.notes = (await api('notes' + (this.noteKind ? '?kind=' + this.noteKind : ''))).notes;
    },
    filterNotes(k) {
        this.noteKind = k;
        this.run(() => this.loadNotes());
    },
    async saveNote() {
        if (!this.noteText.trim()) return;
        await this.run(async () => {
            await api('notes', { method: 'POST', body: { kind: 'observation', text: this.noteText.trim() } });
            this.noteText = '';
            await this.loadNotes();
        });
    },
    async deleteNote(n) {
        await this.run(async () => {
            await api('notes/' + n.id, { method: 'DELETE' });
            await this.loadNotes();
        });
    },
    isDate: (s) => /^\d{4}-\d{2}-\d{2}$/.test(s),
    kindLabel: (k) => ({ observation: 'Наблюдение', suggestion: 'Совет', question: 'Вопрос' }[k] ?? k),

    // ---------- Settings ----------
    async loadSettings() {
        const r = await api('settings');
        this.settings = { ...r.settings };
        this.products = (await api('products')).products;
    },
    async saveSettings() {
        await this.run(async () => {
            const body = {};
            for (const f of SETTING_FIELDS) {
                const v = this.settings[f.key];
                body[f.key] = f.type === 'text' ? v : (v === '' || v === null || v === undefined ? null : Number(v));
            }
            this.settings = (await api('settings', { method: 'PUT', body })).settings;
            this.notify('Настройки сохранены');
        });
    },
    newProduct() {
        this.productForm = { id: null, name: '', kcal_100g: '', protein_100g: '', fat_100g: '', carbs_100g: '', default_portion_g: '' };
    },
    editProduct(p) {
        this.productForm = { ...p, originalName: p.name, default_portion_g: p.default_portion_g ?? '' };
    },
    async saveProduct() {
        const f = this.productForm;
        const num = (v) => Number(String(v || 0).replace(',', '.'));
        await this.run(async () => {
            await api('products', {
                method: 'POST',
                body: {
                    name: f.name.trim(), kcal_100g: num(f.kcal_100g), protein_100g: num(f.protein_100g),
                    fat_100g: num(f.fat_100g), carbs_100g: num(f.carbs_100g),
                    default_portion_g: f.default_portion_g === '' ? null : num(f.default_portion_g),
                },
            });
            // Products are keyed by name: a rename saves a new one, so drop the old record.
            if (f.id && f.originalName && f.originalName !== f.name.trim()) {
                await api('products/' + f.id, { method: 'DELETE' });
            }
            this.productForm = null;
            this.products = (await api('products')).products;
            this.notify('Продукт сохранён');
        });
    },
    async deleteProduct(p) {
        await this.run(async () => {
            await api('products/' + p.id, { method: 'DELETE' });
            this.products = (await api('products')).products;
        });
    },
}));

window.Alpine = Alpine;
Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
