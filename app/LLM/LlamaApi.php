<?php
namespace App\LLM;

abstract class LlamaApi
{
    const URL = 'ollama:11434/api/';

    protected function sendRequest(string $name, array $parameters): mixed
    {
        $handle = curl_init();

        curl_setopt_array(
            $handle,
            [
                CURLOPT_HTTPHEADER      => [
                    'Content-Type: application/json',
                ],
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_SSL_VERIFYHOST  => false,
                CURLOPT_URL             => self::URL . $name,
                CURLOPT_CUSTOMREQUEST   => $parameters['method'],
                CURLOPT_POSTFIELDS      => json_encode($parameters['payload']),
            ]
        );

        $response = json_decode(curl_exec($handle), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid response format');
        }

        $error = curl_errno($handle);
        if ($error !== CURLE_OK) {
            throw new \Exception('Communication Error: ' . curl_strerror($error));
        }

        curl_close($handle);

        return $response;
    }
}