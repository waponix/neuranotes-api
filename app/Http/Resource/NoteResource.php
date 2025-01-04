<?php
namespace App\Http\Resource;

use App\Http\Api\BasicResource;
use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\ResourceValidator;
use App\LLM\Assistant;
use App\Models\Note;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class NoteResource extends BasicResource
{
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
        $count = 0;

        try {
            $notes = Note::getByUserId($userId, $count);
        } catch (RecordsNotFoundException $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        $notes = array_map(fn ($note) => $note->serialize(), $notes);

        $outputBuilder
            ->addMeta('count', $count)
            ->setData($notes);

        return $this;
    }

    protected function getRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        $id = $request->get('noteId');
        $userId = auth()->user()->id;

        try {
            $note = new Note("$userId:$id");
        } catch (RecordsNotFoundException $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        $outputBuilder->setData($note->serialize());

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

        $note = new Note();
        $note->title = $title = trim($request->get('title'));
        $note->content = $content = trim($request->get('content'));
        
        $content = "Title: [$title]\nContent: [$content]";
        $embeddings = $this->assistant->embed($content)['embeddings'];
        $note->embeddings = $embeddings;
        
        $note->user_id = auth()->user()->id;

        try {
            $note->save();

            $outputBuilder
                ->setCode(Response::HTTP_CREATED)
                ->setStatus('created')
                ->setData($note->serialize());
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

        $userId = auth()->user()->id;
        $noteId = $request->get('noteId');

        try {
            $note = new Note("$userId:$noteId");
        } catch (RecordsNotFoundException $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('record.not.found');
            return $this;
        }

        $note->title = $title = trim($request->get('title'));
        $note->content = $content = trim($request->get('content'));

        $content = "Title: $title\nContent: $content";
        $embeddings = $this->assistant->embed($content)['embeddings'];

        $note->embeddings = $embeddings;

        try {
            $note->save();

            $outputBuilder->setData($note->serialize());
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
        $userId = auth()->user()->id;
        $noteId = $request->get('noteId');

        try {
            $note = new Note("$userId:$noteId");
        } catch (RecordsNotFoundException $e) {
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
        $noteId = $request->get('noteId');
        $userId = auth()->user()->id;

        try {
            $note = new Note("$userId:$noteId");
            $note->pin();
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
        $noteId = $request->get('noteId');
        $userId = auth()->user()->id;

        try {
            $note = new Note("$userId:$noteId");
            $note->unpin();
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
        $noteId = $request->get('noteId');
        $userId = auth()->user()->id;

        try {
            $note = new Note("$userId:$noteId");
            $note->star();
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
        $noteId = $request->get('noteId');
        $userId = auth()->user()->id;

        try {
            $note = new Note("$userId:$noteId");
            $note->unstar();
        } catch (\Throwable $e) {
            $outputBuilder
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setStatus('operation.failed');
            return $this;
        }

        return $this;
    }
}