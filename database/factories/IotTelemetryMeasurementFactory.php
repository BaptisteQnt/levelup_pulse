<?php

namespace Database\Factories;

use App\Models\IotDevice;
use App\Models\IotTelemetryMeasurement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IotTelemetryMeasurement>
 */
class IotTelemetryMeasurementFactory extends Factory
{
    protected $model = IotTelemetryMeasurement::class;

    public function definition(): array
    {
        return [
            'iot_device_id' => IotDevice::factory(),
            'temperature' => $this->faker->randomFloat(2, -20, 50),
            'humidity' => $this->faker->randomFloat(2, 0, 100),
            'sequence' => $this->faker->unique()->numberBetween(0, 1_000_000),
            'measured_at' => now(),
            'received_at' => now(),
        ];
    }
}
