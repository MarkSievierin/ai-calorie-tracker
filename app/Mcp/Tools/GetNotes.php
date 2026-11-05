<?php

namespace App\Mcp\Tools;

use App\Actions\Reports\GetNotesAction;
use App\Dto\NoteFilterDto;
use App\Http\Requests\NotesFilterRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_notes')]
#[IsReadOnly]
#[Description('Read earlier notes (newest first) to keep conclusions consistent across chats. Filter by date range and kind.')]
class GetNotes extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $filter = NoteFilterDto::fromArray($request->validate(NotesFilterRequest::fieldRules()), limit: 100);

        return $this->ok(['notes' => app(GetNotesAction::class)->handle($filter)]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->nullable()->description('YYYY-MM-DD'),
            'to' => $schema->string()->nullable()->description('YYYY-MM-DD'),
            'kind' => $schema->string()->enum(['observation', 'suggestion', 'question'])->nullable(),
        ];
    }
}
