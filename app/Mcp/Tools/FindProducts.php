<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use App\Readers\ProductReader;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('find_products')]
#[IsReadOnly]
#[Description('Look up saved products by words of the name (any order, any word form) when the list in the instructions is not enough or may be stale. Returns per-100 g values, default_portion_g, piece_g (weight of one piece) and notes. If found, log the meal with items[].product_id and grams or pieces: the server computes kcal and macros. Read the product notes before using it.')]
class FindProducts extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $query = (string) ($request->validate(['query' => ['sometimes', 'nullable', 'string', 'max:100']])['query'] ?? '');

        return $this->ok(['products' => app(ProductReader::class)->search($query)->map(fn (Product $p) => $p->toApi())->all()]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['query' => $schema->string()->nullable()->description('Words from the name; empty = all')];
    }
}
