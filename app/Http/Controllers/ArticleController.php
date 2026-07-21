<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleReaction;
use App\Models\Game;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function create(Game $game): Response
    {
        return Inertia::render('articles/Edit', [
            'mode' => 'create',
            'game' => $this->gamePayload($game),
            'article' => null,
        ]);
    }

    public function store(Request $request, Game $game, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $this->validateArticle($request);

        $article = Article::create([
            ...$validated,
            'game_id' => $game->id,
            'user_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($validated['title']),
            'images' => $this->storeImages($request),
            'keywords' => $this->normalizeKeywords($validated['keywords'] ?? null),
        ]);

        $auditLogger->log($request, 'article.created', $article, [
            'title' => $article->title,
            'game_id' => $game->id,
            'game_title' => $game->title,
            'is_premium' => $article->is_premium,
            'published_at' => $article->published_at?->toIso8601String(),
        ]);

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'Article publie avec succes.');
    }

    public function show(Request $request, Article $article): Response
    {
        $article->load(['game:id,title,slug,cover_url', 'author:id,name,username']);

        if (! $article->published_at || $article->published_at->isFuture()) {
            if (! $this->canManage($request, $article)) {
                abort(404);
            }
        }

        if (! $this->canRead($request, $article)) {
            abort(403, 'Cet article est reserve aux abonnes.');
        }

        $article->loadCount([
            'reactions as likes_count' => fn ($query) => $query->where('reaction', ArticleReaction::LIKE),
            'reactions as dislikes_count' => fn ($query) => $query->where('reaction', ArticleReaction::DISLIKE),
        ]);

        $userReaction = null;

        if ($request->user()) {
            $userReaction = $article->reactions()
                ->where('user_id', $request->user()->id)
                ->value('reaction');
        }

        return Inertia::render('articles/Show', [
            'article' => $this->articlePayload($article, $userReaction),
            'canManage' => $this->canManage($request, $article),
            'flash' => $request->session()->get('success'),
        ]);
    }

    public function edit(Request $request, Article $article): Response
    {
        if (! $this->canManage($request, $article)) {
            abort(403);
        }

        $article->load(['game:id,title,slug,cover_url']);

        return Inertia::render('articles/Edit', [
            'mode' => 'edit',
            'game' => $this->gamePayload($article->game),
            'article' => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'content' => $article->content,
                'keywords' => implode(', ', $article->keywords ?? []),
                'is_premium' => $article->is_premium,
                'published_at' => $article->published_at?->format('Y-m-d\TH:i'),
                'images' => collect($article->images ?? [])
                    ->map(fn (string $path) => Storage::disk('public')->url($path))
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function update(Request $request, Article $article, AuditLogger $auditLogger): RedirectResponse
    {
        if (! $this->canManage($request, $article)) {
            abort(403);
        }

        $validated = $this->validateArticle($request);
        $newImages = $this->storeImages($request);
        $before = $article->only(['title', 'is_premium', 'published_at']);

        $article->update([
            ...$validated,
            'slug' => $article->title === $validated['title']
                ? $article->slug
                : $this->uniqueSlug($validated['title'], $article),
            'images' => array_values(array_merge($article->images ?? [], $newImages)),
            'keywords' => $this->normalizeKeywords($validated['keywords'] ?? null),
        ]);

        $auditLogger->log($request, 'article.updated', $article, [
            'before' => [
                'title' => $before['title'],
                'is_premium' => $before['is_premium'],
                'published_at' => $before['published_at']?->toIso8601String(),
            ],
            'after' => [
                'title' => $article->title,
                'is_premium' => $article->is_premium,
                'published_at' => $article->published_at?->toIso8601String(),
            ],
            'new_images_count' => count($newImages),
        ]);

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'Article mis a jour.');
    }

    public function destroy(Request $request, Article $article, AuditLogger $auditLogger): RedirectResponse
    {
        if (! $this->canManage($request, $article)) {
            abort(403);
        }

        $game = $article->game;

        foreach ($article->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $auditLogger->log($request, 'article.deleted', $article, [
            'title' => $article->title,
            'slug' => $article->slug,
            'game_id' => $game->id,
            'game_title' => $game->title,
        ]);

        $article->delete();

        return redirect()
            ->route('games.show', $game->slug)
            ->with('success', 'Article supprime.');
    }

    private function validateArticle(Request $request): array
    {
        $request->merge([
            'is_premium' => filter_var($request->input('is_premium'), FILTER_VALIDATE_BOOLEAN),
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'content' => ['required', 'string', 'min:20'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'is_premium' => ['required', 'boolean'],
            'published_at' => ['required', 'date'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['file', 'image', 'max:4096'],
        ]);
    }

    private function storeImages(Request $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        return collect($request->file('images'))
            ->map(fn ($image) => $image->store('articles', 'public'))
            ->all();
    }

    private function normalizeKeywords(?string $keywords): array
    {
        return str($keywords ?? '')
            ->explode(',')
            ->map(fn (string $keyword) => trim($keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function uniqueSlug(string $title, ?Article $existing = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $index = 2;

        while (Article::query()
            ->where('slug', $slug)
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
            ->exists()) {
            $slug = "{$base}-{$index}";
            $index++;
        }

        return $slug;
    }

    private function canRead(Request $request, Article $article): bool
    {
        $user = $request->user();

        if (! $article->is_premium) {
            return true;
        }

        return (bool) ($user?->is_admin || $user?->is_editor || $user?->is_super_admin || $user?->is_subscribed);
    }

    private function canManage(Request $request, Article $article): bool
    {
        $user = $request->user();

        return (bool) ($user?->is_super_admin || $user?->is_admin || ($user?->is_editor && $article->user_id === $user->id));
    }

    private function gamePayload(Game $game): array
    {
        return [
            'id' => $game->id,
            'title' => $game->title,
            'slug' => $game->slug,
            'cover_url' => $game->cover_url,
        ];
    }

    private function articlePayload(Article $article, ?int $userReaction): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'images' => collect($article->images ?? [])
                ->map(fn (string $path) => Storage::disk('public')->url($path))
                ->values()
                ->all(),
            'keywords' => $article->keywords ?? [],
            'is_premium' => $article->is_premium,
            'published_at' => $article->published_at?->toIso8601String(),
            'likes_count' => (int) ($article->likes_count ?? 0),
            'dislikes_count' => (int) ($article->dislikes_count ?? 0),
            'user_reaction' => match ($userReaction) {
                ArticleReaction::LIKE => 'like',
                ArticleReaction::DISLIKE => 'dislike',
                default => null,
            },
            'author' => [
                'id' => $article->author->id,
                'name' => $article->author->name,
                'username' => $article->author->username,
            ],
            'game' => $this->gamePayload($article->game),
        ];
    }
}
