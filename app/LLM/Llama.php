<?php
namespace App\LLM;

use Illuminate\Http\Request;

class Llama extends LlamaApi
{
    const MODEL = 'llama3.2';

    public function embed(string $input): mixed
    {
        $payload = [
            'model' => self::MODEL,
            'input' => $input,
        ];

        $response = $this->sendRequest('embed', [
            'payload'   => $payload,
            'method'    => Request::METHOD_POST,
        ]);

        return ['embeddings' => $response['embeddings']];
    }
}