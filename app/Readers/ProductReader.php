<?php

namespace App\Readers;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductReader
{
    public function find(int $id): ?Product
    {
        return Product::query()->find($id);
    }

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
     * Search by words: every word of the query must occur in the name, in any order and case.
     * Words of 5+ letters are matched without their last two letters, so inflected Russian/Ukrainian forms
     * ("соломки", "соломку") still find "Соломка". The list is small (one user), so it is filtered in PHP:
     * the same result on SQLite and Postgres, whose lower() treats Cyrillic differently.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, int $limit = 30): Collection
    {
        $words = array_map(
            fn (string $w) => mb_strlen($w) >= 5 ? mb_substr($w, 0, -2) : $w,
            preg_split('/[^\p{L}\p{N}%]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [],
        );

        return $this->all()->filter(function (Product $p) use ($words) {
            $name = mb_strtolower($p->name);
            foreach ($words as $word) {
                if (! str_contains($name, $word)) {
                    return false;
                }
            }

            return true;
        })->take($limit)->values();
    }
}
