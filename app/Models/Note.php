<?php
namespace App\Models;

use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final class Note
{
    public readonly string $id;

    public int $userId;

    public string $title = '';

    public string $content = '';

    public array $embeddings = [];

    public bool $pinned = false;

    public bool $starred = false;

    public array $tags = [];

    public int $createdAt = 0;

    public int $updatedAt = 0;

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
        $this->userId = (int) $row['user_id'];
        $this->title = (string) $row['title'];
        $this->content = (string) $row['content'];
        $this->pinned = (bool) $row['pinned'];
        $this->starred = (bool) $row['starred'];
        $this->tags = $row['tags'] !== '' ? explode(',', $row['tags']) : [];
        $this->createdAt = (int) $row['created_at'];
        $this->updatedAt = (int) $row['updated_at'];
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title,
            'content' => $this->content,
            'pinned' => $this->pinned,
            'starred' => $this->starred,
            'tags' => $this->tags,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function save(): static
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        if ($this->createdAt === 0) {
            $this->createdAt = $now->getTimestamp();
        }

        $this->updatedAt = $now->getTimestamp();

        $query = [
            'JSON.SET',
            $this->key(),
            '$',
            json_encode([
                'id' => $this->id,
                'user_id' => $this->userId,
                'embeddings' => $this->embeddings, // Store as binary
                'title' => $this->title,
                'content' => $this->content,
                'pinned' => $this->pinned,
                'starred' => $this->starred,
                'tags' => implode(',', $this->tags),
                'created_at' => $this->createdAt,
                'updated_at' => $this->updatedAt,
            ])
        ];

        Redis::executeRaw($query);

        // Redis::hmset($this->key(), [
        //     'id' => $this->id,
        //     'user_id' => $this->userId,
        //     'embeddings' => $this->embeddings, // Store as binary
        //     'title' => $this->title,
        //     'content' => $this->content,
        //     'pinned' => $this->pinned,
        //     'starred' => $this->starred,
        //     'tags' => implode(',', $this->tags),
        //     'created_at' => $this->createdAt,
        //     'updated_at' => $this->updatedAt,
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

    public function __toString()
    {
        return
            "Title: " . $this->title . "\n" .
            "Content: " . $this->content . "\n";
    }

    private function key(): string
    {
        $id = $this->id;
        $userId = $this->userId;
        return "note:$userId:$id";
    }
}