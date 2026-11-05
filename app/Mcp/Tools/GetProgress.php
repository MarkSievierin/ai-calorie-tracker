<?php

namespace App\Mcp\Tools;

use App\Actions\Reports\GetProgressAction;
use App\Http\Requests\ProgressRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_progress')]
#[IsReadOnly]
#[Description('Progress over the last N complete days: weight trend and pace (kg/week), average intake, adaptive TDEE (real expenditure from intake and trend), recommended daily norm and flags: too_fast (losing >1 kg/week two weeks in a row: suggest eating more), stalled (flat trend 3 weeks despite a deficit), low_logging (<2 meals/day logged: data not reliable), below_min (average below the minimum norm). Use for "how am I doing?".')]
class GetProgress extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $days = (int) ($request->validate(ProgressRequest::fieldRules())['days'] ?? 28);

        return $this->ok(app(GetProgressAction::class)->handle($days));
    }

    public function schema(JsonSchema $schema): array
    {
        return ['days' => $schema->integer()->min(7)->max(365)->nullable()->description('Window length in days, default 28')];
    }
}
