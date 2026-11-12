<?php

namespace App\Mcp\Tools;

use App\Actions\Products\UpdateProductAction;
use App\Dto\ProductUpdateDto;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update_product')]
#[IsIdempotent]
#[Description('Change a saved product by id: pass only the fields that change. Use it to set piece_g after the user weighed pieces, to add a note, to rename, or to fix label values. No need to resend the label. Meals already logged keep their numbers. Returns the product.')]
class UpdateProduct extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $data = $request->validate(['id' => ['required', 'integer']] + UpdateProductRequest::fieldRules());

        return $this->ok(['product' => app(UpdateProductAction::class)->handle((int) $data['id'], ProductUpdateDto::fromArray($data))->toApi()]);
    }

    public function schema(JsonSchema $schema): array
    {
        $num = fn (string $what) => $schema->number()->min(0)->nullable()->description($what);

        return [
            'id' => $schema->integer()->description('Product id from the saved products list or find_products')->required(),
            'name' => $schema->string()->nullable()->description('New name'),
            'kcal_100g' => $num('kcal per 100 g'),
            'protein_100g' => $num('g per 100 g'),
            'fat_100g' => $num('g per 100 g'),
            'carbs_100g' => $num('g per 100 g'),
            'default_portion_g' => $num('Usual portion, grams'),
            'piece_g' => $num('Weight of one piece, grams (one stick, egg, can, cake). Only from the label or a weighing, never a guess'),
            'notes' => $schema->string()->nullable()->description('Short facts about the product; replaces the old note. Max 500 chars'),
        ];
    }
}
