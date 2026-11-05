<?php

namespace App\Mcp\Tools;

use App\Actions\Reports\GetRawDaysAction;
use App\Dto\DateRangeDto;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_metrics')]
#[IsReadOnly]
#[Description('Raw data per day for analysis: food totals (kcal by kcal_max, macros, meal count), weight (daily average) and trend, other metrics. Max 366 days per call.')]
class GetMetrics extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'types' => ['sometimes', 'nullable', 'array'],
            'types.*' => ['string', 'max:40'],
        ]);
        if (CarbonImmutable::parse($data['from'])->diffInDays($data['to']) > 366) {
            throw ValidationException::withMessages(['to' => 'Range is limited to 366 days']);
        }

        return $this->ok(['days' => app(GetRawDaysAction::class)->handle(new DateRangeDto($data['from'], $data['to']), $data['types'] ?? null)]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->description('YYYY-MM-DD')->required(),
            'to' => $schema->string()->description('YYYY-MM-DD, inclusive')->required(),
            'types' => $schema->array()->items($schema->string())->nullable()->description('Only these metric types; omit for all'),
        ];
    }
}
