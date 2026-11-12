<?php

namespace App\Writers;

use App\Dto\ProductDto;
use App\Models\Product;

class ProductWriter
{
    /** Same name overwrites the existing product. */
    public function upsert(ProductDto $dto): Product
    {
        return Product::query()->updateOrCreate(['name' => $dto->name], $dto->toAttributes());
    }

    /** @param array<string, mixed> $attributes only the columns that change */
    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
