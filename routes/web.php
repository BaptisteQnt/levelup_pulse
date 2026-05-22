<?php

use App\Actions\Dashboard\GetDashboardStats;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\GameController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\PowersController;
use App\Http\Controllers\GameRatingController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentReactionController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\TipReactionController;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use App\Models\Announcement;
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
        'stats'         => $stats,
    ]);
};

Route::get('/', $dashboardPage)->name('home');

Route::middleware(['auth', 'verified'])->get('/dashboard', $dashboardPage)->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::get('/games/{slug}', [GameController::class, 'show'])->name('games.show');
    Route::post('/games/{game}/rating', [GameRatingController::class, 'store'])->name('games.rating.store');
});
Route::middleware('auth')->delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
Route::middleware('auth')->post('/comments/{comment}/react', [CommentReactionController::class, 'store'])->name('comments.react');
Route::middleware('auth')->post('/tips', [TipController::class, 'store'])->name('tips.store');
Route::middleware('auth')->delete('/tips/{tip}', [TipController::class, 'destroy'])->name('tips.destroy');
Route::middleware('auth')->post('/tips/{tip}/react', [TipReactionController::class, 'store'])->name('tips.react');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/moderation', ModerationController::class)->name('admin.moderation.index');
    Route::get('/admin/powers', [PowersController::class, 'index'])->name('admin.powers.index');
    Route::patch('/admin/powers/{user}', [PowersController::class, 'update'])->name('admin.powers.update');
    Route::patch('/admin/comments/{comment}/approve', [CommentController::class, 'approve'])->name('admin.comments.approve');
    Route::patch('/admin/tips/{tip}/approve', [TipController::class, 'approve'])->name('admin.tips.approve');
    Route::get('/admin/announcements', [AnnouncementController::class, 'index'])->name('admin.announcements.index');
    Route::post('/admin/announcements', [AnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::delete('/admin/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');
    Route::get('/admin/privacy/requests', [AdminDataErasureRequestController::class, 'index'])->name('admin.privacy.requests.index');
    Route::patch('/admin/privacy/requests/{dataErasureRequest}', [AdminDataErasureRequestController::class, 'update'])->name('admin.privacy.requests.update');
    Route::post('/admin/privacy/requests/{dataErasureRequest}/account/delete', [AdminDataErasureRequestController::class, 'destroyAccount'])->name('admin.privacy.requests.destroy-account');
    Route::post('/admin/privacy/requests/{dataErasureRequest}/data/anonymize', [AdminDataErasureRequestController::class, 'erasePersonalData'])->name('admin.privacy.requests.erase-data');
});

use App\Http\Controllers\Auth\SocialiteController;

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->whereIn('provider', ['google','discord'])
    ->name('oauth.redirect');

Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->whereIn('provider', ['google','discord'])
    ->name('oauth.callback');



Route::get('/users/{username}', [UserProfileController::class, 'show'])->name('users.show');

Route::middleware('auth')->post('/comments', [CommentController::class, 'store'])->name('comments.store');

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
