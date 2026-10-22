<?php

namespace Database\Factories;

use App\Models\IotDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IotDevice>
 */
class IotDeviceFactory extends Factory
{
    protected $model = IotDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => 'LUP-ESP32-'.$this->faker->unique()->numerify('###'),
            'token_hash' => hash('sha256', Str::random(64)),
            'is_active' => true,
            'last_sequence' => null,
            'last_seen_at' => null,
            'token_rotated_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function revoked(): self
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }
}
