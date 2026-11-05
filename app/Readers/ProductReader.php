<?php

namespace App\Readers;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductReader
{
    public function findOrFail(int $id): Product
    {
        return Product::query()->findOrFail($id);
    }

    /** @return Collection<int, Product> */
    public function all(): Collection
    {
        return Product::query()->orderBy('name')->get();
    }

    /**
     * Case-insensitive substring search by name.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, int $limit = 30): Collection
    {
        return Product::query()
            ->whereRaw('lower(name) like ?', ['%'.mb_strtolower($query).'%'])
            ->orderBy('name')->limit($limit)->get();
    }
}
