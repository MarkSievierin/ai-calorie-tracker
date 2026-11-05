<?php

namespace App\Actions\Reports;

use App\Dto\DateRangeDto;
use App\Models\Metric;
use App\Readers\MetricReader;

class GetMetricSeriesAction
{
    public function __construct(private readonly MetricReader $metrics) {}

    /** @param list<string>|null $types */
    public function handle(DateRangeDto $range, ?array $types = null): array
    {
        return [
            'types' => $this->metrics->types(),
            'items' => $this->metrics->between($range->from, $range->to, $types)->map(fn (Metric $m) => $m->toApi())->all(),
        ];
    }
}
