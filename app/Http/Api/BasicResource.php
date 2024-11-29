<?php
namespace App\Http\Api;

use App\Http\Api\Interface\Resource;
use App\Http\Api\Interface\ResourceValidator;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class BasicResource implements Resource
{
    const HANDLER_PREFIX = 'handle';

    private array $validators;
    private string $validatorId = '';
    private JsonResponse $jsonResponse;

    public function __construct(ResourceValidator ...$resourceValidators)
    {
        foreach ($resourceValidators as $resourceValidator) {
            $this->validators[$resourceValidator::class] = $resourceValidator;
        }
    }

    public function __call(string $name, array $args): JsonResponse {
        if (stripos($name, self::HANDLER_PREFIX) !== false) {
                $name = lcfirst(str_replace(self::HANDLER_PREFIX, '', $name));

                list($request) = $args;
                $validator = $this->validator();

                $handlerArgs = [$request];
                if ($validator instanceof ResourceValidator) {
                    $handlerArgs[] = $validator;
                }

                return $this
                    ->{$name}(...$handlerArgs)
                    ->output($this->jsonResponse);
        }

       return $this->{$name}(...$args);
    }

    public function useValidator(string $validatorClassName): static
    {
        $this->validatorId = $validatorClassName;
        return $this;
    }

    protected function setResponse(array|object $data, int $statusCode = 200): static
    {
        $this->jsonResponse = response()->json($data, $statusCode);
        return $this;
    }

    public function output(JsonResponse $jsonResponse): JsonResponse
    {    
        return $jsonResponse;
    }

    protected function validator(): ?ResourceValidator
    {
        return $this->validators[$this->validatorId] ?? null;
    }
}