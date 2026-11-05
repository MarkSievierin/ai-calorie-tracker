<?php

namespace App\Actions\Reports;

use App\Dto\NoteFilterDto;
use App\Models\Note;
use App\Readers\NoteReader;
use App\Services\SettingsService;

class GetNotesAction
{
    public function __construct(private readonly SettingsService $settings, private readonly NoteReader $notes) {}

    public function handle(NoteFilterDto $filter): array
    {
        $tz = $this->settings->timezone();

        return $this->notes->filter(
            $filter->from ? $this->settings->dayBounds($filter->from)[0] : null,
            $filter->to ? $this->settings->dayBounds($filter->to)[1] : null,
            $filter->kind,
            $filter->limit,
        )->map(fn (Note $n) => $n->toApi($tz))->all();
    }
}
