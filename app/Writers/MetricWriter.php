<?php

namespace App\Writers;

use App\Dto\MetricDto;
use App\Models\Metric;

class MetricWriter
{
    public function create(MetricDto $dto): Metric
    {
        return Metric::query()->create($dto->toAttributes());
    }

    /** Notes are kept unless new ones are sent. */
    public function update(Metric $metric, MetricDto $dto): Metric
    {
        $metric->update(['value' => $dto->value] + ($dto->notes === null ? [] : ['notes' => $dto->notes]));

        return $metric;
    }

    public function delete(Metric $metric): void
    {
        $metric->delete();
    }
}
