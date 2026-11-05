<?php

namespace App\Http\Controllers\Api;

use App\Actions\Meals\DeleteMealAction;
use App\Actions\Meals\LogMealAction;
use App\Actions\Meals\RestoreMealAction;
use App\Actions\Meals\UpdateMealAction;
use App\Actions\Reports\GetDayBriefAction;
use App\Http\Requests\StoreMealRequest;
use App\Http\Requests\UpdateMealRequest;
use App\Models\Meal;
use App\Readers\MealReader;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class MealController
{
    public function __construct(private readonly SettingsService $settings) {}

    public function store(StoreMealRequest $request, LogMealAction $action, GetDayBriefAction $brief): JsonResponse
    {
        $meal = $action->handle($request->toDto());

        return response()->json([
            'meal' => $meal->toApi($this->settings->timezone()),
            'day' => $brief->handle($this->settings->localDate($meal->eaten_at)),
        ], 201);
    }

    public function update(UpdateMealRequest $request, int $id, UpdateMealAction $action): JsonResponse
    {
        return response()->json(['meal' => $action->handle($id, $request->toDto())->toApi($this->settings->timezone())]);
    }

    public function destroy(int $id, DeleteMealAction $action): JsonResponse
    {
        $action->handle($id);

        return response()->json(['ok' => true]);
    }

    public function restore(int $id, RestoreMealAction $action): JsonResponse
    {
        return response()->json(['meal' => $action->handle($id)->toApi($this->settings->timezone())]);
    }

    public function deleted(MealReader $reader): JsonResponse
    {
        $tz = $this->settings->timezone();

        return response()->json(['meals' => $reader->recentlyDeleted()->map(fn (Meal $m) => $m->toApi($tz))->all()]);
    }
}
