<?php

namespace App\Actions\Products;

use App\Dto\ProductDto;
use App\Models\Product;
use App\Writers\ProductWriter;

class SaveProductAction
{
    public function __construct(private readonly ProductWriter $writer) {}

    public function handle(ProductDto $dto): Product
    {
        return $this->writer->upsert($dto);
    }
}
