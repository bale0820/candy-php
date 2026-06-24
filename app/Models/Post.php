<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'image_path',
        'attachments',
        'category',
        'tags',
        'author',
        'replies',
        'views',
    ];

    protected $casts = [
        'tags' => 'array',
        'attachments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
