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

    public function delete(Metric $metric): void
    {
        $metric->delete();
    }
}
