<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentReactionController extends Controller
{
    public function store(Request $request, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $validated = $request->validate([
            'reaction' => 'required|in:like,dislike',
        ]);

        $reactionValue = $validated['reaction'] === 'like'
            ? CommentReaction::LIKE
            : CommentReaction::DISLIKE;

        $existing = $comment->reactions()
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->reaction === $reactionValue) {
            $existing->delete();
        } else {
            $comment->reactions()->updateOrCreate(
                ['user_id' => $user->id],
                ['reaction' => $reactionValue],
            );
        }

        return back()->with('success', 'Ta réaction a été prise en compte.');
    }
}
