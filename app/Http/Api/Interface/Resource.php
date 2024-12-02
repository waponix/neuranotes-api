<?php
namespace App\Http\Api\Interface;

interface Resource
{
    public function useValidator(string $validatorClassName): Resource;
}