<?php

namespace App\Http\Controllers\Api;

use App\Actions\Notes\AddNoteAction;
use App\Actions\Notes\DeleteNoteAction;
use App\Actions\Reports\GetNotesAction;
use App\Http\Requests\NotesFilterRequest;
use App\Http\Requests\StoreNoteRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class NoteController
{
    public function index(NotesFilterRequest $request, GetNotesAction $action): JsonResponse
    {
        return response()->json(['notes' => $action->handle($request->toDto())]);
    }

    public function store(StoreNoteRequest $request, AddNoteAction $action, SettingsService $settings): JsonResponse
    {
        return response()->json(['note' => $action->handle($request->toDto())->toApi($settings->timezone())], 201);
    }

    public function destroy(int $id, DeleteNoteAction $action): JsonResponse
    {
        $action->handle($id);

        return response()->json(['ok' => true]);
    }
}
