<?php

namespace App\Readers;

use App\Models\Meal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Soft-deleted meals are excluded unless a method says otherwise. */
class MealReader
{
    public function findOrFail(int $id): Meal
    {
        return Meal::query()->findOrFail($id);
    }

    public function findWithTrashedOrFail(int $id): Meal
    {
        return Meal::withTrashed()->findOrFail($id);
    }

    /** @return Collection<int, Meal> meals with eaten_at in [from, to), oldest first */
    public function between(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Meal::query()->where('eaten_at', '>=', $from)->where('eaten_at', '<', $to)->orderBy('eaten_at')->get();
    }

    /** @return Collection<int, Meal> only eaten_at and kcal_max, whole history */
    public function allForTotals(): Collection
    {
        return Meal::query()->orderBy('eaten_at')->get(['eaten_at', 'kcal_max']);
    }

    /** @return Collection<int, Meal> */
    public function recentlyDeleted(int $limit = 20): Collection
    {
        return Meal::onlyTrashed()->latest('deleted_at')->limit($limit)->get();
    }
}
