<?php
namespace App\Models;

use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final class Note
{
    public readonly string $id;

    public int $user_id;

    public string $title = '';

    public string $content = '';

    public array $embeddings = [];

    public bool $pinned = false;

    public bool $starred = false;

    public array $tags = [];

    public int $created_at = 0;

    public int $updated_at = 0;

    public function __construct(?string $id = null, bool $load = true)
    {
        if ($id === null) {
            $this->id = str_replace('-', '', Str::uuid()->toString());
            return;
        }

        if ($load === false) {
            $this->id = $id;
            return;
        }

        $query = ['JSON.GET', "note:$id"];

        $row = json_decode(Redis::executeRaw($query), true);

        if (empty($row)) {
            throw new RecordsNotFoundException("The note with key $id is not found");
        }

        $this->id = (string) $row['id'];
        $this->user_id = (int) $row['user_id'];
        $this->title = (string) $row['title'];
        $this->content = (string) $row['content'];
        $this->pinned = (bool) $row['pinned'];
        $this->starred = (bool) $row['starred'];
        $this->tags = $row['tags'] !== '' ? explode(',', $row['tags']) : [];
        $this->created_at = (int) $row['created_at'];
        $this->updated_at = (int) $row['updated_at'];
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'content' => $this->content,
            'pinned' => $this->pinned,
            'starred' => $this->starred,
            'tags' => $this->tags,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function save(): static
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        if ($this->created_at === 0) {
            $this->created_at = $now->getTimestamp();
        }

        $this->updated_at = $now->getTimestamp();

        $query = [
            'JSON.SET',
            $this->key(),
            '$',
            json_encode([
                'id' => $this->id,
                'user_id' => $this->user_id,
                'embeddings' => $this->embeddings, // Store as binary
                'title' => $this->title,
                'content' => $this->content,
                'pinned' => $this->pinned,
                'starred' => $this->starred,
                'tags' => implode(',', $this->tags),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ])
        ];

        Redis::executeRaw($query);

        // Redis::hmset($this->key(), [
        //     'id' => $this->id,
        //     'user_id' => $this->user_id,
        //     'embeddings' => $this->embeddings, // Store as binary
        //     'title' => $this->title,
        //     'content' => $this->content,
        //     'pinned' => $this->pinned,
        //     'starred' => $this->starred,
        //     'tags' => implode(',', $this->tags),
        //     'created_at' => $this->created_at,
        //     'updated_at' => $this->updated_at,
        // ]);

        return $this;
    }

    public function delete(): static
    {
        Redis::del($this->key());
        return $this;
    }

    public function pin(): static
    {
        $this->pinned = true;
        Redis::hset($this->key(), 'pinned', $this->pinned);
        return $this;
    }

    public function unpin(): static
    {
        $this->pinned = false;
        Redis::hset($this->key(), 'pinned', $this->pinned);
        return $this;
    }

    public function star(): static
    {
        $this->starred = true;
        Redis::hset($this->key(), 'starred', $this->starred);
        return $this;
    }

    public function unstar(): static
    {
        $this->starred = false;
        Redis::hset($this->key(), 'starred', $this->starred);
        return $this;
    }

    public static function getByUserId(int $userId, int &$count): array
    {
        $query = [
            'FT.SEARCH',
            'idx:notes',
            '(@user_id:[$userId $userId])',
            'PARAMS', 4, 'userId', $userId, 'topK', 5,
            'RETURN', 7, '$.title', '$.content', '$.created_at', '$.updated_at', '$.pinned', '$.starred', '$.user_id',
            'DIALECT', 2,
        ];

        $response = Redis::executeRaw($query);
        $count = (integer) array_shift($response);

        $notes = [];

        while (count($response) > 0) {
            $key = array_shift($response);
            list(,,$id) = explode(':', $key);
            $notes[$key] = new self($id, false);
            $fields = array_shift($response);

            while (count($fields) > 0) {
                $notes[$key]->{str_replace('$.', '', array_shift($fields))} = array_shift($fields);
            }
        }

        return $notes;
    }

    public static function searchWithEmbedding($userId, $embeddings)
    {
        $query = [
            'FT.SEARCH',
            'idx:notes',
            '(@user_id:[$userId $userId])=>[KNN $topK @vector $query]',
            'SORTBY', '__vector_score', "ASC",
            'PARAMS', 6, 'userId', $userId, 'query', $embeddings, 'topK', 3,
            'RETURN', 3, '$.title', '$.content', '$.created_at',
            'DIALECT', 2,
        ];

        $response = Redis::executeRaw($query);
        $count = array_shift($response);

        $notes = [];

        while (count($response) > 0) {
            $key = array_shift($response);
            list(,,$id) = explode(':', $key);
            $notes[$key] = new self($id, false);
            $fields = array_shift($response);

            while (count($fields) > 0) {
                $notes[$key]->{str_replace('$.', '', array_shift($fields))} = array_shift($fields);
            }
        }

        return $notes;
    }

    public function __toString()
    {
        $createdAt = new \DateTime;
        $createdAt->setTimestamp($this->created_at);
        return "### START OF NOTE " . $this->id . " ###\n[Title: " . $this->title . ", Date: " . $createdAt->format('Y/m/d H:i:s') . "]\n[Content: " . $this->content . "]\n### END OF NOTE " . $this->id . " ###";
    }

    private function key(): string
    {
        $id = $this->id;
        $user_id = $this->user_id;
        return "note:$user_id:$id";
    }
}