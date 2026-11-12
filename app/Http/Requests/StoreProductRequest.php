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
            'piece_g' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:5000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function toDto(): ProductDto
    {
        return ProductDto::fromArray($this->validated());
    }
}
