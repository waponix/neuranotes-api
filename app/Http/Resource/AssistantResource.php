<?php
namespace App\Http\Resource;

use App\Http\Api\BasicResource;
use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\ResourceValidator;
use App\LLM\Assistant;
use App\Models\Note;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

final class AssistantResource extends BasicResource
{
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
        $token = $request->get('token');
        $question = trim($request->get('query'));

        $convo = [];
        if ($token !== null) {
            try {
                $token = JWT::decode($token, new Key(self::SECRET . ':' . auth()->user()->id, self::ALGO));
                $convo = json_decode(json_encode($token->convo), true);
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

        $embeddings = pack('f*', ...$this->assistant->embed($question)['embeddings']);

        $userId = auth()->user()->id;

        $query = [
            'FT.SEARCH',
            'idx:notes',
            '(@user_id:[$userId $userId])=>[KNN $topK @vector $query]',
            'SORTBY', '__vector_score', "ASC",
            'PARAMS', 6, 'userId', $userId, 'query', $embeddings, 'topK', 5,
            'RETURN', 3, '$.title', '$.content', '$.created_at',
            'DIALECT', 2,
        ];

        $response = Redis::executeRaw($query);

        $prompt = file_get_contents(__DIR__ . '/../../LLM/assistant_role.txt');        

        if ($token === null) {
            $notes = [];
            $count = array_shift($response);

            while (count($response) > 0) {
                $key = array_shift($response);
                list(,,$id) = explode(':', $key);
                $notes[$key] = new Note($id, false);
                $fields = array_shift($response);

                while (count($fields) > 0) {
                    $notes[$key]->{str_replace('$.', '', array_shift($fields))} = array_shift($fields);
                }
            }
            $noteCount = 1;
            $notes = array_reduce(array_values($notes), function ($carry, $item) use (&$noteCount) {
                $carry .= '### START OF NOTE ' . $noteCount . " ###\n" . $item . "\n### END OF NOTE " . $noteCount . " ###\n";
                $noteCount ++;
                return $carry;
            }, '');
        
            $convo[] = [
                'role' => 'user',
                'content' => "INPUT:\n" . $question . "\nREFERENCE:\n" . $notes . "OUTPUT:",
            ];
        } else {
            $convo[] = [
                'role' => 'user',
                'content' => "FOLLOW UP INPUT:\n" . $question . " (Reminder: please find your answer from the reference only)\nOUTPUT:",
            ];
        }

        $response = $this->assistant->generate([
            [
                'role' => 'user',
                'content' => $prompt,
            ],
            [
                'role' => 'assistant',
                'content' => "Sure, I'd be happy to help! Please provide the input question you would like me to answer.",
            ],
            ...$convo,
        ]);

        $convo[] = $response['message'];

        $token = JWT::encode(['convo' => $convo], self::SECRET . ':' . auth()->user()->id, self::ALGO);

        $outputBuilder->setData([
            'answer' => $response['message']['content'],
            'token' => $token,
        ]);

        return $this;
    }
}