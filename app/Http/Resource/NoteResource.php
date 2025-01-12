<?php
namespace App\Http\Resource;

use App\Http\Api\BasicResource;
use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\ResourceValidator;
use App\LLM\Assistant;
use App\Models\Note;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class NoteResource extends BasicResource
{
    CONST FILE_PATH = __DIR__ . '/../../../documents/notes/';

    public function __construct(
        private readonly Assistant $assistant,
        OutputBuilder $outputBuilder,
        ResourceValidator ...$resourceValidators,
    )
    {
        parent::__construct($outputBuilder, ...$resourceValidators);
    }

    protected function getListRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    )
    {
        $userId = auth()->user()->id;

        try {
            $notes = Note::where('user_id', $userId)->get();
        } catch (RecordsNotFoundException $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        $numberedNotes = [];
        foreach ($notes as $note) {
            $numberedNotes[$note->id] = $note;
        }

        $outputBuilder
            ->setData($numberedNotes);

        return $this;
    }
    
    protected function getRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        $id = $request->get('noteId');

        $note = auth()->user()->notes()->find($id);
        if ($note === null) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        $outputBuilder->setData($note);

        return $this;
    }

    protected function createRequest(
        Request $request,
        OutputBuilder $outputBuilder,
        ResourceValidator $validator,
    ): static
    {
        // validate the request body
        $validator->validate($request);

        if ($validator->errors() !== null) {
            $outputBuilder
                ->setStatus('invalid.arguments')
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setError($validator->errors());
            return $this;
        }

        try {
            $note = new Note;

            $note->title = $request->get('title');
            $note->content = $request->get('content');
            $note->user_id = auth()->user()->id;

            $note->save();

            $this->pushChangesToFile($note, auth()->user());

            $outputBuilder
                ->setCode(Response::HTTP_CREATED)
                ->setStatus('created')
                ->setData($note);
        } catch (\Throwable $e) {
            $outputBuilder
                ->setStatus('operation.failed')
                ->setCode(Response::HTTP_BAD_REQUEST);
        }

        return $this;
    }

    protected function updateRequest(
        Request $request,
        OutputBuilder $outputBuilder,
        ResourceValidator $validator,
    ): static
    {
        // validate the request body
        $validator->validate($request);

        if ($validator->errors() !== null) {
            $outputBuilder
                ->setStatus('invalid.arguments')
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setError($validator->errors());
            return $this;
        }

        $id = $request->get('noteId');

        $note = auth()->user()->notes()->find($id);
        if ($note === null) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        try {
            $note->title = $request->get('title');
            $note->content = $request->get('content');
            $note->save();

            $this->pushChangesToFile($note, auth()->user());

            $outputBuilder->setData($note);
        } catch (\Throwable $e) {
            $outputBuilder
                ->setStatus('operation.failed')
                ->setCode(Response::HTTP_BAD_REQUEST);
        }

        return $this;
    }

    protected function deleteRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        $id = $request->get('noteId');

        $note = auth()->user()->notes()->find($id);
        if ($note === null) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        try {
            $note->delete();
        } catch (\Throwable $e) {
            $outputBuilder
                ->setStatus('operation.failed')
                ->setCode(Response::HTTP_BAD_REQUEST);
        }

        return $this;
    }

    protected function pinRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        $id = $request->get('noteId');
        $note = auth()->user()->notes()->find($id);
        if ($note === null) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        try {
            $note->pinned = true;
            $note->save();
        } catch (\Throwable $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('operation.failed');
            return $this;
        }

        return $this;
    }

    protected function unpinRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        $id = $request->get('noteId');
        $note = auth()->user()->notes()->find($id);
        if ($note === null) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        try {
            $note->pinned = false;
            $note->save();
        } catch (\Throwable $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('operation.failed');
            return $this;
        }

        return $this;
    }

    protected function starRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        $id = $request->get('noteId');
        $note = auth()->user()->notes()->find($id);
        if ($note === null) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        try {
            $note->starred = true;
            $note->save();
        } catch (\Throwable $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('operation.failed');
            return $this;
        }

        return $this;
    }

    protected function unstarRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        $id = $request->get('noteId');
        $note = auth()->user()->notes()->find($id);
        if ($note === null) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        try {
            $note->starred = false;
            $note->save();
        } catch (\Throwable $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('operation.failed');
            return $this;
        }

        return $this;
    }

    private function pushChangesToFile(Note $note, User $user): void
    {
        $format = <<<'TEXT'
        **Author:** 
        
        %s

        **Date:** 
        
        %s
        
        **Title:** 
        
        %s

        **Content:**
        
        %s
        TEXT;

        $createdAt = new Carbon($note->created_at);

        file_put_contents(
            self::FILE_PATH . $note->getMarkdownFilename(),
            sprintf(
                $format, 
                $user->name, 
                $createdAt->format('l jS \\of F Y h:i:s A'),
                $note->title,
                $note->content,
            ),
        );

        $this->assistant->feedDocuments(self::FILE_PATH);
    }
}