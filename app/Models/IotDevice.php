<?php

namespace App\Models;

use Database\Factories\IotDeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IotDevice extends Model
{
    /** @use HasFactory<IotDeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'token_hash',
        'is_active',
        'last_sequence',
        'last_seen_at',
        'token_rotated_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sequence' => 'integer',
            'last_seen_at' => 'datetime',
            'token_rotated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telemetryMeasurements(): HasMany
    {
        return $this->hasMany(IotTelemetryMeasurement::class);
    }
}
