<?php

namespace App\Models;

use App\Enums\NoteAuthor;
use App\Enums\NoteKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property CarbonImmutable $created_at
 * @property NoteKind $kind
 * @property string $text
 * @property list<string>|null $related
 * @property NoteAuthor $author
 */
class Note extends Model
{
    use SoftDeletes;

    protected $fillable = ['kind', 'text', 'related', 'author'];

    protected function casts(): array
    {
        return [
            'related' => 'array',
            'created_at' => 'immutable_datetime',
            'kind' => NoteKind::class,
            'author' => NoteAuthor::class,
        ];
    }

    public function toApi(string $timezone): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->setTimezone($timezone)->toIso8601String(),
            'kind' => $this->kind->value,
            'text' => $this->text,
            'related' => $this->related ?? [],
            'author' => $this->author->value,
        ];
    }
}
