<?php

namespace App\Actions\Dashboard;

use App\Models\Article;
use App\Models\Game;
use App\Models\GameRating;
use App\Models\User;

class GetDashboardStats
{
    /**
     * @return array{
     *     games: array{total: int, rated_total: int},
     *     ratings: array{total: int, average: float|null},
     *     articles: array{published_total: int, premium_total: int},
     *     users: array{total: int},
     * }
     */
    public function handle(): array
    {
        $ratingsCount = GameRating::count();

        $ratingsAverage = null;

        if ($ratingsCount > 0) {
            $ratingsAverage = round((float) GameRating::avg('rating'), 1);
        }

        return [
            'games' => [
                'total' => Game::count(),
                'rated_total' => GameRating::query()->distinct()->count('game_id'),
            ],
            'ratings' => [
                'total' => $ratingsCount,
                'average' => $ratingsAverage,
            ],
            'articles' => [
                'published_total' => Article::published()->count(),
                'premium_total' => Article::published()->where('is_premium', true)->count(),
            ],
            'users' => [
                'total' => User::count(),
            ],
        ];
    }
}
