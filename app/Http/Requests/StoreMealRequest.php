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

        return [
            'title' => [$req, 'string', 'min:1', 'max:200'],
            'items' => [$req, 'array', 'max:50'],
            'items.*.name' => ['required', 'string', 'max:200'],
            'items.*.grams' => ['nullable', 'numeric', 'between:0,5000'],
            'items.*.kcal' => ['required', 'numeric', 'between:0,10000'],
            'kcal_est' => [$req, 'numeric', 'between:0,20000'],
            'kcal_max' => [$req, 'numeric', 'between:0,20000', ...($partial ? [] : ['gte:kcal_est'])],
            'protein_g' => [$req, 'numeric', 'between:0,5000'],
            'fat_g' => [$req, 'numeric', 'between:0,5000'],
            'carbs_g' => [$req, 'numeric', 'between:0,5000'],
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
