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
        $opt = fn (string $what) => $schema->number()->min(0)->nullable()
            ->description("$what, grams. Fill only when you can estimate it, otherwise omit (never send 0 for unknown)");

        $sumNote = '. Omit when any item has product_id: then the server sums the meal from its items';
        $total = fn (string $what) => $schema->number()->min(0)->description($what.$sumNote);

        return [
            'title' => $r($schema->string()->description('Short dish name, e.g. "Omelette with bread"')),
            'items' => $r($schema->array()->description(
                'Components of the meal. For a saved product (find_products) send product_id with grams or pieces '
                .'and nothing else: the server computes kcal and macros from the label. In a meal with product items, '
                .'every other item needs kcal, protein_g, fat_g, carbs_g (and kcal_max if above kcal)'
            )->items($schema->object([
                'product_id' => $schema->integer()->nullable()->description('Saved product id from find_products'),
                'name' => $schema->string()->nullable()->description('Item name. Optional for product items (product name is used)'),
                'grams' => $schema->number()->min(0)->nullable()->description('Portion weight, grams'),
                'pieces' => $schema->number()->min(0)->nullable()
                    ->description('Number of pieces, only for a product with piece_g. Send grams or pieces, not both'),
                'kcal' => $schema->number()->min(0)->nullable()->description('Energy of this item, kcal. Required unless product_id'),
                'kcal_max' => $schema->number()->min(0)->nullable()->description('Upper bound for this item, kcal (product-summed meals only)'),
                'protein_g' => $schema->number()->min(0)->nullable()->description('Protein of this item, grams (product-summed meals only)'),
                'fat_g' => $schema->number()->min(0)->nullable()->description('Fat of this item, grams (product-summed meals only)'),
                'carbs_g' => $schema->number()->min(0)->nullable()->description('Carbohydrates of this item, grams (product-summed meals only)'),
            ]))),
            'kcal_est' => $total('Best estimate of energy, kcal'),
            'kcal_max' => $total(
                'Upper bound of the estimate including hidden oil, butter, sauces and dressings, kcal. Must be >= kcal_est. Daily totals use this value'
            ),
            'protein_g' => $total('Protein, grams'),
            'fat_g' => $total('Fat, grams'),
            'carbs_g' => $total('Carbohydrates, grams'),
            'sugar_g' => $opt('Sugar'),
            'fiber_g' => $opt('Fiber'),
            'sat_fat_g' => $opt('Saturated fat'),
            'salt_g' => $opt('Salt'),
            'confidence' => $r($schema->string()->enum(['low', 'medium', 'high'])->description('How sure you are about the estimate')),
            'source' => $r($schema->string()->enum(['photo', 'text', 'label'])
                ->description('photo = estimated from a photo, text = from a description, label = from a nutrition label')),
            'eaten_at' => $schema->string()->nullable()
                ->description('When it was really eaten, ISO 8601. Without an offset it is read in the user timezone. Omit for "now". Never shift the time to move a meal to another day: the day start hour setting does that'),
            'notes' => $schema->string()->nullable()->description('Anything worth remembering about this meal'),
        ];
    }
}
