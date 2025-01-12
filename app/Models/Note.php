<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Note extends Model
{
    use HasFactory;
    use SerializeTrait;

    /**
     *
     * @var string
     */
    protected $table = 'notes';

    /**
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'title',
        'content',
        'user_id',
        'pinned',
        'starred',
    ];

    /**
     * 
     * @var array
     */
    protected $private = [
        'user_id',
    ];


    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            if (empty($note->uuid)) {
                $note->uuid = str_replace('-', '', ((string) Str::uuid()));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMarkdownFilename(): string
    {
        return "{$this->user_id}.{$this->uuid}.md";
    }

    // Define a scope for the full-text search
    public function scopeSearch($query, $searchTerm)
    {
        return $query->whereRaw("MATCH(title, content) AGAINST (? IN NATURAL LANGUAGE MODE)", [$searchTerm]);
    }
}
