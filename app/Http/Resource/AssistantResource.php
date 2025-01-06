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
        $token = $request->get('token');
        $question = trim($request->get('query'));

        $reminder = '(Reminder: Always check and reference your role description from the very first message containing the engineering prompt before responding. Ensure your responses strictly align with your role as a helpful assistant that provides factual, concise, and note-backed answers based solely on the provided notes. Find your answer from the reference only without including the examples)';

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

        $prompt = file_get_contents(__DIR__ . '/../../LLM/_assistant_role.txt');  
        $userId = auth()->user()->id;      

        if ($token === null) {
            $embeddings = pack('f*', ...$this->assistant->embed($question)['embeddings']);
           
            $notes = Note::searchWithEmbedding($userId, $embeddings);
        
            $convo[] = [
                'role' => 'user',
                'content' => "INPUT:\n" . $question . " " . $reminder . "\nREFERENCE:\n" . implode("\n", $notes) . "OUTPUT:",
            ];
        } else {
            // check if there is a need to fetch new reference
            $loadReference = false;
            do {
                $verifyResponse = $this->assistant->generate([
                    ...$convo,
                    [
                        'role' => 'user',
                        'content' => "INPUT:\nBased from our conversation, determine if this phrase is still relevant to any of our topics \"" . 
                        $question . "\", if yes answer with \"YES\" and if no answer with \"NO\" only, our conversation can jump from one topic to another so you have to take it into consideration.\nOUTPUT:"
                    ]
                ]);

                if (stripos($verifyResponse['message']['content'], 'NO') !== false) {
                    $loadReference = true;

                    $keywordResponse = $this->assistant->generate([
                        [
                            'role' => 'user',
                            'content' => "INPUT:\nGive concise 20 list of comma separated positive related topics based on this phrase \"" . 
                            $question . "\" without introduction.\nOUTPUT:"
                        ]
                    ]);

                    break;
                }

                $verifyResponse = $this->assistant->generate([
                    ...$convo,
                    [
                        'role' => 'user',
                        'content' => "INPUT:\nBased from our conversation, determine if this phrase is asking for you to re-check the current reference again or to check other reference \"" . 
                        $question . "\", if yes answer with \"YES\" and if no answer with \"NO\" only.\nOUTPUT:"
                    ]
                ]);

                if (stripos($verifyResponse['message']['content'], 'NO') !== false) {
                    $loadReference = true;

                    $keywordResponse = $this->assistant->generate([
                        ...$convo,
                        [
                            'role' => 'user',
                            'content' => "INPUT:\nGive concise 20 list of comma separated positive related topics based from our last topic with no introduction.\nOUTPUT:"
                        ]
                    ]);

                    break;
                }
            } while(false);

            $notes = null;
            if ($loadReference === true) {
                // get new embeddings for the keyword
                $toBeEmbedded = $keywordResponse['message']['content'];
                $embeddings = pack('f*', ...$this->assistant->embed($toBeEmbedded)['embeddings']);
                
                $notes = Note::searchWithEmbedding($userId, $embeddings);
                $noteCount = 1;
            }

            if ($notes === null) {
                $convo[] = [
                    'role' => 'user',
                    'content' => "FOLLOW UP INPUT:\n" . 
                    $question . ' ' . 
                    $reminder . 
                    "\nOUTPUT:",
                ];
            } else {
                $convo[] = [
                    'role' => 'user',
                    'content' => "FOLLOW UP INPUT:\n" . 
                    $question . 
                    " " . 
                    $reminder . "\nREFERENCE:\n" . 
                    implode("\n", $notes) . "\nOUTPUT:",
                ];
            }
           
        }

        $response = $this->assistant->generate([
            [
                'role' => 'user',
                'content' => $prompt,
            ],
            [
                'role' => 'assistant',
                'content' => "Understood! I will adhere to my role and ensure that my responses align strictly with the guidelines provided. I will stay in character, follow the rules set in the engineering prompt, and focus on delivering helpful, accurate, and context-appropriate responses at all times.",
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

    private function playRole2(
        Request $request, 
        OutputBuilder $outputBuilder
    ) {
        $token = $request->get('token');
        $question = trim($request->get('query'));
        $note = $request->get('note');

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

        $convo[] = [
            'role' => 'user',
            'content' => "INPUT:\n" . $question . " (Reminder: Always check and reference your role description from the very first message containing the engineering prompt before responding. Ensure your reply aligns with your role as a brainstorming partner, focusing on being clear, concise, collaborative, and supportive without overwhelming the user. And most importantly do not treat the example provided in the engineering prompt as the actual topic of discussion. The example is only a guide for tone, structure, and approach. Focus solely on the user’s current message and their specific topic of interest.)" . "\nSHARED NOTE:\n" . $note . "OUTPUT:",
        ];

        $prompt = file_get_contents(__DIR__ . '/../../LLM/_partner_role.txt');  
        $userId = auth()->user()->id;

        $response = $this->assistant->generate([
            [
                'role' => 'user',
                'content' => $prompt,
            ],
            [
                'role' => 'assistant',
                'content' => "Understood! I will adhere to my role and ensure that my responses align strictly with the guidelines provided. I will stay in character, follow the rules set in the engineering prompt, and focus on delivering helpful, accurate, and context-appropriate responses at all times.",
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