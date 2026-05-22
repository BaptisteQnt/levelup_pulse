<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class GameRatingController extends Controller
{
    private const RATINGS_UNAVAILABLE_MESSAGE = 'Les notes ne sont pas disponibles pour le moment. Veuillez réessayer plus tard.';

    public function show(Request $request, Game $game): JsonResponse
    {
        $this->ensureRatingsTableExists();

        $rating = GameRating::query()
            ->where('game_id', $game->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($rating === null) {
            return response()->json([
                'message' => 'Aucune note trouvée pour ce jeu.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'game_id' => $rating->game_id,
            'user_id' => $rating->user_id,
            'rating' => $rating->rating,
            'created_at' => $rating->created_at,
            'updated_at' => $rating->updated_at,
        ]);
    }

    public function store(Request $request, Game $game): JsonResponse
    {
        $this->ensureRatingsTableExists();

        $validated = $this->validateRating($request);

        $userId = $request->user()->id;

        $existingRating = GameRating::query()
            ->where('game_id', $game->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingRating !== null) {
            return response()->json([
                'message' => 'Une note existe déjà pour ce jeu. Utilisez PUT pour la modifier.',
            ], Response::HTTP_CONFLICT);
        }

        $rating = GameRating::create([
            'game_id' => $game->id,
            'user_id' => $userId,
            'rating' => $validated['rating'],
        ]);

        return response()->json([
            'game_id' => $rating->game_id,
            'user_id' => $rating->user_id,
            'rating' => $rating->rating,
            'created_at' => $rating->created_at,
            'updated_at' => $rating->updated_at,
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, Game $game): JsonResponse
    {
        $this->ensureRatingsTableExists();

        $validated = $this->validateRating($request);

        $rating = GameRating::query()
            ->where('game_id', $game->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($rating === null) {
            return response()->json([
                'message' => 'Aucune note trouvée pour ce jeu.',
            ], Response::HTTP_NOT_FOUND);
        }

        $rating->update([
            'rating' => $validated['rating'],
        ]);

        return response()->json([
            'game_id' => $rating->game_id,
            'user_id' => $rating->user_id,
            'rating' => $rating->rating,
            'created_at' => $rating->created_at,
            'updated_at' => $rating->updated_at,
        ]);
    }

    public function destroy(Request $request, Game $game): JsonResponse
    {
        $this->ensureRatingsTableExists();

        $rating = GameRating::query()
            ->where('game_id', $game->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($rating === null) {
            return response()->json([
                'message' => 'Aucune note trouvée pour ce jeu.',
            ], Response::HTTP_NOT_FOUND);
        }

        $rating->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function validateRating(Request $request): array
    {
        return $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:10'],
        ]);
    }

    private function ensureRatingsTableExists(): void
    {
        if (! Schema::hasTable('game_ratings')) {
            throw ValidationException::withMessages([
                'rating' => self::RATINGS_UNAVAILABLE_MESSAGE,
            ]);
        }
    }
}
