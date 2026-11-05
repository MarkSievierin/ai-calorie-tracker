<?php

namespace App\Mcp\Tools;

use App\Actions\Meals\LogMealAction;
use App\Actions\Reports\GetDayBriefAction;
use App\Dto\MealDto;
use App\Http\Requests\StoreMealRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('log_meal')]
#[Description('Save a meal. Call after you have estimated food from a photo, a description or a label. kcal_max is the upper bound of the estimate including hidden oil and sauces. Energy in kcal, macros in grams. Returns the record id and the day totals (eaten / norm / remaining).')]
class LogMeal extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $dto = MealDto::fromArray($request->validate(StoreMealRequest::fieldRules()), $this->settings()->timezone());
        $meal = app(LogMealAction::class)->handle($dto);
        $date = $this->settings()->localDate($meal->eaten_at);

        return $this->ok([
            'id' => $meal->id,
            'eaten_at' => $meal->toApi($this->settings()->timezone())['eaten_at'],
            'day' => app(GetDayBriefAction::class)->handle($date),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->mealSchema($schema, partial: false);
    }
}
