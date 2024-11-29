<?php
namespace App\Http\Resource\Validator\Auth;

use App\Http\Api\BasicResourceValidator;

class RegisterValidator extends BasicResourceValidator
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|string|min:6|same:password',
        ];
    }
}