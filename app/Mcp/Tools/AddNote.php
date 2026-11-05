<?php

namespace App\Mcp\Tools;

use App\Actions\Notes\AddNoteAction;
use App\Dto\NoteDto;
use App\Enums\NoteAuthor;
use App\Http\Requests\StoreNoteRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('add_note')]
#[Description('Save a conclusion from the data so it shows in the app and in later chats. Only observations from the data, no diagnoses. Phrase worrying signals as "show to a doctor". kind: observation (what the data shows), suggestion (what to try), question (what to clarify). related: metric types and/or dates YYYY-MM-DD the note is about.')]
class AddNote extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $dto = NoteDto::fromArray($request->validate(StoreNoteRequest::fieldRules()), NoteAuthor::Claude);

        return $this->ok(['ok' => true, 'id' => app(AddNoteAction::class)->handle($dto)->id]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'kind' => $schema->string()->enum(['observation', 'suggestion', 'question'])->required(),
            'text' => $schema->string()->description('The note, short and concrete')->required(),
            'related' => $schema->array()->items($schema->string())->nullable()->description('e.g. ["weight", "2026-11-05"]'),
        ];
    }
}
