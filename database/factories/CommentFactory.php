<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

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
