<?php

namespace App\Readers;

use App\Enums\NoteKind;
use App\Models\Note;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class NoteReader
{
    public function findOrFail(int $id): Note
    {
        return Note::query()->findOrFail($id);
    }

    /** @return Collection<int, Note> all standing rules, oldest first */
    public function rules(int $limit = 50): Collection
    {
        return Note::query()->where('kind', NoteKind::Rule)->oldest('created_at')->oldest('id')->limit($limit)->get();
    }

    /** @return Collection<int, Note> dated findings (everything except rules), newest first */
    public function latestFindings(int $limit): Collection
    {
        return Note::query()->where('kind', '!=', NoteKind::Rule)->latest('created_at')->latest('id')->limit($limit)->get();
    }

    /** @return Collection<int, Note> newest first */
    public function filter(?CarbonImmutable $from, ?CarbonImmutable $to, ?NoteKind $kind, int $limit): Collection
    {
        return Note::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->when($kind, fn ($q) => $q->where('kind', $kind))
            ->latest('created_at')->latest('id')
            ->limit($limit)
            ->get();
    }
}
