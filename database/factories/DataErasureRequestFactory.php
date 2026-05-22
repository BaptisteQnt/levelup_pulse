<?php

namespace Database\Factories;

use App\Models\DataErasureRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataErasureRequest>
 */
class DataErasureRequestFactory extends Factory
{
    protected $model = DataErasureRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'request_type' => $this->faker->randomElement(['account_deletion', 'data_deletion']),
            'details' => $this->faker->optional()->sentence(),
            'status' => 'pending',
            'admin_notes' => null,
            'resolved_at' => null,
        ];
    }

    public function accountDeletion(): self
    {
        return $this->state(fn () => [
            'request_type' => 'account_deletion',
        ]);
    }

    public function dataDeletion(): self
    {
        return $this->state(fn () => [
            'request_type' => 'data_deletion',
        ]);
    }
}
