<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompatibilityScan extends Model
{
    public const STATUS_CREATED = 'created';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_RESEARCHING = 'researching';

    public const STATUS_ANALYZING = 'analyzing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'uuid',
        'user_id',
        'game_id',
        'token_hash',
        'status',
        'hardware_payload',
        'requirements_payload',
        'result_payload',
        'error_code',
        'uploaded_at',
        'researched_at',
        'completed_at',
        'upload_expires_at',
        'purge_at',
    ];

    protected $hidden = [
        'token_hash',
        'hardware_payload',
        'requirements_payload',
        'result_payload',
    ];

    protected function casts(): array
    {
        return [
            'hardware_payload' => 'encrypted:array',
            'requirements_payload' => 'encrypted:array',
            'result_payload' => 'encrypted:array',
            'uploaded_at' => 'datetime',
            'researched_at' => 'datetime',
            'completed_at' => 'datetime',
            'upload_expires_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
