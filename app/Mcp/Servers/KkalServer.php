<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools;
use App\Models\Note;
use App\Readers\NoteReader;
use App\Services\SettingsService;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('kkal')]
#[Version('1.0.0')]
class KkalServer extends Server
{
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
        Tools\SaveProduct::class,
        Tools\FindProducts::class,
    ];

    protected function boot(): void
    {
        // Claude in chat cannot read the repo: it only sees these instructions and the tool descriptions.
        $this->instructions = (string) file_get_contents(resource_path('mcp/instructions.md')).$this->recentNotes();
    }

    /**
     * The latest notes go into the instructions, so the agent knows earlier findings (product weighings, corrections)
     * from the first message without remembering to call get_notes. Older notes stay available through get_notes.
     */
    private function recentNotes(int $limit = 10): string
    {
        $notes = app(NoteReader::class)->filter(null, null, null, $limit);
        if ($notes->isEmpty()) {
            return '';
        }

        $tz = app(SettingsService::class)->timezone();
        $lines = $notes->map(fn (Note $n) => sprintf(
            '- %s, %s: %s',
            $n->created_at->setTimezone($tz)->format('Y-m-d'),
            $n->kind->value,
            mb_strimwidth(preg_replace('/\s+/', ' ', $n->text) ?? '', 0, 400, '...'),
        ));

        return "\n\n## Latest notes (newest first, older ones via get_notes)\n".$lines->implode("\n")."\n";
    }
}
