<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reports\GetCaloriesSeriesAction;
use App\Actions\Reports\GetProgressAction;
use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\ProgressRequest;
use Illuminate\Http\JsonResponse;

class ReportController
{
    public function calories(DateRangeRequest $request, GetCaloriesSeriesAction $action): JsonResponse
    {
        $range = $request->toDto(28);

        return response()->json(['from' => $range->from, 'to' => $range->to, 'days' => $action->handle($range)]);
    }

    public function progress(ProgressRequest $request, GetProgressAction $action): JsonResponse
    {
        return response()->json($action->handle($request->days()));
    }
}
