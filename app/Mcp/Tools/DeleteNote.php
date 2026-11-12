<?php

namespace App\Mcp\Tools;

use App\Actions\Notes\DeleteNoteAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('delete_note')]
#[IsDestructive]
#[Description('Remove a note that is outdated, wrong or replaced by a newer one (or by a product field), so it stops misleading later chats. To correct a note: delete it and add the right one. Returns ok.')]
class DeleteNote extends KkalTool
{
    public function handle(Request $request): ResponseFactory
    {
        $id = (int) $request->validate(['id' => ['required', 'integer']])['id'];
        app(DeleteNoteAction::class)->handle($id);

        return $this->ok(['ok' => true, 'id' => $id]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Note id (#N in the instructions, id in get_notes)')->required()];
    }
}
