<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Tip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $pendingComments = Comment::query()
            ->with(['user:id,username', 'game:id,title,slug'])
            ->where('is_approved', false)
            ->latest()
            ->get()
            ->map(fn (Comment $comment) => [
                'id'         => $comment->id,
                'content'    => $comment->content,
                'created_at' => $comment->created_at?->toIso8601String(),
                'user'       => [
                    'id'       => $comment->user->id,
                    'username' => $comment->user->username,
                ],
                'game'       => [
                    'id'    => $comment->game->id,
                    'title' => $comment->game->title,
                    'slug'  => $comment->game->slug,
                ],
            ]);

        $pendingTips = Tip::query()
            ->with(['user:id,username', 'game:id,title,slug'])
            ->where('is_approved', false)
            ->latest()
            ->get()
            ->map(fn (Tip $tip) => [
                'id'         => $tip->id,
                'content'    => $tip->content,
                'created_at' => $tip->created_at?->toIso8601String(),
                'user'       => [
                    'id'       => $tip->user->id,
                    'username' => $tip->user->username,
                ],
                'game'       => [
                    'id'    => $tip->game->id,
                    'title' => $tip->game->title,
                    'slug'  => $tip->game->slug,
                ],
            ]);

        return Inertia::render('admin/Moderation', [
            'pendingComments' => $pendingComments,
            'pendingTips'     => $pendingTips,
            'flash'           => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ]);
    }
}
