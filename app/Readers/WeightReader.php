<?php

namespace App\Readers;

use App\Models\Weight;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WeightReader
{
    public function findOrFail(int $id): Weight
    {
        return Weight::query()->findOrFail($id);
    }

    public function latest(): ?Weight
    {
        return Weight::query()->latest('measured_at')->first();
    }

    /** @return Collection<int, Weight> measured_at in [from, to), oldest first */
    public function between(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Weight::query()->where('measured_at', '>=', $from)->where('measured_at', '<', $to)->orderBy('measured_at')->get();
    }

    /** @return Collection<int, Weight> */
    public function all(): Collection
    {
        return Weight::query()->orderBy('measured_at')->get(['measured_at', 'weight_kg']);
    }
}
