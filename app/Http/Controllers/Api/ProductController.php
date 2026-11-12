<?php

namespace App\Http\Controllers\Api;

use App\Actions\Products\DeleteProductAction;
use App\Actions\Products\SaveProductAction;
use App\Actions\Products\UpdateProductAction;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Readers\ProductReader;
use Illuminate\Http\JsonResponse;

class ProductController
{
    public function index(ProductReader $reader): JsonResponse
    {
        return response()->json(['products' => $reader->all()->map(fn (Product $p) => $p->toApi())->all()]);
    }

    public function store(StoreProductRequest $request, SaveProductAction $action): JsonResponse
    {
        return response()->json(['product' => $action->handle($request->toDto())->toApi()], 201);
    }

    public function update(int $id, UpdateProductRequest $request, UpdateProductAction $action): JsonResponse
    {
        return response()->json(['product' => $action->handle($id, $request->toDto())->toApi()]);
    }

    public function destroy(int $id, DeleteProductAction $action): JsonResponse
    {
        $action->handle($id);

        return response()->json(['ok' => true]);
    }
}
