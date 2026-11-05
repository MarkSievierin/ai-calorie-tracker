<?php

namespace App\Mcp\Tools;

use App\Services\SettingsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Base for MCP tools. Tools validate with the same FormRequest rules as REST, build a DTO and call an Action.
 */
abstract class KkalTool extends Tool
{
    protected function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    protected function ok(array $data): ResponseFactory
    {
        return Response::structured($data);
    }

    /** Meal fields shared by log_meal and update_meal. */
    protected function mealSchema(JsonSchema $schema, bool $partial): array
    {
        $r = fn ($type) => $partial ? $type : $type->required();
        $g = fn (string $what) => $schema->number()->min(0)->description("$what, grams");
        $opt = fn (string $what) => $schema->number()->min(0)->nullable()
            ->description("$what, grams. Fill only when you can estimate it, otherwise omit (never send 0 for unknown)");

        return [
            'title' => $r($schema->string()->description('Short dish name, e.g. "Omelette with bread"')),
            'items' => $r($schema->array()->description('Components of the meal')->items($schema->object([
                'name' => $schema->string()->description('Item name')->required(),
                'grams' => $schema->number()->min(0)->nullable()->description('Portion weight, grams'),
                'kcal' => $schema->number()->min(0)->description('Energy of this item, kcal')->required(),
            ]))),
            'kcal_est' => $r($schema->number()->min(0)->description('Best estimate of energy, kcal')),
            'kcal_max' => $r($schema->number()->min(0)->description(
                'Upper bound of the estimate including hidden oil, butter, sauces and dressings, kcal. Must be >= kcal_est. Daily totals use this value'
            )),
            'protein_g' => $r($g('Protein')),
            'fat_g' => $r($g('Fat')),
            'carbs_g' => $r($g('Carbohydrates')),
            'sugar_g' => $opt('Sugar'),
            'fiber_g' => $opt('Fiber'),
            'sat_fat_g' => $opt('Saturated fat'),
            'salt_g' => $opt('Salt'),
            'confidence' => $r($schema->string()->enum(['low', 'medium', 'high'])->description('How sure you are about the estimate')),
            'source' => $r($schema->string()->enum(['photo', 'text', 'label'])
                ->description('photo = estimated from a photo, text = from a description, label = from a nutrition label')),
            'eaten_at' => $schema->string()->nullable()
                ->description('When it was eaten, ISO 8601. Without an offset it is read in the user timezone. Omit for "now"'),
            'notes' => $schema->string()->nullable()->description('Anything worth remembering about this meal'),
        ];
    }
}
