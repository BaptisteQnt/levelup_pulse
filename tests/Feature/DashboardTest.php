<?php

use App\Models\Article;
use App\Models\Game;
use App\Models\IotDevice;
use App\Models\IotTelemetryAiAnalysis;
use App\Models\IotTelemetryMeasurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

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

test('dashboard exposes only the latest telemetry owned by the authenticated user', function () {
    config()->set('iot.display.safe_temperature_min', 5);
    config()->set('iot.display.safe_temperature_max', 35);

    $user = User::factory()->create();
    $ownDevice = IotDevice::factory()->for($user)->create(['device_id' => 'CAPTEUR-SALON']);
    $otherDevice = IotDevice::factory()->create(['device_id' => 'CAPTEUR-VOISIN']);

    $ownMeasurement = IotTelemetryMeasurement::factory()->for($ownDevice, 'device')->create([
        'temperature' => 23.40,
        'humidity' => 48.20,
        'received_at' => now()->subMinute(),
    ]);
    $ownMeasurement->aiAnalysis()->create([
        'status' => IotTelemetryAiAnalysis::STATUS_COMPLETED,
        'reliability' => IotTelemetryAiAnalysis::RELIABILITY_RELIABLE,
        'approved_for_use' => true,
        'risk_level' => IotTelemetryAiAnalysis::RISK_LOW,
        'risk_summary' => 'Conditions thermiques normales.',
        'reliability_reason' => 'Mesure cohérente avec la série récente.',
        'recommendations' => ['Maintenir une ventilation normale.'],
        'disclaimer' => 'Analyse informative.',
        'analyzed_at' => now(),
    ]);
    IotTelemetryMeasurement::factory()->for($otherDevice, 'device')->create([
        'temperature' => 49,
        'received_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('latestTelemetry.device_id', 'CAPTEUR-SALON')
            ->where('latestTelemetry.temperature', 23.4)
            ->where('latestTelemetry.humidity', 48.2)
            ->where('latestTelemetry.status', 'safe')
            ->where('latestTelemetry.safe_temperature_min', 5)
            ->where('latestTelemetry.safe_temperature_max', 35)
            ->where('latestTelemetry.ai_analysis.reliability', 'reliable')
            ->where('latestTelemetry.ai_analysis.approved_for_use', true)
            ->where('latestTelemetry.ai_analysis.risk_level', 'low')
        );
});

test('latest telemetry endpoint flags a dangerous temperature and stays private', function () {
    $user = User::factory()->create();
    $device = IotDevice::factory()->for($user)->create(['device_id' => 'CAPTEUR-BUREAU']);

    IotTelemetryMeasurement::factory()->for($device, 'device')->create([
        'temperature' => 41.25,
        'humidity' => 37,
    ]);

    $response = $this->actingAs($user)->getJson(route('iot.telemetry.latest'));

    $response
        ->assertOk()
        ->assertHeader('Cache-Control')
        ->assertJsonPath('data.device_id', 'CAPTEUR-BUREAU')
        ->assertJsonPath('data.temperature', 41.25)
        ->assertJsonPath('data.status', 'danger');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
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
