<?php

namespace App\Mcp\Tools;

use App\Actions\Products\SaveProductAction;
use App\Dto\ProductDto;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('save_product')]
#[Description('Save a new product (values per 100 g) so later meals with it are counted the same way every time: a packaged product from its nutrition label, or a food the user eats often (banana, boiled egg, home-cooked bulgur with butter) with reference values, with "reference values, not a label" in notes. Same name overwrites the values. To change an existing product (piece weight, note, name) use update_product. Returns the product.')]
class SaveProduct extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $dto = ProductDto::fromArray($request->validate(StoreProductRequest::fieldRules()));

        return $this->ok(['product' => app(SaveProductAction::class)->handle($dto)->toApi()]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Product name, with brand for packaged food, e.g. "Greek yogurt 2% Galychyna", "Banana"')->required(),
            'kcal_100g' => $schema->number()->min(0)->description('kcal per 100 g')->required(),
            'protein_100g' => $schema->number()->min(0)->description('g per 100 g')->required(),
            'fat_100g' => $schema->number()->min(0)->description('g per 100 g')->required(),
            'carbs_100g' => $schema->number()->min(0)->description('g per 100 g')->required(),
            'default_portion_g' => $schema->number()->min(0)->nullable()->description('Usual portion, grams'),
            'piece_g' => $schema->number()->min(0)->nullable()
                ->description('Weight of one piece, grams (one stick, egg, can, cake). Only from the label or a weighing, never a guess'),
            'notes' => $schema->string()->nullable()
                ->description('Short facts about the product, e.g. "weighed: 20 pcs = 25 g". Shown with find_products. Max 500 chars'),
        ];
    }
}
