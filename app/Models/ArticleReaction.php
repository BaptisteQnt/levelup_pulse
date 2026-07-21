<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleReaction extends Model
{
    use HasFactory;

    public const LIKE = 1;
    public const DISLIKE = -1;

    protected $fillable = [
        'article_id',
        'user_id',
        'reaction',
    ];

    protected $casts = [
        'reaction' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
