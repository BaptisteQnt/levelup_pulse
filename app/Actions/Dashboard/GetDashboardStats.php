<?php

namespace App\Actions\Dashboard;

use App\Models\Comment;
use App\Models\Game;
use App\Models\GameRating;
use App\Models\Tip;
use App\Models\User;

class GetDashboardStats
{
    /**
     * @return array{
     *     games: array{total: int, rated_total: int},
     *     ratings: array{total: int, average: float|null},
     *     comments: array{approved_total: int},
     *     tips: array{approved_total: int},
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
            'comments' => [
                'approved_total' => Comment::approved()->count(),
            ],
            'tips' => [
                'approved_total' => Tip::approved()->count(),
            ],
            'users' => [
                'total' => User::count(),
            ],
        ];
    }
}

