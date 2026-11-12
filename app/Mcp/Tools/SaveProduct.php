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
#[Description('Save a packaged product from its nutrition label (values per 100 g) so later meals with it are counted exactly. Same name overwrites the label values; default_portion_g, piece_g and notes change only when sent (null clears). Also use it to add a piece weight or a note to an existing product: send its exact name and current label values from find_products. Returns the product.')]
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
            'name' => $schema->string()->description('Product name with brand, e.g. "Greek yogurt 2% Galychyna"')->required(),
            'kcal_100g' => $schema->number()->min(0)->description('kcal per 100 g')->required(),
            'protein_100g' => $schema->number()->min(0)->description('g per 100 g')->required(),
            'fat_100g' => $schema->number()->min(0)->description('g per 100 g')->required(),
            'carbs_100g' => $schema->number()->min(0)->description('g per 100 g')->required(),
            'default_portion_g' => $schema->number()->min(0)->nullable()->description('Usual portion, grams'),
            'piece_g' => $schema->number()->min(0)->nullable()
                ->description('Weight of one piece, grams (one stick, egg, candy). Only from the label or a weighing, never a guess'),
            'notes' => $schema->string()->nullable()
                ->description('Short facts about the product, e.g. "weighed: 20 pcs = 25 g". Shown with find_products. Max 500 chars'),
        ];
    }
}
