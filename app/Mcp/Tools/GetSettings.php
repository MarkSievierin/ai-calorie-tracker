<?php

namespace App\Mcp\Tools;

use App\Actions\Reports\GetProfileAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_settings')]
#[IsReadOnly]
#[Description('The user profile and goal: height, age, activity factor, target weight, deficit %, minimum norm, timezone, day start hour, plus today\'s date, the current TDEE and daily norm. Use when the user asks about their goal or norm, or before changing a setting.')]
class GetSettings extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        return $this->ok(app(GetProfileAction::class)->handle());
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
