<?php

namespace App\Http\Requests;

use App\Dto\NoteFilterDto;
use App\Enums\NoteKind;
use Illuminate\Validation\Rule;

class NotesFilterRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return [
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'kind' => ['sometimes', 'nullable', Rule::enum(NoteKind::class)],
        ];
    }

    public function toDto(int $limit = 200): NoteFilterDto
    {
        return NoteFilterDto::fromArray($this->validated(), $limit);
    }
}
