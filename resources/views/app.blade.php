<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f1115">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Kkal">
    <title>Kkal</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
<div x-data="kkal" x-cloak class="mx-auto max-w-xl px-4 pb-28" style="padding-top: max(1rem, env(safe-area-inset-top))">

    {{-- Login --}}
    <template x-if="authed === false">
        <form class="mt-24 space-y-4" @submit.prevent="login">
            <h1 class="text-3xl font-bold">Kkal</h1>
            <p class="text-muted">Введи токен доступа.</p>
            <input class="input" type="password" x-model="token" autocomplete="current-password" placeholder="Токен" autofocus>
            <button class="btn w-full" :disabled="busy || !token">Войти</button>
            <p class="text-bad text-sm" x-show="error" x-text="error"></p>
        </form>
    </template>

    <template x-if="authed">
        <div>
            <p class="mb-3 rounded-xl bg-bad/15 px-3 py-2 text-sm text-bad" x-show="error" x-text="error"></p>

            {{-- Today --}}
            <section x-show="tab === 'today'" class="space-y-4">
                <header class="flex items-center gap-2">
                    <button class="btn-ghost px-4" @click="shiftDay(-1)" aria-label="Предыдущий день">‹</button>
                    <label class="relative flex-1 text-center">
                        <span class="text-xl font-semibold" x-text="dayTitle"></span>
                        <span class="block text-xs text-muted">нажми, чтобы выбрать дату</span>
                        <input type="date" class="absolute inset-0 opacity-0" :max="todayIso" :value="date" @change="goToDate($event.target.value)">
                    </label>
                    <button class="btn-ghost px-4" @click="shiftDay(1)" :disabled="isToday" :class="isToday && 'opacity-30'" aria-label="Следующий день">›</button>
                </header>
                <button class="btn-ghost w-full" x-show="!isToday" @click="goToDate(todayIso)">Вернуться к сегодня</button>

                {{-- Day summary --}}
                <div class="card" x-show="day">
                    <template x-if="day?.norm_kcal">
                        <div>
                            <div class="flex items-baseline justify-between">
                                <div><span class="text-4xl font-bold" x-text="Math.round(day.totals.kcal)"></span>
                                    <span class="text-muted">из <span x-text="day.norm_kcal"></span> ккал</span></div>
                            </div>
                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-line">
                                <div class="h-full rounded-full transition-all" :class="day.remaining_kcal < 0 ? 'bg-bad' : 'bg-accent'" :style="`width: ${eatenPct}%`"></div>
                            </div>
                            <div class="mt-2 text-lg font-semibold" :class="day.remaining_kcal < 0 ? 'text-bad' : 'text-accent'" x-text="remainingText"></div>
                        </div>
                    </template>
                    <template x-if="day && !day.norm_kcal">
                        <div>
                            <div><span class="text-4xl font-bold" x-text="Math.round(day.totals.kcal)"></span> <span class="text-muted">ккал съедено</span></div>
                            <p class="mt-2 rounded-xl bg-warn/15 px-3 py-2 text-sm text-warn">Норма появится, когда будут вес (запиши ниже), рост и возраст (Настройки).</p>
                        </div>
                    </template>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-bg py-2"><div class="text-xs text-muted">Белки</div><div class="font-semibold"><span x-text="Math.round(day?.totals.protein_g ?? 0)"></span> г</div></div>
                        <div class="rounded-xl bg-bg py-2"><div class="text-xs text-muted">Жиры</div><div class="font-semibold"><span x-text="Math.round(day?.totals.fat_g ?? 0)"></span> г</div></div>
                        <div class="rounded-xl bg-bg py-2"><div class="text-xs text-muted">Углеводы</div><div class="font-semibold"><span x-text="Math.round(day?.totals.carbs_g ?? 0)"></span> г</div></div>
                    </div>
                    <div class="mt-2 flex justify-center gap-4 text-xs text-muted" x-show="day?.totals.sugar_g !== null || day?.totals.fiber_g !== null">
                        <span x-show="day?.totals.sugar_g !== null">сахар <span x-text="day?.totals.sugar_g"></span> г</span>
                        <span x-show="day?.totals.fiber_g !== null">клетчатка <span x-text="day?.totals.fiber_g"></span> г</span>
                    </div>
                </div>

                {{-- Weight today --}}
                <div class="card flex items-center gap-3" x-show="isToday">
                    <div class="flex-1">
                        <div class="text-xs text-muted" x-text="weighedToday ? 'Вес сегодня' : 'Взвешивался сегодня?'"></div>
                        <template x-if="day?.weight?.trend_kg">
                            <div class="text-sm">тренд <b x-text="day.weight.trend_kg"></b> кг
                                <span class="text-muted" x-show="day.weight.trend_change_week_kg !== null">(<span x-text="signed(day.weight.trend_change_week_kg)"></span> за неделю)</span>
                            </div>
                        </template>
                    </div>
                    <form class="flex gap-2" @submit.prevent="quickWeight">
                        <input class="input w-24 text-center" inputmode="decimal" x-model="weightInput" :placeholder="day?.weight?.weight_kg ?? 'кг'">
                        <button class="btn" :disabled="busy || !weightInput">OK</button>
                    </form>
                </div>

                {{-- Meals --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between px-1" x-show="day?.meals.length">
                        <h2 class="text-sm font-semibold text-muted">Приёмы пищи</h2>
                        <span class="text-xs text-muted">нажми, чтобы открыть</span>
                    </div>
                    <template x-for="m in day?.meals ?? []" :key="m.id">
                        <div class="card">
                            <button class="flex w-full items-center justify-between text-left" @click="toggleMeal(m)">
                                <div class="min-w-0">
                                    <div class="truncate font-medium" x-text="m.title"></div>
                                    <div class="text-xs text-muted"><span x-text="time(m.eaten_at)"></span> · Б <span x-text="Math.round(m.protein_g)"></span> · Ж <span x-text="Math.round(m.fat_g)"></span> · У <span x-text="Math.round(m.carbs_g)"></span></div>
                                </div>
                                <div class="ml-3 whitespace-nowrap text-lg font-semibold"><span x-text="Math.round(m.kcal_max)"></span><span class="text-xs font-normal text-muted"> ккал</span></div>
                            </button>

                            <div class="mt-3 space-y-3 border-t border-line pt-3 text-sm" x-show="openMeal === m.id && editing?.id !== m.id">
                                <div class="space-y-1" x-show="m.items.length">
                                    <template x-for="i in m.items">
                                        <div class="flex justify-between text-muted"><span><span x-text="i.name"></span><span x-show="i.grams">, <span x-text="Math.round(i.grams)"></span> г</span></span><span x-text="Math.round(i.kcal)"></span></div>
                                    </template>
                                </div>
                                <div class="text-xs text-muted">
                                    <span x-text="sourceLabel(m.source)"></span> · <span x-text="confidenceLabel(m.confidence)"></span>
                                    <span x-show="m.kcal_est !== m.kcal_max"> · оценка <span x-text="Math.round(m.kcal_est)"></span>–<span x-text="Math.round(m.kcal_max)"></span>, в зачёт идёт верхняя</span>
                                </div>
                                <p class="text-xs text-muted" x-show="m.notes" x-text="m.notes"></p>
                                <div class="flex gap-2">
                                    <button class="btn-ghost flex-1" @click="editMeal(m)">Изменить</button>
                                    <button class="btn-ghost flex-1 text-bad" @click="deleteMeal(m)">Удалить</button>
                                </div>
                            </div>

                            <template x-if="editing?.id === m.id">
                                <form class="mt-3 space-y-2 border-t border-line pt-3" @submit.prevent="saveEdit">
                                    <label class="block"><span class="label">Название</span><input class="input" x-model="editing.title"></label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label><span class="label">Ккал (в зачёт)</span><input class="input" inputmode="decimal" x-model="editing.kcal_max"></label>
                                        <label><span class="label">Время</span><input class="input" type="time" x-model="editing.time"></label>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <label><span class="label">Белки, г</span><input class="input" inputmode="decimal" x-model="editing.protein_g"></label>
                                        <label><span class="label">Жиры, г</span><input class="input" inputmode="decimal" x-model="editing.fat_g"></label>
                                        <label><span class="label">Углеводы, г</span><input class="input" inputmode="decimal" x-model="editing.carbs_g"></label>
                                    </div>
                                    <div class="flex gap-2">
                                        <button class="btn flex-1" :disabled="busy">Сохранить</button>
                                        <button type="button" class="btn-ghost" @click="editing = null">Отмена</button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </template>
                    <div class="card text-center text-sm text-muted" x-show="day && !day.meals.length">
                        <div class="mb-1 text-2xl">🍽</div>
                        <span x-text="isToday ? 'Сегодня пока ничего не записано.' : 'В этот день записей нет.'"></span><br>
                        Проще всего сфотографировать еду и отправить Claude.
                    </div>
                </div>

                {{-- Manual add --}}
                <button class="btn-ghost w-full py-3" x-show="!mealForm" @click="newMeal">+ Добавить еду вручную</button>
                <form class="card space-y-3" x-show="mealForm" @submit.prevent="saveNewMeal">
                    <template x-if="mealForm">
                        <div class="space-y-3">
                            <h2 class="font-semibold">Новая запись</h2>
                            <div x-show="products.length">
                                <span class="label">Свой продукт (посчитает по этикетке)</span>
                                <div class="flex gap-2">
                                    <select class="input" x-model="productPick.id" @change="applyProduct">
                                        <option value="">не выбран</option>
                                        <template x-for="p in products" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                                    </select>
                                    <input class="input w-24" inputmode="decimal" x-model="productPick.grams" @change="applyProduct" placeholder="грамм">
                                </div>
                            </div>
                            <p class="text-xs text-muted" x-show="!products.length">Часто ешь одно и то же? Добавь продукты в <button type="button" class="text-kcal underline" @click="tab = 'settings'">Настройках</button>, тогда их можно выбрать здесь.</p>
                            <label class="block"><span class="label">Что съел</span><input class="input" x-model="mealForm.title" placeholder="например, гречка с курицей"></label>
                            <div class="grid grid-cols-2 gap-2">
                                <label><span class="label">Калории, ккал *</span><input class="input" inputmode="decimal" x-model="mealForm.kcal" required></label>
                                <label><span class="label">Время</span><input class="input" type="time" x-model="mealForm.time"></label>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <label><span class="label">Белки, г</span><input class="input" inputmode="decimal" x-model="mealForm.protein_g"></label>
                                <label><span class="label">Жиры, г</span><input class="input" inputmode="decimal" x-model="mealForm.fat_g"></label>
                                <label><span class="label">Углеводы, г</span><input class="input" inputmode="decimal" x-model="mealForm.carbs_g"></label>
                            </div>
                            <div class="flex gap-2">
                                <button class="btn flex-1" :disabled="busy || !mealForm.kcal">Сохранить</button>
                                <button type="button" class="btn-ghost" @click="mealForm = null">Отмена</button>
                            </div>
                        </div>
                    </template>
                </form>
            </section>

            {{-- Weight --}}
            <section x-show="tab === 'weight'" class="space-y-4">
                <h1 class="text-xl font-semibold">Вес</h1>
                <div class="card grid grid-cols-3 gap-2 text-center" x-show="weightBrief?.trend_kg">
                    <div><div class="text-xs text-muted">Тренд</div><div class="text-xl font-bold"><span x-text="weightBrief?.trend_kg"></span></div><div class="text-xs text-muted">кг</div></div>
                    <div><div class="text-xs text-muted">За неделю</div>
                        <div class="text-xl font-bold" :class="weightBrief?.trend_change_week_kg < 0 ? 'text-accent' : (weightBrief?.trend_change_week_kg > 0 ? 'text-warn' : '')" x-text="signed(weightBrief?.trend_change_week_kg)"></div><div class="text-xs text-muted">кг</div></div>
                    <div><div class="text-xs text-muted">До цели</div><div class="text-xl font-bold" x-text="toTarget(weightBrief) ?? '—'"></div>
                        <div class="text-xs text-muted" x-text="weightBrief?.target_weight_kg ? 'кг' : 'цель в настройках'"></div></div>
                </div>
                <div class="flex gap-2">
                    <template x-for="p in [[28, '4 недели'], [91, '3 месяца'], [0, 'Всё']]">
                        <button class="chip" :class="weightPeriod === p[0] && 'chip-on'" @click="weightPeriod = p[0]; run(() => loadWeights())" x-text="p[1]"></button>
                    </template>
                </div>
                <div class="card">
                    <div class="h-60"><canvas id="weightChart"></canvas></div>
                    <p class="mt-2 text-xs text-muted">Точки: взвешивания. Линия: тренд, сглаженный вес без скачков от воды и соли. Смотри на линию.</p>
                </div>
                <form class="card flex gap-2" @submit.prevent="quickWeight">
                    <input class="input" inputmode="decimal" x-model="weightInput" placeholder="Новый вес, кг">
                    <button class="btn" :disabled="busy || !weightInput">Записать</button>
                </form>
                <div class="card divide-y divide-line p-0" x-show="weightList.length">
                    <template x-for="w in weightList" :key="w.id">
                        <div class="flex items-center justify-between px-4 py-2 text-sm">
                            <span class="text-muted" x-text="new Date(w.measured_at).toLocaleDateString('ru-RU', {day: 'numeric', month: 'short'}) + ', ' + w.measured_at.slice(11, 16)"></span>
                            <span class="font-medium" x-text="w.weight_kg + ' кг'"></span>
                            <button class="text-xs text-muted" @click="deleteWeight(w)">удалить</button>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Calories --}}
            <section x-show="tab === 'calories'" class="space-y-4">
                <h1 class="text-xl font-semibold">Калории</h1>
                <template x-for="f in activeFlags" :key="f">
                    <div class="rounded-xl bg-warn/15 px-3 py-2 text-sm text-warn" x-text="'⚠️ ' + flagText[f]"></div>
                </template>
                <div class="card grid grid-cols-2 gap-4" x-show="progress">
                    <div><div class="text-xs text-muted">Норма в день</div><div class="text-2xl font-bold text-accent" x-text="progress?.energy.norm_kcal ?? '—'"></div></div>
                    <div><div class="text-xs text-muted">Ешь в среднем</div><div class="text-2xl font-bold" x-text="progress?.intake.avg_kcal ?? '—'"></div></div>
                    <div><div class="text-xs text-muted">Твой расход</div><div class="text-lg font-semibold" x-text="progress?.energy.tdee_kcal ?? '—'"></div>
                        <div class="text-xs text-muted" x-text="progress?.energy.tdee_source === 'fact' ? 'посчитан по твоим данным' : 'пока по формуле, уточнится через 2 недели'"></div></div>
                    <div><div class="text-xs text-muted">Темп</div><div class="text-lg font-semibold" x-text="signed(progress?.weight.pace_kg_per_week, ' кг/нед')"></div>
                        <div class="text-xs text-muted">записи <span x-text="progress?.intake.logged_days"></span> из <span x-text="progress?.period.days"></span> дней</div></div>
                </div>
                <div class="flex gap-2">
                    <template x-for="p in [[14, '2 недели'], [28, '4 недели'], [91, '3 месяца']]">
                        <button class="chip" :class="calPeriod === p[0] && 'chip-on'" @click="calPeriod = p[0]; run(() => loadCalories())" x-text="p[1]"></button>
                    </template>
                </div>
                <div class="card">
                    <div class="h-60"><canvas id="calChart"></canvas></div>
                    <p class="mt-2 text-xs text-muted">Столбики: съедено за день (оранжевые = выше нормы). Зелёная линия: норма. Пунктир: расход, при котором вес стоит на месте.</p>
                </div>
                <p class="text-xs text-muted" x-show="progress && progress.energy.tdee_reliable === false">Последний пересчёт пропущен: много дней без записей, оставлено прошлое значение.</p>
            </section>

            {{-- Metrics --}}
            <section x-show="tab === 'metrics'" class="space-y-4">
                <h1 class="text-xl font-semibold">Здоровье</h1>
                <div class="flex flex-wrap gap-2">
                    <template x-for="t in metricTypes" :key="t">
                        <button class="chip" :class="metricType === t && 'chip-on'" @click="selectMetric(t)" x-text="metricLabels[t] ?? t"></button>
                    </template>
                    <button class="chip" :class="metricType === '__new' && 'chip-on'" @click="selectMetric('__new')">+ свой</button>
                </div>
                <div class="card" x-show="metricType !== '__new'">
                    <div class="h-52"><canvas id="metricChart"></canvas></div>
                    <p class="text-center text-sm text-muted" x-show="!metricRows.length">Пока нет данных. Запиши ниже или скажи Claude.</p>
                </div>
                <form class="card space-y-2" @submit.prevent="saveMetric">
                    <label class="block" x-show="metricType === '__new'"><span class="label">Название латиницей, например weight_lifted_kg</span><input class="input" x-model="metricForm.type"></label>
                    <div class="grid grid-cols-2 gap-2">
                        <label><span class="label" x-text="(metricLabels[metricForm.type] ?? 'Значение') + (metricHints[metricForm.type] ? ': ' + metricHints[metricForm.type] : '')"></span>
                            <input class="input" inputmode="decimal" x-model="metricForm.value"></label>
                        <label><span class="label">Дата</span><input class="input" type="date" :max="todayIso" x-model="metricForm.date"></label>
                    </div>
                    <button class="btn w-full" :disabled="busy || !metricForm.value || !metricForm.type">Записать</button>
                </form>
                <div class="card divide-y divide-line p-0" x-show="metricRows.length && metricType !== '__new'">
                    <template x-for="m in metricRows.slice(0, 30)" :key="m.id">
                        <div class="flex items-center justify-between px-4 py-2 text-sm">
                            <span class="text-muted" x-text="new Date(m.date + 'T12:00').toLocaleDateString('ru-RU', {day: 'numeric', month: 'short'})"></span>
                            <span class="font-medium" x-text="m.value"></span>
                            <button class="text-xs text-muted" @click="deleteMetric(m)">удалить</button>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Notes --}}
            <section x-show="tab === 'notes'" class="space-y-4">
                <h1 class="text-xl font-semibold">Заметки</h1>
                <p class="text-sm text-muted">Здесь выводы Claude по твоим данным. Попроси его: «проанализируй мой месяц».</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="k in [['', 'Все'], ['observation', 'Наблюдения'], ['suggestion', 'Советы'], ['question', 'Вопросы']]">
                        <button class="chip" :class="noteKind === k[0] && 'chip-on'" @click="filterNotes(k[0])" x-text="k[1]"></button>
                    </template>
                </div>
                <template x-for="n in notes" :key="n.id">
                    <div class="card space-y-2">
                        <div class="flex items-center justify-between text-xs text-muted">
                            <span><span x-text="kindLabel(n.kind)"></span> · <span x-text="n.author === 'claude' ? 'Claude' : 'я'"></span></span>
                            <span x-text="new Date(n.created_at).toLocaleDateString('ru-RU', {day: 'numeric', month: 'short'})"></span>
                        </div>
                        <p class="whitespace-pre-line text-sm" x-text="n.text"></p>
                        <div class="flex flex-wrap items-center gap-1">
                            <template x-for="r in n.related">
                                <button class="chip text-xs" :class="isDate(r) && 'text-kcal'" @click="isDate(r) && goToDate(r)" x-text="isDate(r) ? '📅 ' + r : (metricLabels[r] ?? r)"></button>
                            </template>
                            <button class="ml-auto text-xs text-muted" @click="deleteNote(n)">удалить</button>
                        </div>
                    </div>
                </template>
                <p class="text-center text-sm text-muted" x-show="!notes.length">Заметок пока нет.</p>
                <form class="flex gap-2" @submit.prevent="saveNote">
                    <input class="input" x-model="noteText" placeholder="Своя заметка">
                    <button class="btn" :disabled="busy || !noteText.trim()">Добавить</button>
                </form>
            </section>

            {{-- Settings --}}
            <section x-show="tab === 'settings'" class="space-y-4">
                <h1 class="text-xl font-semibold">Настройки</h1>
                <form class="space-y-4" @submit.prevent="saveSettings">
                    <template x-for="g in settingGroups" :key="g.title">
                        <div class="card space-y-3">
                            <div><div class="font-semibold" x-text="g.title"></div><div class="text-xs text-muted" x-show="g.hint" x-text="g.hint"></div></div>
                            <template x-for="f in g.fields" :key="f.key">
                                <label class="block">
                                    <span class="label" x-text="f.label"></span>
                                    <template x-if="f.options">
                                        <div class="grid gap-2" :class="`grid-cols-${f.options.length}`">
                                            <template x-for="o in f.options">
                                                <button type="button" class="chip px-2 py-2 text-xs" :class="Number(settings[f.key]) === o[0] && 'chip-on'" @click="settings[f.key] = o[0]" x-text="o[1]"></button>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!f.options">
                                        <input class="input" :type="f.type ?? 'number'" :step="f.step" x-model="settings[f.key]" :placeholder="f.optional ? 'не задано' : ''">
                                    </template>
                                </label>
                            </template>
                        </div>
                    </template>
                    <button class="btn w-full py-3" :disabled="busy">Сохранить настройки</button>
                </form>

                <div class="card space-y-3" id="products">
                    <div>
                        <div class="font-semibold">Мои продукты</div>
                        <div class="text-xs text-muted">Что ешь часто. Агент сначала ищет здесь и считает по этим цифрам, а не на глаз.
                            Добавить можно тут, или отправь агенту фото этикетки.</div>
                    </div>
                    <p class="rounded-xl bg-bg px-3 py-2 text-sm text-muted" x-show="!products.length">Пока пусто.</p>
                    <div class="divide-y divide-line" x-show="products.length">
                        <template x-for="p in products" :key="p.id">
                            <div class="flex items-center justify-between gap-2 py-2 text-sm">
                                <div class="min-w-0">
                                    <div class="truncate" x-text="p.name"></div>
                                    <div class="text-xs text-muted">
                                        <span x-text="p.kcal_100g"></span> ккал · Б <span x-text="p.protein_100g"></span> · Ж <span x-text="p.fat_100g"></span> · У <span x-text="p.carbs_100g"></span> на 100 г<span x-show="p.default_portion_g"> · порция <span x-text="p.default_portion_g"></span> г</span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 gap-3 text-xs text-muted">
                                    <button @click="editProduct(p)">изменить</button>
                                    <button @click="deleteProduct(p)">удалить</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="btn-ghost w-full" x-show="!productForm" @click="newProduct">+ Добавить продукт</button>
                    <form class="space-y-2 border-t border-line pt-3" x-show="productForm" @submit.prevent="saveProduct">
                        <template x-if="productForm">
                            <div class="space-y-2">
                                <label class="block"><span class="label">Название (с маркой, если важно)</span><input class="input" x-model="productForm.name" placeholder="например, творог 5% Галичина"></label>
                                <div class="text-xs text-muted">На 100 г, как на этикетке:</div>
                                <div class="grid grid-cols-4 gap-2">
                                    <label><span class="label">Ккал *</span><input class="input" inputmode="decimal" x-model="productForm.kcal_100g" required></label>
                                    <label><span class="label">Белки</span><input class="input" inputmode="decimal" x-model="productForm.protein_100g"></label>
                                    <label><span class="label">Жиры</span><input class="input" inputmode="decimal" x-model="productForm.fat_100g"></label>
                                    <label><span class="label">Углев.</span><input class="input" inputmode="decimal" x-model="productForm.carbs_100g"></label>
                                </div>
                                <label class="block"><span class="label">Обычная порция, г</span><input class="input" inputmode="decimal" x-model="productForm.default_portion_g" placeholder="например, 200"></label>
                                <div class="flex gap-2">
                                    <button class="btn flex-1" :disabled="busy || !productForm.name || !productForm.kcal_100g">Сохранить</button>
                                    <button type="button" class="btn-ghost" @click="productForm = null">Отмена</button>
                                </div>
                            </div>
                        </template>
                    </form>
                </div>

                <div class="card space-y-2 text-sm">
                    <div class="font-semibold">Как подключить агента (Claude и др.)</div>
                    <ol class="list-decimal space-y-1 pl-5 text-muted">
                        <li>claude.ai → Settings → Connectors → Add custom connector.</li>
                        <li>URL: <code class="break-all text-text" x-text="origin + '/mcp'"></code></li>
                        <li>No sign-in → Request headers: <code class="text-text">Authorization</code> = <code class="text-text">Bearer ТОКЕН</code>.</li>
                        <li>В чате включи коннектор и отправь фото еды.</li>
                    </ol>
                </div>
                <button class="btn-ghost w-full" @click="logout">Выйти</button>
            </section>

            {{-- Toast --}}
            <div class="fixed inset-x-4 bottom-24 z-20 mx-auto flex max-w-xl items-center justify-between rounded-xl bg-line px-4 py-3 text-sm shadow-lg" x-show="toast" x-transition>
                <span x-text="toast"></span>
                <button class="ml-3 font-semibold text-accent" x-show="toastAction" @click="undo">Вернуть</button>
            </div>

            {{-- Tabs --}}
            <nav class="fixed inset-x-0 bottom-0 z-10 border-t border-line bg-bg/95 backdrop-blur" style="padding-bottom: env(safe-area-inset-bottom)">
                <div class="mx-auto grid max-w-xl grid-cols-6 text-center text-[11px]">
                    <template x-for="t in [['today', '🍽', 'День'], ['weight', '⚖️', 'Вес'], ['calories', '📊', 'Калории'], ['metrics', '❤️', 'Здоровье'], ['notes', '📝', 'Заметки'], ['settings', '⚙️', 'Настройки']]">
                        <button class="py-2" :class="tab === t[0] ? 'text-accent' : 'text-muted'" @click="tab = t[0]">
                            <div class="text-lg leading-none" x-text="t[1]"></div>
                            <div class="mt-1" x-text="t[2]"></div>
                        </button>
                    </template>
                </div>
            </nav>
        </div>
    </template>
</div>
</body>
</html>
