<?php

namespace App\Models;

use Database\Factories\IotTelemetryMeasurementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IotTelemetryMeasurement extends Model
{
    /** @use HasFactory<IotTelemetryMeasurementFactory> */
    use HasFactory;

    protected $fillable = [
        'iot_device_id',
        'temperature',
        'humidity',
        'sequence',
        'measured_at',
        'received_at',
        'quarantine_reason',
    ];

    public const QUARANTINE_TEMPERATURE_JUMP = 'temperature_jump';

    public const QUARANTINE_HUMIDITY_JUMP = 'humidity_jump';

    public function isQuarantined(): bool
    {
        return $this->quarantine_reason !== null;
    }

    protected function casts(): array
    {
        return [
            'temperature' => 'decimal:2',
            'humidity' => 'decimal:2',
            'sequence' => 'integer',
            'measured_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'iot_device_id');
    }

    public function aiAnalysis(): HasOne
    {
        return $this->hasOne(IotTelemetryAiAnalysis::class, 'iot_telemetry_measurement_id');
    }
}
