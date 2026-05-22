<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TipController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'content' => 'required|string|min:3|max:1000',
        ]);

        Tip::create([
            'user_id'     => Auth::id(),
            'game_id'     => $validated['game_id'],
            'content'     => $validated['content'],
            'is_approved' => false,
        ]);

        return back()->with('success', 'Merci pour ton astuce ! Elle sera visible après validation.');
    }

    public function destroy(Request $request, Tip $tip)
    {
        $user = $request->user();

        if (! $user || ($tip->user_id !== $user->id && ! $user->is_admin)) {
            abort(403);
        }

        $tip->delete();

        return back()->with('success', 'Astuce supprimée.');
    }

    public function approve(Request $request, Tip $tip)
    {
        if (! $request->user()?->is_admin) {
            abort(403);
        }

        if (! $tip->is_approved) {
            $tip->forceFill(['is_approved' => true])->save();
        }

        return back()->with('success', 'Astuce validée avec succès.');
    }
}
