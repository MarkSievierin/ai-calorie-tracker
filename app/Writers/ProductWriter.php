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

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
