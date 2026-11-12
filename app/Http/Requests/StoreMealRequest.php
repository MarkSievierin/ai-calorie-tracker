<?php

namespace App\Http\Requests;

use App\Dto\MealDto;
use App\Enums\Confidence;
use App\Enums\MealSource;
use Illuminate\Validation\Rule;

class StoreMealRequest extends ApiRequest
{
    public static function fieldRules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        // Totals may be omitted when items use product_id; LogMealAction checks which case it is.
        $total = $partial ? 'sometimes' : 'nullable';

        return [
            'title' => [$req, 'string', 'min:1', 'max:200'],
            'items' => [$req, 'array', 'max:50'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.name' => ['required_without:items.*.product_id', 'nullable', 'string', 'max:200'],
            'items.*.grams' => ['nullable', 'numeric', 'between:0,5000'],
            'items.*.pieces' => ['nullable', 'numeric', 'gt:0', 'max:1000'],
            'items.*.kcal' => ['required_without:items.*.product_id', 'nullable', 'numeric', 'between:0,10000'],
            'items.*.kcal_max' => ['nullable', 'numeric', 'between:0,10000'],
            'items.*.protein_g' => ['nullable', 'numeric', 'between:0,5000'],
            'items.*.fat_g' => ['nullable', 'numeric', 'between:0,5000'],
            'items.*.carbs_g' => ['nullable', 'numeric', 'between:0,5000'],
            'kcal_est' => [$total, 'numeric', 'between:0,20000'],
            'kcal_max' => [$total, 'numeric', 'between:0,20000'], // >= kcal_est is checked in the actions
            'protein_g' => [$total, 'numeric', 'between:0,5000'],
            'fat_g' => [$total, 'numeric', 'between:0,5000'],
            'carbs_g' => [$total, 'numeric', 'between:0,5000'],
            'sugar_g' => ['sometimes', 'nullable', 'numeric', 'between:0,5000'],
            'fiber_g' => ['sometimes', 'nullable', 'numeric', 'between:0,5000'],
            'sat_fat_g' => ['sometimes', 'nullable', 'numeric', 'between:0,5000'],
            'salt_g' => ['sometimes', 'nullable', 'numeric', 'between:0,200'],
            'confidence' => [$req, Rule::enum(Confidence::class)],
            'source' => [$req, Rule::enum(MealSource::class)],
            'eaten_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function toDto(): MealDto
    {
        return MealDto::fromArray($this->validated(), $this->settings()->timezone());
    }
}
