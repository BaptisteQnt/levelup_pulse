<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);
        $slug = Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1000, 9999);

        return [
            'title' => $title,
            'slug' => $slug,
            'twitch_id' => (string) $this->faker->unique()->numberBetween(100000, 999999),
            'cover_url' => null,
            'description' => $this->faker->paragraph(),
            'summary' => $this->faker->optional()->paragraph(),
            'storyline' => $this->faker->optional()->paragraph(),
        ];
    }
}
