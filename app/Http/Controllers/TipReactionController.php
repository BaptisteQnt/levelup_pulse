<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use App\Models\TipReaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TipReactionController extends Controller
{
    public function store(Request $request, Tip $tip): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $validated = $request->validate([
            'reaction' => 'required|in:like,dislike',
        ]);

        $reactionValue = $validated['reaction'] === 'like'
            ? TipReaction::LIKE
            : TipReaction::DISLIKE;

        $existing = $tip->reactions()
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->reaction === $reactionValue) {
            $existing->delete();
        } else {
            $tip->reactions()->updateOrCreate(
                ['user_id' => $user->id],
                ['reaction' => $reactionValue],
            );
        }

        return back()->with('success', 'Ta réaction a été prise en compte.');
    }
}
