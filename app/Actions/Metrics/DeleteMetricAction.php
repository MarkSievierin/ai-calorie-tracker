<?php

namespace App\Actions\Metrics;

use App\Readers\MetricReader;
use App\Writers\MetricWriter;

class DeleteMetricAction
{
    public function __construct(private readonly MetricReader $reader, private readonly MetricWriter $writer) {}

    public function handle(int $id): void
    {
        $this->writer->delete($this->reader->findOrFail($id));
    }
}
