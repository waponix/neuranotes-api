<?php
namespace App\Http\Resource\Validator;

use App\Http\Api\BasicResourceValidator;

final class ChatValidator extends BasicResourceValidator
{
    public function rules(): array
    {
        return [
            'message' => 'required|string',
        ];
    }
}