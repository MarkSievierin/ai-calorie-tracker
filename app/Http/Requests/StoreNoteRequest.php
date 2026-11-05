<?php

namespace App\Http\Requests;

use App\Dto\NoteDto;
use App\Enums\NoteAuthor;
use App\Enums\NoteKind;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends ApiRequest
{
    public static function fieldRules(): array
    {
        return [
            'kind' => ['required', Rule::enum(NoteKind::class)],
            'text' => ['required', 'string', 'max:5000'],
            'related' => ['sometimes', 'nullable', 'array', 'max:50'],
            'related.*' => ['string', 'max:40'],
        ];
    }

    /** Notes from the web app are the user's; Claude writes through MCP. */
    public function toDto(): NoteDto
    {
        return NoteDto::fromArray($this->validated(), NoteAuthor::User);
    }
}
