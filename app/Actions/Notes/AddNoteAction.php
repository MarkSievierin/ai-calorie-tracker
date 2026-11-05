<?php

namespace App\Actions\Notes;

use App\Dto\NoteDto;
use App\Models\Note;
use App\Writers\NoteWriter;

class AddNoteAction
{
    public function __construct(private readonly NoteWriter $writer) {}

    public function handle(NoteDto $dto): Note
    {
        return $this->writer->create($dto);
    }
}
