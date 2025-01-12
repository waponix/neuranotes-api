<?php
namespace App\Http\Resource;

use App\Http\Api\BasicResource;
use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\ResourceValidator;
use App\LLM\Assistant;
use App\LLM\AssistantTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use LLPhant\Chat\Message;
use Symfony\Component\HttpFoundation\Response;

final class AssistantResource extends BasicResource
{
    use AssistantTrait;

    const SECRET = '6a87b2faeef3401f8590215b30ddfa40';
    const ALGO = 'HS256';
    
    public function __construct(
        private readonly Assistant $assistant,
        OutputBuilder $outputBuilder,
        ResourceValidator ...$resourceValidators,
    )
    {
        parent::__construct($outputBuilder, ...$resourceValidators);
    }

    public function generalNotesQueryRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        // TODO: implement input validation
        $role = (integer) $request->get('role');

        switch ($role) {
            case 2: $this->playRole2($request, $outputBuilder); break;
            case 1: 
            default: $this->playRole1($request, $outputBuilder);
        }

        return $this;
    }

    private function playRole1(
        Request $request, 
        OutputBuilder $outputBuilder
    ) {
        // $this->assistant->feedDocuments(__DIR__ . '/../../../documents/notes/user_1');
        $token = $request->get('token');
        $query = trim($request->get('query'));

        $convo = [];
        if ($token !== null) {
            try {
                $token = JWT::decode($token, new Key(self::SECRET . ':' . auth()->user()->id, self::ALGO));
                $convo = json_decode(json_encode($token->convo), true);

                $convo = array_map(function ($message) {
                    return match ($message['role']) {
                        'user'      => Message::user($message['content']),
                        'assistant' => Message::assistant($message['content']),
                    };
                }, $convo);
            } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
                $outputBuilder
                    ->setStatus('invalid.arguments')
                    ->setCode(Response::HTTP_BAD_REQUEST)
                    ->setError([
                        'token' => 'invalid token'
                    ]);

                return $this;
            }
        }

        $body = $this->assistant->qa()->answerQuestionFromChat([
            Message::system($this->getSystemPromptForClara()),
            ...$this->getTrainingForClara(auth()->user()),
            ...$convo,
            Message::user($query),
        ], 10);

        $answer = '';
        // Process the stream in chunks
        while (!$body->eof()) {
            $answer .= $body->read(1024); // Read 1KB at a time
        }

        $convo[] = Message::user($query);
        $convo[] = Message::assistant($answer);

        $token = JWT::encode(['convo' => $convo], self::SECRET . ':' . auth()->user()->id, self::ALGO);

        // $this->assistant->chat()->setSystemMessage($systemPrompt);
        // $answer = $this->assistant->qa()->answerQuestion($query);

        $outputBuilder
            ->setData([
                'answer' => $answer,
                'token' => $token,
            ]);

        return $this;
    }

    private function playRole2(
        Request $request, 
        OutputBuilder $outputBuilder
    ) {
        $token = $request->get('token');
        $query = trim($request->get('query'));

        $convo = [];
        if ($token !== null) {
            try {
                $token = JWT::decode($token, new Key(self::SECRET . ':' . auth()->user()->id, self::ALGO));
                $convo = json_decode(json_encode($token->convo), true);

                $convo = array_map(function ($message) {
                    return match ($message['role']) {
                        'user'      => Message::user($message['content']),
                        'assistant' => Message::assistant($message['content']),
                    };
                }, $convo);
            } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
                $outputBuilder
                    ->setStatus('invalid.arguments')
                    ->setCode(Response::HTTP_BAD_REQUEST)
                    ->setError([
                        'token' => 'invalid token'
                    ]);

                return $this;
            }
        }

        $answer = $this->assistant->chat()->generateChat([
            Message::system($this->getSystemPromptForDan()),
            ...$this->getTrainingForDan(auth()->user()),
            ...$convo,
            Message::user($query),
        ]);

        $convo[] = Message::user($query);
        $convo[] = Message::assistant($answer);

        $token = JWT::encode(['convo' => $convo], self::SECRET . ':' . auth()->user()->id, self::ALGO);

        $outputBuilder->setData([
            'answer' => $answer,
            'token' => $token,
        ]);
        return $this;
    }
}