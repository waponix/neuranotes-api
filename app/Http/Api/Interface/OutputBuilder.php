<?php
namespace App\Http\Api\Interface;

use Symfony\Component\HttpFoundation\JsonResponse;

interface OutputBuilder
{
    public function setCode(int $code): OutputBuilder;

    public function setStatus(string $status): OutputBuilder;

    public function setData(mixed $data): OutputBuilder;

    public function addMeta(string $name, mixed $value): OutputBuilder;
    
    public function setError(mixed $error): OutputBuilder;

    public function addHeader(string $name, string $value): OutputBuilder;

    public function build(): JsonResponse;
}