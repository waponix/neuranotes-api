<?php
namespace App\Http\Api;

use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\Resource;
use App\Http\Api\Interface\ResourceValidator;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

abstract class BasicResource implements Resource
{
    const HANDLER_PREFIX = 'handle';

    private array $validators;
    private string $validatorId = '';

    public function __construct(
        private readonly OutputBuilder $outputBuilder, 
        ResourceValidator ...$resourceValidators,
    )
    {
        foreach ($resourceValidators as $resourceValidator) {
            $this->validators[$resourceValidator::class] = $resourceValidator;
        }
    }

    public function __call(string $name, array $args): Response {
        if (stripos($name, self::HANDLER_PREFIX) !== false) {
                $name = lcfirst(str_replace(self::HANDLER_PREFIX, '', $name));

                list($request) = $args;
                $validator = $this->validator();

                $handlerArgs = [
                    $request, 
                    $this->outputBuilder,
                ];

                if ($validator instanceof ResourceValidator) {
                    $handlerArgs[] = $validator;
                }

                return $this
                    ->{$name}(...$handlerArgs)
                    ->outputBuilder
                    ->build();
        }

       return $this->{$name}(...$args);
    }

    public function useValidator(string $validatorClassName): static
    {
        $this->validatorId = $validatorClassName;
        return $this;
    }

    protected function validator(): ?ResourceValidator
    {
        return $this->validators[$this->validatorId] ?? null;
    }

    protected function getLoggedInUser(): User
    {
        return auth()->user();
    }
}