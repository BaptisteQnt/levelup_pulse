<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;


class GameRatingController extends Controller
{
    public function store(Request $request, Game $game): RedirectResponse
    {
        if (!Schema::hasTable('game_ratings')) {
            throw ValidationException::withMessages([
                'rating' => 'Les notes ne sont pas disponibles pour le moment. Veuillez réessayer plus tard.',
            ]);
        }


        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        GameRating::updateOrCreate(
            [
                'game_id' => $game->id,
                'user_id' => $request->user()->id,
            ],
            [
                'rating' => $validated['rating'],
            ]
        );

        return back()->with('success', 'Votre note a été enregistrée.');
    }
}
