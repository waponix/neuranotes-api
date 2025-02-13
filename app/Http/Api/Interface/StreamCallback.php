<?php
namespace App\Http\Api\Interface;

use Generator;

interface StreamCallback
{
    public function callback(): Generator;
}