<?php
namespace App\Http\Resource;

use App\Http\Api\BasicResource;
use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\ResourceValidator;
use App\LLM\Llama;
use Illuminate\Http\Request;

final class ChatResource extends BasicResource
{
    public function __construct(
        private readonly Llama $llama,
        OutputBuilder $outputBuilder, 
        ResourceValidator ...$resourceValidators,
    )
    {   
        parent::__construct($outputBuilder, ...$resourceValidators);
    }

    public function chatRequest(
        Request $request, 
        OutputBuilder $outputBuilder,
        ResourceValidator $validator,
    )
    {
        $validator->validate($request);

        if ($validator->errors() !== null) {
            $outputBuilder
                ->setStatus('invalid.arguments')
                ->setCode(400)
                ->setError($validator->errors());
            return $this;
        }

        $embeddings = $this->llama->embed($request->get('message'));
        
        $outputBuilder
            ->setData($embeddings)
            ->setCode(201);
        return $this;
    }
}