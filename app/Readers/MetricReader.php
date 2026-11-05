<?php

namespace App\Readers;

use App\Models\Metric;
use Illuminate\Support\Collection;

class MetricReader
{
    public function findOrFail(int $id): Metric
    {
        return Metric::query()->findOrFail($id);
    }

    /**
     * @param  list<string>|null  $types
     * @return Collection<int, Metric> oldest first; same-day values keep insert order
     */
    public function between(string $from, string $to, ?array $types = null): Collection
    {
        return Metric::query()
            ->whereBetween('date', [$from, $to])
            ->when($types, fn ($q) => $q->whereIn('type', $types))
            ->orderBy('date')->orderBy('id')
            ->get();
    }

    /** @return list<string> */
    public function types(): array
    {
        return Metric::query()->distinct()->orderBy('type')->pluck('type')->all();
    }
}
