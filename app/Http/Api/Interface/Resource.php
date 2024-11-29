<?php
namespace App\Http\Api\Interface;

use Symfony\Component\HttpFoundation\JsonResponse;

interface Resource
{
    public function useValidator(string $validatorClassName): Resource;

    public function output(JsonResponse $jsonResponse): JsonResponse;
}