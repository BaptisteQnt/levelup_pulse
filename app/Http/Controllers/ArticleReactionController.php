<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleReaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArticleReactionController extends Controller
{
    public function store(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'reaction' => ['required', 'in:like,dislike'],
        ]);

        $reactionValue = $validated['reaction'] === 'like'
            ? ArticleReaction::LIKE
            : ArticleReaction::DISLIKE;

        $existing = $article->reactions()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing && $existing->reaction === $reactionValue) {
            $existing->delete();
        } else {
            $article->reactions()->updateOrCreate(
                ['user_id' => $request->user()->id],
                ['reaction' => $reactionValue],
            );
        }

        return back()->with('success', 'Ta reaction a ete prise en compte.');
    }
}
