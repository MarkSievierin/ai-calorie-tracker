<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reports\GetDaySummaryAction;
use App\Actions\Reports\GetWeightBriefAction;
use App\Http\Requests\DayRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class DayController
{
    public function __invoke(DayRequest $request, GetDaySummaryAction $summary, GetWeightBriefAction $weight, SettingsService $settings): JsonResponse
    {
        // `today` is the server's idea of the current day (timezone and day start hour), for the web app.
        return response()->json($summary->handle($request->day()) + ['weight' => $weight->handle(), 'today' => $settings->today()]);
    }
}
