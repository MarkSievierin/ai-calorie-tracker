<?php

namespace App\Http\Controllers\Api;

use App\Actions\Settings\UpdateSettingsAction;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController
{
    public function show(SettingsService $settings): JsonResponse
    {
        return response()->json(['settings' => $settings->all(), 'defaults' => config('kkal.defaults')]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettingsAction $action): JsonResponse
    {
        return response()->json(['settings' => $action->handle($request->toDto())]);
    }
}
