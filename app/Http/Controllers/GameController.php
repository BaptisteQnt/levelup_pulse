<?php

namespace App\Http\Controllers;

use App\Jobs\TranslateGameTexts;
use App\Models\Article;
use App\Models\Game;
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

        $articles = $game->articles()
            ->published()
            ->with('author:id,name,username')
            ->withCount('reactions')
            ->latest();

        $articles = $articles->get()->map(function (Article $article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => Str::limit(strip_tags($article->content), 180),
                'is_premium' => $article->is_premium,
                'published_at' => $article->published_at?->toIso8601String(),
                'reactions_count' => (int) ($article->reactions_count ?? 0),
                'author' => [
                    'name' => $article->author->name,
                    'username' => $article->author->username,
                ],
            ];
        })->values();

        return Inertia::render('games/Show', [
            'game' => [
                'id'          => $game->id,
                'title'       => $game->title,
                'slug'        => $game->slug,
                'cover_url'   => $game->cover_url,
                'summary'     => $texts['summary'],
                'storyline'   => $texts['storyline'],
                'description' => $body !== '' ? $body : null,
                'articles'    => $articles->all(),
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
            'canCreateArticle' => (bool) ($requestUser?->is_editor || $requestUser?->is_admin || $requestUser?->is_super_admin),
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
