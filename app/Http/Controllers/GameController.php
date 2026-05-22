<?php

namespace App\Http\Controllers;

use App\Jobs\TranslateGameTexts;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Game;
use App\Models\Tip;
use App\Models\TipReaction;
use App\Services\IGDBService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class GameController extends Controller
{
    public function index(Request $request): Response
    {
        $lang = $request->input('lang', 'en');
        $search = trim((string) $request->input('search', ''));
        $search = $search === '' ? null : $search;
        $searchMessage = null;

        $query = Game::query();

        if ($search !== null) {
            $this->applySearchFilter($query, $search);

            if (!(clone $query)->exists()) {
                $imported = 0;

                try {
                    /** @var IGDBService $igdb */
                    $igdb = app(IGDBService::class);
                    $imported = $this->importGamesFromIGDB($igdb, $search);
                } catch (Throwable $exception) {
                    report($exception);
                    $searchMessage = "Impossible de contacter IGDB pour cette recherche.";
                }

                $query = Game::query();
                $this->applySearchFilter($query, $search);

                if ((clone $query)->exists()) {
                    if ($imported > 0) {
                        $searchMessage = $imported === 1
                            ? 'Un jeu a été importé depuis IGDB.'
                            : "{$imported} jeux ont été importés depuis IGDB.";
                    }
                } elseif ($searchMessage === null) {
                    $searchMessage = "Aucun jeu n'a été trouvé pour \"{$search}\".";
                }
            }
        }

        $paginator = $query->orderByDesc('created_at')
            ->paginate(9, $this->gameColumns())
            ->appends(array_filter([
                'lang' => $lang,
                'search' => $search,
            ], fn ($value) => $value !== null));

        $paginator->getCollection()->transform(function (Game $game) use ($lang) {
            $texts = $game->localizedTexts($lang);
            $body = collect([
                $texts['storyline'] ?? null,
                $texts['summary'] ?? null,
            ])->filter()->implode("\n\n");

            return [
                'id'          => $game->id,
                'title'       => $game->title,
                'slug'        => $game->slug,
                'cover_url'   => $game->cover_url,
                'summary'     => $texts['summary'],
                'storyline'   => $texts['storyline'],
                'description' => $body !== '' ? $body : null,
            ];
        });

        $games = [
            'data' => $paginator->items(),
            'links' => $paginator->linkCollection()->map(fn ($link) => [
                'url' => $link['url'],
                'label' => $link['label'],
                'active' => $link['active'],
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];

        return Inertia::render('games/Index', [
            'games'           => $games,
            'activeLanguage'  => $lang,
            'searchQuery'     => $search,
            'searchMessage'   => $searchMessage,
        ]);
    }

    public function show(string $slug): \Inertia\Response
    {

        $hasRatingsTable = Schema::hasTable('game_ratings');

        $gameQuery = Game::where('slug', $slug);

        if ($hasRatingsTable) {
            $gameQuery->withCount('ratings')->withAvg('ratings', 'rating');
        }

        $game = $gameQuery->firstOrFail();

        $lang = request('lang', 'en');

        $texts = $game->localizedTexts($lang);
        $body = collect([
            $texts['storyline'] ?? null,
            $texts['summary'] ?? null,
        ])->filter()->implode("\n\n");

        $userRating = null;
        $requestUser = request()->user();

        if ($hasRatingsTable && $requestUser) {
            $userRating = $game->ratings()
                ->where('user_id', $requestUser->id)
                ->value('rating');
        }

        $commentsQuery = $game->comments()
            ->approved()
            ->with('user:id,username,display_name_color,display_alias,profile_border_style')
            ->withCount([
                'reactions as likes_count' => fn ($query) => $query->where('reaction', CommentReaction::LIKE),
                'reactions as dislikes_count' => fn ($query) => $query->where('reaction', CommentReaction::DISLIKE),
            ])
            ->latest();

        if ($requestUser) {
            $commentsQuery->with([
                'reactions' => fn ($query) => $query
                    ->where('user_id', $requestUser->id)
                    ->select('id', 'comment_id', 'user_id', 'reaction'),
            ]);
        }

        $comments = $commentsQuery->get()->map(function (Comment $comment) use ($requestUser) {
            $commentUser = $comment->user;

            $userReaction = null;

            if ($requestUser && $comment->relationLoaded('reactions')) {
                $userReaction = optional($comment->reactions->first())->reaction;
            }

            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'likes_count' => (int) ($comment->likes_count ?? 0),
                'dislikes_count' => (int) ($comment->dislikes_count ?? 0),
                'user_reaction' => match ($userReaction) {
                    CommentReaction::LIKE => 'like',
                    CommentReaction::DISLIKE => 'dislike',
                    default => null,
                },
                'user' => [
                    'username' => $commentUser->username,
                    'display_name_color' => $commentUser->display_name_color,
                    'display_alias' => $commentUser->display_alias,
                    'profile_border_style' => $commentUser->profile_border_style,
                    'is_subscribed' => $commentUser->is_subscribed,
                ],
            ];
        })->values();

        $tipsQuery = $game->tips()
            ->approved()
            ->with('user:id,username,display_name_color,display_alias,profile_border_style')
            ->withCount([
                'reactions as likes_count' => fn ($query) => $query->where('reaction', TipReaction::LIKE),
                'reactions as dislikes_count' => fn ($query) => $query->where('reaction', TipReaction::DISLIKE),
            ])
            ->latest();

        if ($requestUser) {
            $tipsQuery->with([
                'reactions' => fn ($query) => $query
                    ->where('user_id', $requestUser->id)
                    ->select('id', 'tip_id', 'user_id', 'reaction'),
            ]);
        }

        $tips = $tipsQuery->get()->map(function (Tip $tip) use ($requestUser) {
            $tipUser = $tip->user;

            $userReaction = null;

            if ($requestUser && $tip->relationLoaded('reactions')) {
                $userReaction = optional($tip->reactions->first())->reaction;
            }

            return [
                'id' => $tip->id,
                'content' => $tip->content,
                'likes_count' => (int) ($tip->likes_count ?? 0),
                'dislikes_count' => (int) ($tip->dislikes_count ?? 0),
                'user_reaction' => match ($userReaction) {
                    TipReaction::LIKE => 'like',
                    TipReaction::DISLIKE => 'dislike',
                    default => null,
                },
                'user' => [
                    'username' => $tipUser->username,
                    'display_name_color' => $tipUser->display_name_color,
                    'display_alias' => $tipUser->display_alias,
                    'profile_border_style' => $tipUser->profile_border_style,
                    'is_subscribed' => $tipUser->is_subscribed,
                ],
            ];
        })->values();

        return Inertia::render('games/Show', [
            'game' => [
                'id'          => $game->id,
                'title'       => $game->title,
                'cover_url'   => $game->cover_url,
                'summary'     => $texts['summary'],
                'storyline'   => $texts['storyline'],
                'description' => $body !== '' ? $body : null,
                'comments'    => $comments->all(),
                'tips'        => $tips->all(),
                'ratings'     => [

                    'enabled' => $hasRatingsTable,
                    'average' => ($hasRatingsTable && $game->ratings_avg_rating !== null)
                        ? round((float) $game->ratings_avg_rating, 1)
                        : null,
                    'count'   => $hasRatingsTable
                        ? (int) ($game->ratings_count ?? 0)
                        : 0,

                    'user'    => $userRating !== null ? (int) $userRating : null,
                ],
            ],
            'flash' => session('success'),
        ]);
    }

    /**
     * Retourne les colonnes disponibles pour la table games.
     */
    private function gameColumns(): array
    {
        static $columns;

        if ($columns === null) {
            $columns = ['id', 'title', 'slug', 'cover_url', 'description'];

            foreach (['summary', 'storyline'] as $optionalColumn) {
                if (Schema::hasColumn('games', $optionalColumn)) {
                    $columns[] = $optionalColumn;
                }
            }
        }

        return $columns;
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        $slug = Str::slug($search);

        $query->where(function (Builder $builder) use ($search, $slug) {
            $builder->where('title', 'like', "%{$search}%");

            if ($slug !== '') {
                $builder->orWhere('slug', 'like', "%{$slug}%");
            }
        });
    }

    private function importGamesFromIGDB(IGDBService $igdb, string $search): int
    {
        $imported = 0;

        foreach ($igdb->fetchGames($search) as $gameData) {
            if (!isset($gameData['name'])) {
                continue;
            }

            $slug = Str::slug($gameData['name']);

            $game = Game::updateOrCreate(
                ['slug' => $slug],
                [
                    'title'       => $gameData['name'],
                    'twitch_id'   => $gameData['id'] ?? null,
                    'cover_url'   => data_get($gameData, 'cover.url'),
                    'summary'     => $gameData['summary'] ?? null,
                    'storyline'   => $gameData['storyline'] ?? null,
                    'description' => $gameData['storyline']
                        ?? $gameData['summary']
                        ?? null,
                ]
            );

            if (filled($game->summary) || filled($game->storyline) || filled($game->description)) {
                TranslateGameTexts::dispatchSync($game->id);
            }

            $imported++;
        }

        return $imported;
    }
}

