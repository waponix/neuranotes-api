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

        $embeddings = pack('f*', ...$this->assistant->embed($question)['embeddings']);

        $userId = auth()->user()->id;

        $query = [
            'FT.SEARCH',
            'idx:notes',
            '(@user_id:[$userId $userId])=>[KNN $topK @vector $query]',
            'SORTBY', '__vector_score', "ASC",
            'PARAMS', 6, 'userId', $userId, 'query', $embeddings, 'topK', 10,
            'RETURN', 2, '$.title', '$.content',
            'DIALECT', 2,
        ];

        $response = Redis::executeRaw($query);

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

        $prompt = file_get_contents(__DIR__ . '/../../LLM/assistant_role.txt');
        // $prompt = str_replace('<NOTES>', implode("\n", array_values($notes)), $prompt);

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
            }
        }

        $convo[] = [
            'role' => 'user',
            'content' => "Here is the question:\n[" . $question . "]\n\nSource notes:\n[" . implode("\n", array_values($notes)) . "]"
        ];

        $response = $this->assistant->generate([
            [
                'role' => 'assistant',
                'content' => $prompt
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