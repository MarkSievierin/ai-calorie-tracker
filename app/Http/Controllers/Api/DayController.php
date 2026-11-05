<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reports\GetDaySummaryAction;
use App\Actions\Reports\GetWeightBriefAction;
use App\Http\Requests\DayRequest;
use Illuminate\Http\JsonResponse;

class DayController
{
    public function __invoke(DayRequest $request, GetDaySummaryAction $summary, GetWeightBriefAction $weight): JsonResponse
    {
        return response()->json($summary->handle($request->day()) + ['weight' => $weight->handle()]);
    }
}
