<?php
namespace App\Http\Api;

use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\StreamCallback;
use Firebase\JWT\JWT;
use Generator;
use Psr\Http\Message\StreamInterface;
use LLPhant\Chat\Message;

class LLphantStreamCallback implements StreamCallback
{
    public function __construct(
        private readonly StreamInterface $source,
        private array $convo,
        private readonly string $secret,
        private readonly string $algo,
    )
    {

    }

    public function callback(): Generator
    {
        $source = $this->source;
        $convo = $this->convo;

        while (!$source->eof()) {
            
            // $convo = [
            //     ...$convo,
            //     Message::assistant($answer),
            // ];

            // $token = JWT::encode(['convo' => $convo], $this->secret, $this->algo);

            yield $source->read(1024);
        }
    }
}