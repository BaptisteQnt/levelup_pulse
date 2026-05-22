<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipReaction extends Model
{
    use HasFactory;

    public const LIKE = 1;
    public const DISLIKE = -1;

    protected $fillable = [
        'tip_id',
        'user_id',
        'reaction',
    ];

    protected $casts = [
        'reaction' => 'integer',
    ];

    public function tip()
    {
        return $this->belongsTo(Tip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
