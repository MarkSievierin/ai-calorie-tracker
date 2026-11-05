<?php

namespace App\Mcp\Tools;

use App\Actions\Meals\DeleteMealAction;
use App\Actions\Reports\GetDayBriefAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('delete_meal')]
#[IsDestructive]
#[Description('Delete a meal saved by mistake or twice. Soft delete: it can be restored with restore_meal. Returns ok and the day totals.')]
class DeleteMeal extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $id = (int) $request->validate(['id' => ['required', 'integer']])['id'];
        $meal = app(DeleteMealAction::class)->handle($id);

        return $this->ok(['ok' => true, 'id' => $id, 'day' => app(GetDayBriefAction::class)->handle($this->settings()->localDate($meal->eaten_at))]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Meal id')->required()];
    }
}
