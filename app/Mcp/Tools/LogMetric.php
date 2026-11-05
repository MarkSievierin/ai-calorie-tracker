<?php

namespace App\Mcp\Tools;

use App\Actions\Metrics\LogMetricAction;
use App\Dto\MetricDto;
use App\Http\Requests\StoreMetricRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('log_metric')]
#[Description('Save any other daily metric. Known types: steps (count), sleep_h (hours), bp_sys and bp_dia (mmHg, save blood pressure as two calls), pulse (bpm), waist_cm (cm). A new type is fine: snake_case with the unit in the name. Returns ok.')]
class LogMetric extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $dto = MetricDto::fromArray($request->validate(StoreMetricRequest::fieldRules()), $this->settings()->today());

        return $this->ok(['ok' => true, 'metric' => app(LogMetricAction::class)->handle($dto)->toApi()]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Metric type, snake_case, e.g. steps, sleep_h, bp_sys')->required(),
            'value' => $schema->number()->description('Value in the unit of the type')->required(),
            'date' => $schema->string()->nullable()->description('Local date YYYY-MM-DD; omit for today'),
            'notes' => $schema->string()->nullable(),
        ];
    }
}
