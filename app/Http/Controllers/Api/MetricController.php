<?php

namespace App\Http\Controllers\Api;

use App\Actions\Metrics\DeleteMetricAction;
use App\Actions\Metrics\LogMetricAction;
use App\Actions\Metrics\SetDailyMetricAction;
use App\Actions\Reports\GetMetricSeriesAction;
use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\StoreMetricRequest;
use Illuminate\Http\JsonResponse;

class MetricController
{
    public function index(DateRangeRequest $request, GetMetricSeriesAction $action): JsonResponse
    {
        $range = $request->toDto(90);

        return response()->json(['from' => $range->from, 'to' => $range->to] + $action->handle($range, $request->types()));
    }

    public function store(StoreMetricRequest $request, LogMetricAction $action): JsonResponse
    {
        return response()->json(['metric' => $action->handle($request->toDto())->toApi()], 201);
    }

    /** Idempotent: sets the day's value. Used by automatic exports, e.g. steps from a phone. */
    public function put(StoreMetricRequest $request, SetDailyMetricAction $action): JsonResponse
    {
        return response()->json(['metric' => $action->handle($request->toDto())->toApi()]);
    }

    public function destroy(int $id, DeleteMetricAction $action): JsonResponse
    {
        $action->handle($id);

        return response()->json(['ok' => true]);
    }
}
