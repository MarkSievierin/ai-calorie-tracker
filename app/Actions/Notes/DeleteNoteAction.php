<?php

namespace App\Actions\Notes;

use App\Readers\NoteReader;
use App\Writers\NoteWriter;

class DeleteNoteAction
{
    public function __construct(private readonly NoteReader $reader, private readonly NoteWriter $writer) {}

    public function handle(int $id): void
    {
        $this->writer->delete($this->reader->findOrFail($id));
    }
}
