<?php
namespace App\Http\Controllers;

use App\Http\Resource\NoteResource;
use App\Http\Resource\Validator\CreateNoteValidator;
use Illuminate\Http\Request;

final class NoteController extends Controller
{
    public function list(
        Request $request,
        NoteResource $noteResource,
    )
    {
        return $noteResource->handleGetListRequest($request);
    }

    public function get(
        string $noteId,
        Request $request,
        NoteResource $noteResource,
    )
    {
        $request->request->set('noteId', $noteId);
        return $noteResource->handleGetRequest($request);
    }

    public function create(
        Request $request,
        NoteResource $noteResource,
    ) 
    {
        return $noteResource
            ->useValidator(CreateNoteValidator::class)
            ->handleCreateRequest($request);
    }

    public function update(
        string $noteId,
        Request $request,
        NoteResource $noteResource,
    ) 
    {
        $request->request->set('noteId', $noteId);
        return $noteResource
            ->useValidator(CreateNoteValidator::class)
            ->handleUpdateRequest($request);
    }

    public function delete(
        string $noteId,
        Request $request,
        NoteResource $noteResource,
    ) 
    {
        $request->request->set('noteId', $noteId);
        return $noteResource->handleDeleteRequest($request);
    }

    public function pin(
        string $noteId,
        Request $request,
        NoteResource $noteResource,
    ) 
    {
        $request->request->set('noteId', $noteId);
        return $noteResource->handlePinRequest($request);
    }

    public function unpin(
        string $noteId,
        Request $request,
        NoteResource $noteResource,
    ) 
    {
        $request->request->set('noteId', $noteId);
        return $noteResource->handleUnpinRequest($request);
    }

    public function star(
        string $noteId,
        Request $request,
        NoteResource $noteResource,
    ) 
    {
        $request->request->set('noteId', $noteId);
        return $noteResource->handleStarRequest($request);
    }

    public function unstar(
        string $noteId,
        Request $request,
        NoteResource $noteResource,
    ) 
    {
        $request->request->set('noteId', $noteId);
        return $noteResource->handleUnstarRequest($request);
    }
}