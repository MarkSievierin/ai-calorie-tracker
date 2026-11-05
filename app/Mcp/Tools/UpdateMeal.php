<?php

namespace App\Mcp\Tools;

use App\Actions\Meals\UpdateMealAction;
use App\Actions\Reports\GetDayBriefAction;
use App\Dto\MealUpdateDto;
use App\Http\Requests\UpdateMealRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update_meal')]
#[IsIdempotent]
#[Description('Correct a saved meal: pass its id and only the fields that change (same units as log_meal). Use when the user says the portion, time or dish was different. Returns the updated record.')]
class UpdateMeal extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $data = $request->validate(['id' => ['required', 'integer']] + UpdateMealRequest::fieldRules());
        $meal = app(UpdateMealAction::class)->handle((int) $data['id'], MealUpdateDto::fromArray($data, $this->settings()->timezone()));

        return $this->ok([
            'meal' => $meal->toApi($this->settings()->timezone()),
            'day' => app(GetDayBriefAction::class)->handle($this->settings()->localDate($meal->eaten_at)),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Meal id')->required()] + $this->mealSchema($schema, partial: true);
    }
}
