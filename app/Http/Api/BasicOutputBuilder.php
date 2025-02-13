<?php
namespace App\Http\Api;

use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\StreamCallback;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BasicOutputBuilder implements OutputBuilder
{
    private array $meta = [];
    private mixed $data = null;
    private mixed $error = null;
    private int $code = Response::HTTP_OK;
    private string $status = 'ok';
    private array $headers = [];
    private bool $isStream = false;
    private $streamCallback;

    public function setData(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function addMeta(string $name, mixed $value): static
    {
        $this->meta[$name] = $value;
        return $this;
    }

    public function setError(mixed $error): static
    {
        $this->error = $error;
        return $this;
    }

    public function setCode(int $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function stream(bool $flag): static
    {
        $this->isStream = $flag;
        return $this;
    }

    public function streamCallback(StreamCallback $callback): static
    {
        $this->streamCallback = $callback;
        return $this;
    }

    public function build(): Response
    {
        $content = [
            'status' => $this->status,
            'meta' => $this->meta,
            'error' => $this->error,
            'data' => $this->data,
        ];

        if ($this->isStream === true) {
            $content['data'] = $this->streamCallback->callback($this);
            return response()->streamJson($content);
        }

        return response()->json($content, $this->code, $this->headers);
    }
}