<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reports\GetWeightBriefAction;
use App\Actions\Reports\GetWeightSeriesAction;
use App\Actions\Weights\DeleteWeightAction;
use App\Actions\Weights\LogWeightAction;
use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\StoreWeightRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class WeightController
{
    public function index(DateRangeRequest $request, GetWeightSeriesAction $series, GetWeightBriefAction $brief): JsonResponse
    {
        $range = $request->toDto(28);

        return response()->json(['from' => $range->from, 'to' => $range->to, 'brief' => $brief->handle()] + $series->handle($range));
    }

    public function store(StoreWeightRequest $request, LogWeightAction $action, GetWeightBriefAction $brief, SettingsService $settings): JsonResponse
    {
        $weight = $action->handle($request->toDto());

        return response()->json(['weight' => $weight->toApi($settings->timezone())] + $brief->handle(), 201);
    }

    public function destroy(int $id, DeleteWeightAction $action): JsonResponse
    {
        $action->handle($id);

        return response()->json(['ok' => true]);
    }
}
