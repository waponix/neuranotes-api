<?php
namespace App\Http\Api\Interface;

use Illuminate\Http\Request;

interface ResourceValidator
{
    public function validate(Request $request): ResourceValidator;
    
    public function errors(): null|array;

    public function rules(): array;
}