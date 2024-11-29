<?php
namespace App\Http\Api;

use App\Http\Api\Interface\ResourceValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class BasicResourceValidator implements ResourceValidator
{
    private null|array $errors = null;

    public function validate(Request $request): static
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            $this->errors = $validator->errors()->getMessages();
        }

        return $this;
    }

    public function errors(): null|array
    {
        return $this->errors;
    }

    abstract public function rules(): array;
}