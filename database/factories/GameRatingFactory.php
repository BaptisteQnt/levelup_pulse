<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameRating>
 */
class GameRatingFactory extends Factory
{
    protected $model = GameRating::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'rating' => $this->faker->numberBetween(1, 10),
        ];
    }
}
