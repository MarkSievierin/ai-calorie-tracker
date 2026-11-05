<?php

namespace App\Actions\Reports;

/** Short day totals returned after a write: eaten / norm / remaining. */
class GetDayBriefAction
{
    public function __construct(private readonly GetDaySummaryAction $summary) {}

    public function handle(string $date): array
    {
        $s = $this->summary->handle($date);

        return [
            'date' => $date,
            'eaten_kcal' => $s['totals']['kcal'],
            'norm_kcal' => $s['norm_kcal'],
            'remaining_kcal' => $s['remaining_kcal'],
            'meals_count' => count($s['meals']),
        ];
    }
}
