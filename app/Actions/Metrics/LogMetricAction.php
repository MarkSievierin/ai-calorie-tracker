<?php

namespace App\Actions\Metrics;

use App\Dto\MetricDto;
use App\Models\Metric;
use App\Writers\MetricWriter;

class LogMetricAction
{
    public function __construct(private readonly MetricWriter $writer) {}

    public function handle(MetricDto $dto): Metric
    {
        return $this->writer->create($dto);
    }
}
