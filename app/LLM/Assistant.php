<?php
namespace App\LLM;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingFormatter\EmbeddingFormatter;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Redis\RedisVectorStore;
use LLPhant\OllamaConfig;
use LLPhant\Query\SemanticSearch\LLMReranker;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use LLPhant\Query\SemanticSearch\SiblingsDocumentTransformer;
use Predis\Client;

class Assistant extends AssistantApi
{
    const LLM_MODEL = 'qwen2.5';
    const EMBEDDING_MODEL = 'nomic-embed-text';

    private OllamaConfig $ollamaConfig;

    private RedisVectorStore $vectorStore;

    public function __construct(Client $client)
    {
        $this->ollamaConfig = new OllamaConfig;
        $this->ollamaConfig->model = self::LLM_MODEL;
        $this->ollamaConfig->url = 'http://ollama:11434/api/';

        $this->vectorStore = new RedisVectorStore(
            new Client([
                'scheme'    => 'tcp',
                'host'      => 'db_redis',
                'port'      => '6379',
                'username'  => 'radmin',
                'password'  => 'redisAdmin123',
            ]),
            'app_idx_notes'
        );
    }

    public function feedDocuments(string $path): static
    {
        // TODO: temporary solution when refreshing the vectore store
        Redis::executeRaw(['FLUSHDB']);

        // load the documents from the given $path
        $reader = new FileDataReader($path, Document::class, ['md']);
        $documents = $reader->getDocuments();
        // split the documents into managable chunks for the embeddings
        $splitDocuments = DocumentSplitter::splitDocuments($documents, 1024, wordOverlap: 10);
        // format the splitted documents to provide important information for the LLM to understand the relation of the splitted document
        $formattedDocuments = EmbeddingFormatter::formatEmbeddings($splitDocuments);
        // create embeddings for the documents
        $embeddingGenerator = new OllamaEmbeddingGenerator($this->ollamaConfig);
        $embeddedDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);
        // store the embeddings to the vector storage
        $this->vectorStore->addDocuments($embeddedDocuments);
        return $this;
    }

    public function qa(): QuestionAnswering
    {
        // $reranker = new Reranker(new OllamaChat($this->ollamaConfig), 10);
        $qa = new QuestionAnswering(
            $this->vectorStore,
            new OllamaEmbeddingGenerator($this->ollamaConfig),
            $this->chat(),
            // retrievedDocumentsTransformer: $reranker,
        );

        $qa->systemMessageTemplate = "Use the following pieces of context in my notes to answer my question. If you don't know the answer, just say that you don't know, don't try to make up an answer.\n\n{context}.";

        return $qa;
    }

    public function chat(): OllamaChat
    {
        return new OllamaChat($this->ollamaConfig);;
    }

    public function generate(string $input): mixed
    {
        $payload = [
            'model' => self::LLM_MODEL,
            'prompt' => $input,
            'stream' => false,
        ];

        $response = $this->sendRequest('generate', [
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