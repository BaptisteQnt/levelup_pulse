<?php

namespace App\Models;

use Database\Factories\IotTelemetryMeasurementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

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
}
