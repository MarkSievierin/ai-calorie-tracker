<?php

namespace App\Writers;

use App\Dto\NoteDto;
use App\Models\Note;

class NoteWriter
{
    public function create(NoteDto $dto): Note
    {
        return Note::query()->create($dto->toAttributes());
    }

    public function delete(Note $note): void
    {
        $note->delete();
    }
}
