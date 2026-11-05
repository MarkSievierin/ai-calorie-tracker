<?php

namespace App\Mcp\Tools;

use App\Actions\Meals\RestoreMealAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('restore_meal')]
#[Description('Bring back a meal deleted with delete_meal. Returns the restored record.')]
class RestoreMeal extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $id = (int) $request->validate(['id' => ['required', 'integer']])['id'];

        return $this->ok(['meal' => app(RestoreMealAction::class)->handle($id)->toApi($this->settings()->timezone())]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Meal id')->required()];
    }
}
