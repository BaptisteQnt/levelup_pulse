<?php

use App\Models\Article;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('home exposes the latest published editorial content and recent games', function () {
    Storage::fake('public');

    $author = User::factory()->create([
        'name' => 'Rédaction LevelUp',
        'username' => 'levelup-redaction',
    ]);

    $games = collect(range(1, 7))->map(fn (int $index) => Game::factory()->create([
        'title' => "Jeu {$index}",
        'slug' => "jeu-{$index}",
        'cover_url' => "//images.igdb.com/igdb/image/upload/t_thumb/jeu{$index}.jpg",
        'created_at' => now()->subDays(7 - $index),
    ]));

    collect(range(1, 6))->each(fn (int $index) => Article::factory()
        ->for($games[$index - 1], 'game')
        ->for($author, 'author')
        ->create([
            'title' => "Article {$index}",
            'slug' => "article-{$index}",
            'images' => $index === 6 ? ['articles/une.jpg'] : [],
            'published_at' => now()->subDays(6 - $index),
        ]));

    Article::factory()
        ->for($games->first(), 'game')
        ->for($author, 'author')
        ->create([
            'title' => 'Article futur',
            'slug' => 'article-futur',
            'published_at' => now()->addDay(),
        ]);

    $featureImageUrl = Storage::disk('public')->url('articles/une.jpg');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('latestArticles', 5)
            ->where('latestArticles.0.title', 'Article 6')
            ->where('latestArticles.0.image_url', $featureImageUrl)
            ->where('latestArticles.0.game.cover_url', '//images.igdb.com/igdb/image/upload/t_thumb/jeu6.jpg')
            ->where('latestArticles.0.author.username', 'levelup-redaction')
            ->has('recentGames', 5)
            ->where('recentGames.0.title', 'Jeu 7')
            ->missing('latestArticles.5')
        );
});
