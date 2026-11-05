<?php

namespace App\Dto;

use App\Enums\NoteAuthor;
use App\Enums\NoteKind;

final readonly class NoteDto
{
    /** @param list<string> $related */
    public function __construct(
        public NoteKind $kind,
        public string $text,
        public array $related,
        public NoteAuthor $author,
    ) {}

    public static function fromArray(array $data, NoteAuthor $author): self
    {
        return new self(NoteKind::from($data['kind']), (string) $data['text'], array_values($data['related'] ?? []), $author);
    }

    public function toAttributes(): array
    {
        return ['kind' => $this->kind, 'text' => $this->text, 'related' => $this->related, 'author' => $this->author];
    }
}
