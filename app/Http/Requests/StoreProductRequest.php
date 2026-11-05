<?php

namespace App\Http\Requests;

use App\Dto\ProductDto;

class StoreProductRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'kcal_100g' => ['required', 'numeric', 'between:0,1000'],
            'protein_100g' => ['required', 'numeric', 'between:0,100'],
            'fat_100g' => ['required', 'numeric', 'between:0,100'],
            'carbs_100g' => ['required', 'numeric', 'between:0,100'],
            'default_portion_g' => ['sometimes', 'nullable', 'numeric', 'between:0,5000'],
        ];
    }

    public function toDto(): ProductDto
    {
        return ProductDto::fromArray($this->validated());
    }
}
