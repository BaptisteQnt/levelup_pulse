<?php

use App\Jobs\AnalyzeCompatibilityScan;
use App\Models\CompatibilityScan;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('requires authentication to create a compatibility scan', function () {
    $game = Game::factory()->create();

    $this->post(route('games.compatibility-scans.store', $game))
        ->assertRedirect(route('login'));
});

it('creates a personalized powershell script without exposing the OpenAI key', function () {
    config(['compatibility.openai.api_key' => 'secret-openai-key']);
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Sample Game', 'slug' => 'sample-game']);

    $response = $this->actingAs($user)
        ->post(route('games.compatibility-scans.store', $game));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/x-powershell; charset=UTF-8');

    $scanId = $response->headers->get('X-Scan-ID');
    $script = $response->getContent();

    expect($scanId)->not->toBeNull()
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('X-Script-SHA256'))->toBe(hash('sha256', $script))
        ->and($script)->toContain($scanId)
        ->and($script)->toContain('X-LevelUp-Scan-Token')
        ->and($script)->not->toContain('secret-openai-key')
        ->and($script)->not->toContain('__SCAN_TOKEN__');

    $this->assertDatabaseHas('compatibility_scans', [
        'uuid' => $scanId,
        'user_id' => $user->id,
        'game_id' => $game->id,
        'status' => CompatibilityScan::STATUS_CREATED,
    ]);
});

it('limits script creation to three scans per user and hour', function () {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    RateLimiter::clear((string) $user->id);

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->actingAs($user)
            ->post(route('games.compatibility-scans.store', $game))
            ->assertOk();
    }

    $this->actingAs($user)
        ->post(route('games.compatibility-scans.store', $game))
        ->assertTooManyRequests();
});

it('accepts one valid hardware upload and dispatches the analysis', function () {
    Queue::fake();
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $scriptResponse = $this->actingAs($user)
        ->post(route('games.compatibility-scans.store', $game));

    $scanId = $scriptResponse->headers->get('X-Scan-ID');
    preg_match("/\\\$scanToken = '([^']+)'/", $scriptResponse->getContent(), $matches);
    $token = $matches[1] ?? null;

    expect($token)->not->toBeNull();

    $uploadUrl = route('api.compatibility-scans.hardware.store', $scanId);
    $this->withHeader('X-LevelUp-Scan-Token', $token)
        ->postJson($uploadUrl, compatibilityHardwarePayload())
        ->assertAccepted()
        ->assertJsonPath('status', CompatibilityScan::STATUS_UPLOADED);

    Queue::assertPushed(AnalyzeCompatibilityScan::class);

    $scan = CompatibilityScan::where('uuid', $scanId)->firstOrFail();
    expect($scan->token_hash)->toBeNull()
        ->and($scan->hardware_payload['cpu'][0]['name'])->toBe('AMD Ryzen 5 5600X');

    $this->withHeader('X-LevelUp-Scan-Token', $token)
        ->postJson($uploadUrl, compatibilityHardwarePayload())
        ->assertConflict();
});

it('rejects invalid, expired and oversized hardware uploads', function () {
    Queue::fake();
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $scan = CompatibilityScan::create([
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'game_id' => $game->id,
        'token_hash' => hash('sha256', 'valid-token'),
        'status' => CompatibilityScan::STATUS_CREATED,
        'upload_expires_at' => now()->subMinute(),
        'purge_at' => now()->addDay(),
    ]);

    $url = route('api.compatibility-scans.hardware.store', $scan);

    $this->withHeader('X-LevelUp-Scan-Token', 'invalid-token')
        ->postJson($url, compatibilityHardwarePayload())
        ->assertGone();

    $freshScan = CompatibilityScan::create([
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'game_id' => $game->id,
        'token_hash' => hash('sha256', 'valid-token'),
        'status' => CompatibilityScan::STATUS_CREATED,
        'upload_expires_at' => now()->addMinutes(15),
        'purge_at' => now()->addDay(),
    ]);

    $this->withHeader('X-LevelUp-Scan-Token', 'invalid-token')
        ->postJson(route('api.compatibility-scans.hardware.store', $freshScan), compatibilityHardwarePayload())
        ->assertUnauthorized();

    $payloadWithIdentifier = compatibilityHardwarePayload();
    $payloadWithIdentifier['hostname'] = 'PRIVATE-PC-NAME';
    $this->withHeader('X-LevelUp-Scan-Token', 'valid-token')
        ->postJson(route('api.compatibility-scans.hardware.store', $freshScan), $payloadWithIdentifier)
        ->assertUnprocessable();

    config(['compatibility.scan.max_payload_bytes' => 10]);
    $this->withHeader('X-LevelUp-Scan-Token', 'valid-token')
        ->postJson(route('api.compatibility-scans.hardware.store', $freshScan), compatibilityHardwarePayload())
        ->assertStatus(413);
});

it('only exposes a scan result to its owner and game', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $game = Game::factory()->create();
    $otherGame = Game::factory()->create();
    $scan = CompatibilityScan::create([
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'user_id' => $owner->id,
        'game_id' => $game->id,
        'status' => CompatibilityScan::STATUS_COMPLETED,
        'result_payload' => ['verdict' => 'high', 'summary' => 'Compatible'],
        'requirements_payload' => ['sources' => [['title' => 'Official', 'url' => 'https://example.com', 'publisher' => null]]],
        'upload_expires_at' => now()->subMinute(),
        'purge_at' => now()->addDay(),
        'completed_at' => now(),
    ]);

    $this->actingAs($owner)
        ->getJson(route('games.compatibility-scans.show', [$game, $scan]))
        ->assertOk()
        ->assertJsonPath('result.verdict', 'high')
        ->assertJsonCount(1, 'sources');

    $this->actingAs($stranger)
        ->getJson(route('games.compatibility-scans.show', [$game, $scan]))
        ->assertNotFound();

    $this->actingAs($owner)
        ->getJson(route('games.compatibility-scans.show', [$otherGame, $scan]))
        ->assertNotFound();
});

it('purges compatibility scans after their retention period', function () {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $expired = CompatibilityScan::create([
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'game_id' => $game->id,
        'status' => CompatibilityScan::STATUS_COMPLETED,
        'upload_expires_at' => now()->subDay(),
        'purge_at' => now()->subMinute(),
    ]);
    $active = CompatibilityScan::create([
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'game_id' => $game->id,
        'status' => CompatibilityScan::STATUS_CREATED,
        'upload_expires_at' => now()->addMinutes(10),
        'purge_at' => now()->addDay(),
    ]);

    $this->artisan('compatibility-scans:purge')->assertSuccessful();

    $this->assertDatabaseMissing('compatibility_scans', ['id' => $expired->id]);
    $this->assertDatabaseHas('compatibility_scans', ['id' => $active->id]);
});
