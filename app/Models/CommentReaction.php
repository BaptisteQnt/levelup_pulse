<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentReaction extends Model
{
    use HasFactory;

    public const LIKE = 1;
    public const DISLIKE = -1;

    protected $fillable = [
        'comment_id',
        'user_id',
        'reaction',
    ];

    protected $casts = [
        'reaction' => 'integer',
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
