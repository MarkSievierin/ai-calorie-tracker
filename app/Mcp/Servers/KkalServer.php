<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools;
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
        $this->instructions = (string) file_get_contents(resource_path('mcp/instructions.md'));
    }
}
