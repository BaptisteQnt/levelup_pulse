<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'content' => 'required|string|min:3|max:1000',
        ]);

        Comment::create([
            'user_id'     => Auth::id(),
            'game_id'     => $validated['game_id'],
            'content'     => $validated['content'],
            'is_approved' => false,
        ]);

        return back()->with('success', 'Merci pour ton partage ! Ton commentaire sera visible après validation.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();

        if (! $user || ($comment->user_id !== $user->id && ! $user->is_admin)) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Commentaire supprimé.');
    }

    public function approve(Request $request, Comment $comment)
    {
        if (! $request->user()?->is_admin) {
            abort(403);
        }

        if (! $comment->is_approved) {
            $comment->forceFill(['is_approved' => true])->save();
        }

        return back()->with('success', 'Commentaire validé avec succès.');
    }

}

