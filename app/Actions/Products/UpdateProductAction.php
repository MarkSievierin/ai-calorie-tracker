<?php

namespace App\Actions\Products;

use App\Dto\ProductUpdateDto;
use App\Models\Product;
use App\Readers\ProductReader;
use App\Writers\ProductWriter;
use Illuminate\Validation\ValidationException;

/**
 * Changes a product by id, so a piece weight, a note or a better name can be set without resending the label.
 * Meals already logged keep their numbers: items store the computed values, not a live link.
 */
class UpdateProductAction
{
    public function __construct(private readonly ProductReader $reader, private readonly ProductWriter $writer) {}

    public function handle(int $id, ProductUpdateDto $dto): Product
    {
        $product = $this->reader->findOrFail($id);

        $name = $dto->attributes['name'] ?? null;
        if ($name !== null && $this->reader->all()->contains(fn (Product $p) => $p->id !== $id && $p->name === $name)) {
            throw ValidationException::withMessages(['name' => "Another product is already named \"$name\"."]);
        }

        return $this->writer->update($product, $dto->attributes);
    }
}
