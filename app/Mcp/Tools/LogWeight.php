<?php

namespace App\Mcp\Tools;

use App\Actions\Reports\GetWeightBriefAction;
use App\Actions\Weights\LogWeightAction;
use App\Dto\WeightDto;
use App\Http\Requests\StoreWeightRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('log_weight')]
#[Description('Save a body weight measurement in kg, e.g. "I weigh 82.5". Returns the weight, the smoothed trend, the trend 7 days ago and its weekly change. Daily fluctuations are normal: talk about the trend, not the raw number.')]
class LogWeight extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $dto = WeightDto::fromArray($request->validate(StoreWeightRequest::fieldRules()), $this->settings()->timezone());
        $weight = app(LogWeightAction::class)->handle($dto);

        return $this->ok(['id' => $weight->id] + app(GetWeightBriefAction::class)->handle());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'weight_kg' => $schema->number()->min(30)->max(300)->description('Body weight, kg')->required(),
            'measured_at' => $schema->string()->nullable()->description('ISO 8601; omit for "now"'),
            'notes' => $schema->string()->nullable()->description('e.g. "after dinner", "new scale"'),
        ];
    }
}
