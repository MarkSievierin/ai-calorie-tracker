<?php

namespace App\Http\Requests;

use App\Dto\ProductUpdateDto;

class UpdateProductRequest extends ApiRequest
{
    /** Same limits as StoreProductRequest, every field optional. */
    public static function fieldRules(): array
    {
        return array_map(
            fn (array $rules) => ['sometimes', 'nullable', ...array_values(array_diff($rules, ['required', 'sometimes', 'nullable']))],
            StoreProductRequest::fieldRules(),
        );
    }

    public function toDto(): ProductUpdateDto
    {
        return ProductUpdateDto::fromArray($this->validated());
    }
}
