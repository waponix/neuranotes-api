<?php
namespace App\Http\Resource\Validator;

use App\Http\Api\BasicResourceValidator;

final class CreateNoteValidator extends BasicResourceValidator
{
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'content' => 'required|string',
        ];
    }
}