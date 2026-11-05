<?php

namespace App\Mcp\Tools;

use App\Actions\Reports\GetDaySummaryAction;
use App\Http\Requests\DayRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_day_summary')]
#[IsReadOnly]
#[Description('Meals of one day with totals (kcal by kcal_max, protein, fat, carbs, sugar, fiber), the daily norm and what is left. Use for "how much is left today?" or "what did I eat yesterday?". Sugar/fiber are null when no meal had them estimated.')]
class GetDaySummary extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $date = $request->validate(DayRequest::fieldRules())['date'] ?? null;

        return $this->ok(app(GetDaySummaryAction::class)->handle($date));
    }

    public function schema(JsonSchema $schema): array
    {
        return ['date' => $schema->string()->nullable()->description('Local date YYYY-MM-DD; omit for today')];
    }
}
