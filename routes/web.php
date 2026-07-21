<?php

use App\Actions\Dashboard\GetDashboardStats;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleReactionController;
use App\Http\Controllers\Security\AuditLogController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\GameController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\PowersController;
use App\Http\Controllers\GameRatingController;
use App\Http\Controllers\SubscriptionController;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Article;
use App\Models\Game;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\DataErasureRequestController;
use App\Http\Controllers\Admin\DataErasureRequestController as AdminDataErasureRequestController;


$dashboardPage = function (Request $request) {
    $user = $request->user();

    if ($user) {
        $user->refresh();
    }

    $subscription = $user?->subscription('default');

    $announcement = Announcement::query()
        ->with(['user:id,name,username'])
        ->orderByDesc('published_at')
        ->orderByDesc('created_at')
        ->first();

    $recentGames = Game::query()
        ->latest('created_at')
        ->take(5)
        ->get(['id', 'title', 'slug', 'cover_url', 'created_at'])
        ->map(fn (Game $game) => [
            'id'          => $game->id,
            'title'       => $game->title,
            'slug'        => $game->slug,
            'cover_url'   => $game->cover_url,
            'searched_at' => optional($game->created_at)->toIso8601String(),
        ])
        ->values()
        ->all();

    $latestArticles = Article::query()
        ->published()
        ->with(['game:id,title,slug', 'author:id,name,username'])
        ->latest('published_at')
        ->take(5)
        ->get()
        ->map(fn (Article $article) => [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => \Illuminate\Support\Str::limit(strip_tags($article->content), 150),
            'is_premium' => $article->is_premium,
            'published_at' => $article->published_at?->toIso8601String(),
            'game' => [
                'title' => $article->game->title,
                'slug' => $article->game->slug,
            ],
            'author' => [
                'name' => $article->author->name,
                'username' => $article->author->username,
            ],
        ])
        ->values()
        ->all();

    $stats = app(GetDashboardStats::class)->handle();

    return Inertia::render('Dashboard', [
        'isSubscribed'  => $subscription?->active() ?? false,
        'onGracePeriod' => $subscription?->onGracePeriod() ?? false,
        'endsAt'        => optional($subscription?->ends_at)->format('d/m/Y'),
        'announcement'  => $announcement ? [
            'id'           => $announcement->id,
            'title'        => $announcement->title,
            'content'      => $announcement->content,
            'published_at' => $announcement->published_at?->toIso8601String(),
            'author'       => $announcement->user?->only(['id', 'name', 'username']),
        ] : null,
        'recentGames'   => $recentGames,
        'latestArticles' => $latestArticles,
        'stats'         => $stats,
    ]);
};

Route::get('/', $dashboardPage)->name('home');

Route::middleware(['auth', 'verified'])->get('/dashboard', $dashboardPage)->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::get('/games/{slug}', [GameController::class, 'show'])->name('games.show');
    Route::post('/games/{game}/rating', [GameRatingController::class, 'store'])->name('games.rating.store');
    Route::get('/articles/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
    Route::post('/articles/{article:slug}/react', [ArticleReactionController::class, 'store'])->name('articles.react');
});

Route::middleware(['auth', 'editor'])->group(function () {
    Route::get('/games/{game}/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/games/{game}/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article:slug}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::patch('/articles/{article:slug}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article:slug}', [ArticleController::class, 'destroy'])->name('articles.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/powers', [PowersController::class, 'index'])->name('admin.powers.index');
    Route::patch('/admin/powers/{user}', [PowersController::class, 'update'])->name('admin.powers.update');
    Route::get('/admin/announcements', [AnnouncementController::class, 'index'])->name('admin.announcements.index');
    Route::post('/admin/announcements', [AnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::delete('/admin/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');
    Route::get('/admin/privacy/requests', [AdminDataErasureRequestController::class, 'index'])->name('admin.privacy.requests.index');
    Route::patch('/admin/privacy/requests/{dataErasureRequest}', [AdminDataErasureRequestController::class, 'update'])->name('admin.privacy.requests.update');
    Route::post('/admin/privacy/requests/{dataErasureRequest}/account/delete', [AdminDataErasureRequestController::class, 'destroyAccount'])->name('admin.privacy.requests.destroy-account');
    Route::post('/admin/privacy/requests/{dataErasureRequest}/data/anonymize', [AdminDataErasureRequestController::class, 'erasePersonalData'])->name('admin.privacy.requests.erase-data');
});

Route::middleware(['auth', 'security'])->group(function () {
    Route::get('/security/audit-logs', [AuditLogController::class, 'index'])->name('security.audit-logs.index');
});

use App\Http\Controllers\Auth\SocialiteController;

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->whereIn('provider', ['google','discord'])
    ->name('oauth.redirect');

Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->whereIn('provider', ['google','discord'])
    ->name('oauth.callback');



Route::get('/users/{username}', [UserProfileController::class, 'show'])->name('users.show');

// Pages plans/checkout/portal protégées pour utilisateurs connectés
Route::middleware(['auth','verified','profile.complete'])->group(function () {
    Route::get('/billing/plans', [SubscriptionController::class, 'plans'])->name('billing.plans');
    Route::get('/billing/checkout', [SubscriptionController::class, 'checkout'])->name('billing.checkout'); // ⬅️ GET
    Route::get('/billing/success', [SubscriptionController::class, 'success'])->name('billing.success');
    Route::get('/billing/cancel', [SubscriptionController::class, 'cancel'])->name('billing.cancel');
    Route::get('/billing/portal', [SubscriptionController::class, 'portal'])->name('billing.portal');
});

// Webhook Stripe (Cashier le gère)
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook']);

Route::middleware(['auth','verified','subscribed'])
    ->get('/premium', fn() => Inertia::render('Premium/Index'));

Route::get('/information', fn () => Inertia::render('Information'))->name('information');
Route::get('/presentation', fn () => Inertia::render('Presentation'))->name('presentation');
Route::get('/mentions-legales', [LegalController::class, 'mentions'])->name('legal.mentions');
Route::get('/politique-confidentialite', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/conditions-generales-vente', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/politique-cookies', [LegalController::class, 'cookies'])->name('legal.cookies');
Route::middleware('auth')->post('/mentions-legales/demandes', [DataErasureRequestController::class, 'store'])->name('legal.requests.store');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
