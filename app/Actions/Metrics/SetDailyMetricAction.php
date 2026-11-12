<?php

namespace App\Actions\Metrics;

use App\Dto\MetricDto;
use App\Models\Metric;
use App\Readers\MetricReader;
use App\Writers\MetricWriter;

/**
 * Sets the value of a metric for a day, replacing what is there. For automatic senders (a phone that exports
 * its step count): they repeat the same day many times with a growing total, and a plain "log" would pile up
 * duplicates. Manual logging (several blood pressure readings a day) keeps using LogMetricAction.
 */
class SetDailyMetricAction
{
    public function __construct(private readonly MetricReader $reader, private readonly MetricWriter $writer) {}

    public function handle(MetricDto $dto): Metric
    {
        $existing = $this->reader->between($dto->date, $dto->date, [$dto->type]);
        $keep = $existing->shift();
        if ($keep === null) {
            return $this->writer->create($dto);
        }
        // Earlier manual entries of the same day (e.g. a partial count typed in chat) give way to the full total.
        $existing->each(fn (Metric $m) => $this->writer->delete($m));

        return $this->writer->update($keep, $dto);
    }
}
