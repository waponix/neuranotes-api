<?php
namespace App\Http\Resource\Validator\Auth;

use App\Http\Api\BasicResourceValidator;

class LoginValidator extends BasicResourceValidator
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }
}

