<?php

namespace App\Mcp\Servers;

use App\Actions\Reports\GetProfileAction;
use App\Mcp\Tools;
use App\Models\Note;
use App\Models\Product;
use App\Readers\NoteReader;
use App\Readers\ProductReader;
use App\Services\SettingsService;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('kkal')]
#[Version('1.0.0')]
class KkalServer extends Server
{
    /** All tools in one tools/list page: a client that does not follow the cursor would lose the rest. */
    public int $defaultPaginationLength = 50;

    protected array $tools = [
        Tools\LogMeal::class,
        Tools\UpdateMeal::class,
        Tools\DeleteMeal::class,
        Tools\RestoreMeal::class,
        Tools\LogWeight::class,
        Tools\LogMetric::class,
        Tools\GetDaySummary::class,
        Tools\GetProgress::class,
        Tools\GetMetrics::class,
        Tools\AddNote::class,
        Tools\GetNotes::class,
        Tools\DeleteNote::class,
        Tools\SaveProduct::class,
        Tools\UpdateProduct::class,
        Tools\FindProducts::class,
        Tools\GetSettings::class,
        Tools\UpdateSettings::class,
    ];

    protected function boot(): void
    {
        // Claude in chat cannot read the repo: it only sees these instructions and the tool descriptions.
        // Everything it would otherwise have to remember to look up (profile, rules, products, recent findings)
        // is appended here, so it is known from the first message of every chat.
        $this->instructions = (string) file_get_contents(resource_path('mcp/instructions.md'))
            .$this->math().$this->profile().$this->rules().$this->products().$this->recentNotes();
    }

    /**
     * How the server computes everything, with the numbers taken from config, so the agent explains the real model
     * instead of guessing and the text cannot drift from the code.
     */
    private function math(): string
    {
        $e = config('kkal.energy');

        return strtr((string) file_get_contents(resource_path('mcp/math.md')), [
            '{trend_alpha}' => (string) $e['trend_alpha'],
            '{trend_alpha_pct}' => (string) round($e['trend_alpha'] * 100),
            '{trend_lag}' => (string) round(1 / $e['trend_alpha']),
            '{recalc_every_days}' => (string) $e['recalc_every_days'],
            '{warmup_days}' => (string) $e['warmup_days'],
            '{window_days}' => (string) $e['window_days'],
            '{kcal_per_kg}' => (string) $e['kcal_per_kg'],
            '{keep_old}' => (string) $e['keep_old'],
            '{keep_new}' => (string) round(1 - $e['keep_old'], 2),
            '{max_missing_pct}' => (string) round($e['max_missing_share'] * 100),
        ]);
    }

    private function profile(): string
    {
        $p = app(GetProfileAction::class)->handle();
        $s = $p['settings'];
        $v = fn (mixed $x, string $unit = '') => $x === null ? 'not set' : $x.$unit;

        return "\n\n## Profile (change with update_settings)\n"
            ."- today is {$p['today']} (timezone {$s['timezone']}, a day starts at {$s['day_start_hour']}:00)\n"
            .'- height '.$v($s['height_cm'], ' cm').', age '.$v($s['age']).', activity factor '.$s['activity_factor']."\n"
            .'- target weight '.$v($s['target_weight_kg'], ' kg').', deficit '.$s['deficit_pct'].'%, minimum norm '.$s['min_kcal']." kcal\n"
            .'- TDEE '.$v($p['tdee_kcal'], ' kcal').', daily norm '.$v($p['norm_kcal'], ' kcal')."\n";
    }

    /** Standing facts about the user. All of them, always: a rule must not drop out when newer notes arrive. */
    private function rules(): string
    {
        $rules = app(NoteReader::class)->rules();
        if ($rules->isEmpty()) {
            return '';
        }

        return "\n## Rules (standing facts from the user; apply them, delete_note when outdated)\n"
            .$rules->map(fn (Note $n) => sprintf('- #%d: %s', $n->id, $this->oneLine($n->text)))->implode("\n")."\n";
    }

    /** The saved products, so the agent uses them without having to remember find_products. */
    private function products(int $limit = 80): string
    {
        $products = app(ProductReader::class)->all();
        if ($products->isEmpty()) {
            return '';
        }

        $lines = $products->take($limit)->map(fn (Product $p) => sprintf(
            '- id %d: %s; %s kcal/100 g%s%s%s',
            $p->id,
            $p->name,
            $p->kcal_100g,
            $p->piece_g === null ? '' : "; piece {$p->piece_g} g",
            $p->default_portion_g === null ? '' : "; usual portion {$p->default_portion_g} g",
            $p->notes === null ? '' : '; note: '.$this->oneLine($p->notes, 200),
        ));
        $more = $products->count() > $limit ? "\n- ...and more: use find_products" : '';

        return "\n## Saved products (log them as items with product_id; no piece weight = ask, do not guess)\n"
            .$lines->implode("\n").$more."\n";
    }

    /**
     * The latest dated findings. Older ones stay available through get_notes; rules are listed separately.
     */
    private function recentNotes(int $limit = 10): string
    {
        $notes = app(NoteReader::class)->latestFindings($limit);
        if ($notes->isEmpty()) {
            return '';
        }

        $tz = app(SettingsService::class)->timezone();
        $lines = $notes->map(fn (Note $n) => sprintf(
            '- #%d, %s, %s: %s',
            $n->id,
            $n->created_at->setTimezone($tz)->format('Y-m-d'),
            $n->kind->value,
            $this->oneLine($n->text),
        ));

        return "\n## Latest notes (newest first, older ones via get_notes)\n".$lines->implode("\n")."\n";
    }

    private function oneLine(string $text, int $max = 400): string
    {
        return mb_strimwidth(preg_replace('/\s+/', ' ', $text) ?? '', 0, $max, '...');
    }
}
