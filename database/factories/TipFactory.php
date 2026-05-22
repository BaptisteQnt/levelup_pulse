<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tip>
 */
class TipFactory extends Factory
{
    protected $model = Tip::class;

    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(),
            'user_id' => User::factory(),
            'game_id' => Game::factory(),
            'is_approved' => true,
        ];
    }
}
