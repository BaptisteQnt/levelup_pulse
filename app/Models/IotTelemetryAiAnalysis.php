<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotTelemetryAiAnalysis extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /** Pseudo-state exposed by the API when no analysis row exists (never stored). */
    public const STATE_NOT_REQUIRED = 'not_required';

    public const TRIGGER_PERIODIC = 'periodic';

    public const TRIGGER_THRESHOLD = 'threshold';

    public const TRIGGER_SPIKE = 'spike';

    public const TRIGGER_ANOMALY = 'anomaly';

    public const RELIABILITY_RELIABLE = 'reliable';

    public const RELIABILITY_SUSPICIOUS = 'suspicious';

    public const RELIABILITY_UNKNOWN = 'unknown';

    public const RISK_LOW = 'low';

    public const RISK_MODERATE = 'moderate';

    public const RISK_HIGH = 'high';

    public const RISK_CRITICAL = 'critical';

    public const RISK_UNKNOWN = 'unknown';

    protected $fillable = [
        'iot_telemetry_measurement_id',
        'status',
        'trigger_reason',
        'reliability',
        'approved_for_use',
        'risk_level',
        'risk_summary',
        'reliability_reason',
        'recommendations',
        'disclaimer',
        'model',
        'error_code',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_for_use' => 'boolean',
            'recommendations' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(IotTelemetryMeasurement::class, 'iot_telemetry_measurement_id');
    }
}
