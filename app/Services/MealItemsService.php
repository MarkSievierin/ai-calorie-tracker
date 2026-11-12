<?php

namespace App\Services;

use App\Dto\MealItemDto;
use App\Readers\ProductReader;
use Illuminate\Validation\ValidationException;

/**
 * Turns meal items that point to saved products into exact numbers, so the agent does not do the label arithmetic.
 *
 * When at least one item has product_id, the whole meal is summed from its items:
 * - product item: grams (or pieces x product.piece_g) x per-100 g values; kcal_max = kcal (a label is exact);
 * - other items: the agent gives kcal, protein_g, fat_g, carbs_g per item, kcal_max per item is optional (= kcal).
 * Meal-level kcal_est / kcal_max / protein_g / fat_g / carbs_g must then be omitted: a total sent next to product
 * items would either double count them or silently disagree with them.
 */
class MealItemsService
{
    private const TOTALS = ['kcal_est', 'kcal_max', 'protein_g', 'fat_g', 'carbs_g'];

    public function __construct(private readonly ProductReader $products) {}

    /** @param list<MealItemDto> $items */
    public function hasProducts(array $items): bool
    {
        foreach ($items as $item) {
            if ($item->isProduct()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<MealItemDto>  $items
     * @param  array<string, mixed>  $sentTotals  meal-level totals the client sent (non-null)
     * @return array{items: list<MealItemDto>, kcal_est: float, kcal_max: float, protein_g: float, fat_g: float, carbs_g: float}
     *
     * @throws ValidationException
     */
    public function sum(array $items, array $sentTotals): array
    {
        $errors = [];
        foreach (array_intersect(self::TOTALS, array_keys($sentTotals)) as $key) {
            $errors[$key] = "Omit $key when items use product_id: the meal is summed from its items.";
        }

        $resolved = [];
        $t = ['kcal_est' => 0.0, 'kcal_max' => 0.0, 'protein_g' => 0.0, 'fat_g' => 0.0, 'carbs_g' => 0.0];
        foreach ($items as $i => $item) {
            $item = $item->isProduct() ? $this->fromProduct($i, $item, $errors) : $this->checkEstimated($i, $item, $errors);
            if ($item === null) {
                continue;
            }
            $resolved[] = $item;
            $t['kcal_est'] += (float) $item->kcal;
            $t['kcal_max'] += $item->kcalMax ?? (float) $item->kcal;
            $t['protein_g'] += (float) $item->proteinG;
            $t['fat_g'] += (float) $item->fatG;
            $t['carbs_g'] += (float) $item->carbsG;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return ['items' => $resolved] + array_map(fn (float $v) => round($v, 1), $t);
    }

    private function fromProduct(int $i, MealItemDto $item, array &$errors): ?MealItemDto
    {
        $product = $this->products->find((int) $item->productId);
        if ($product === null) {
            $errors["items.$i.product_id"] = "Product {$item->productId} not found. Call find_products.";

            return null;
        }

        if ($item->grams !== null && $item->pieces !== null) {
            $errors["items.$i.pieces"] = 'Send grams or pieces, not both.';

            return null;
        }
        if ($item->pieces !== null && $product->piece_g === null) {
            $errors["items.$i.pieces"] = "Product \"{$product->name}\" has no piece_g. Ask the user for grams or the weight of one piece, do not guess.";

            return null;
        }
        $grams = $item->pieces !== null ? $item->pieces * (float) $product->piece_g : $item->grams;
        if ($grams === null) {
            $errors["items.$i.grams"] = 'A product item needs grams or pieces.';

            return null;
        }

        $k = $grams / 100;
        $kcal = round($product->kcal_100g * $k, 1);

        return new MealItemDto(
            name: $item->name ?? $product->name,
            grams: round($grams, 1),
            kcal: $kcal,
            productId: $product->id,
            pieces: $item->pieces,
            kcalMax: $kcal,
            proteinG: round($product->protein_100g * $k, 1),
            fatG: round($product->fat_100g * $k, 1),
            carbsG: round($product->carbs_100g * $k, 1),
        );
    }

    private function checkEstimated(int $i, MealItemDto $item, array &$errors): ?MealItemDto
    {
        $missing = array_keys(array_filter(
            ['kcal' => $item->kcal, 'protein_g' => $item->proteinG, 'fat_g' => $item->fatG, 'carbs_g' => $item->carbsG],
            fn ($v) => $v === null,
        ));
        foreach ($missing as $key) {
            $errors["items.$i.$key"] = "Items without product_id need $key when the meal has product items.";
        }
        if ($item->kcalMax !== null && $item->kcal !== null && $item->kcalMax < $item->kcal) {
            $errors["items.$i.kcal_max"] = 'kcal_max must be >= kcal.';
        }

        return $missing === [] ? $item : null;
    }
}
