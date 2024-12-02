<?php
namespace App\Http\Api;

use App\Http\Api\Interface\OutputBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BasicOutputBuilder implements OutputBuilder
{
    private array $meta = [];
    private mixed $data = null;
    private mixed $error = null;
    private int $code = Response::HTTP_OK;
    private string $status = 'ok';
    private array $headers = [];

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

    public function build(): JsonResponse
    {
        $content = [
            'status' => $this->status,
            'meta' => $this->meta,
            'error' => $this->error,
            'data' => $this->data,
        ];

        return response()->json($content, $this->code, $this->headers);
    }
}