<?php

namespace App\Http\Controllers\API;

use App\Actions\Dashboard\GetDashboardStats;
use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(private readonly GetDashboardStats $getDashboardStats)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->getDashboardStats->handle());
    }

    public function gameRating(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
        ]);

        $game = Game::query()
            ->where(function ($query) use ($data) {
                $query->where('title', $data['name'])
                    ->orWhere('slug', $data['name']);
            })
            ->firstOrFail();

        $ratingsCount = $game->ratings()->count();

        $averageRating = null;

        if ($ratingsCount > 0) {
            $averageRating = round((float) $game->ratings()->avg('rating'), 1);
        }

        return response()->json([
            'game_id' => $game->id,
            'average_rating' => $averageRating,
            'ratings_count' => $ratingsCount,
        ]);
    }
}
