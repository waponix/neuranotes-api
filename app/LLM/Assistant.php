<?php
namespace App\LLM;

use Illuminate\Http\Request;

class Assistant extends AssistantApi
{
    const LLM_MODEL = 'llama3.2';
    const EMBEDDING_MODEL = 'nomic-embed-text';

    public function generate(array $input): mixed
    {
        $payload = [
            'model' => self::LLM_MODEL,
            'messages' => $input,
            'stream' => false,
        ];

        $response = $this->sendRequest('chat', [
            'payload' => $payload,
            'method' => Request::METHOD_POST,
        ]);

        return $response;
    }

    public function embed(string $input): mixed
    {
        $payload = [
            'model' => self::EMBEDDING_MODEL,
            'prompt' => $input,
        ];

        $response = $this->sendRequest('embeddings', [
            'payload'   => $payload,
            'method'    => Request::METHOD_POST,
        ]);

        if (isset($response['embedding'])) {
            return ['embeddings' => $response['embedding']];
        }

        return ['embeddings' => []];
    }
}