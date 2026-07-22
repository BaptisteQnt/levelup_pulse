<?php

use App\Contracts\CompatibilityAiProvider;
use App\Jobs\AnalyzeCompatibilityScan;
use App\Models\CompatibilityScan;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs requirement research and compatibility analysis in order', function () {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'Test Game']);
    $scan = CompatibilityScan::create([
        'uuid' => (string) Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'game_id' => $game->id,
        'status' => CompatibilityScan::STATUS_UPLOADED,
        'hardware_payload' => compatibilityHardwarePayload(),
        'uploaded_at' => now(),
        'upload_expires_at' => now()->addMinutes(15),
        'purge_at' => now()->addDay(),
    ]);

    $provider = new class implements CompatibilityAiProvider
    {
        public array $calls = [];

        public function researchRequirements(Game $game, int $userId): array
        {
            $this->calls[] = 'research';

            return [
                'minimum' => ['cpu' => 'Ryzen 5'],
                'recommended' => ['cpu' => 'Ryzen 7'],
                'sources' => [['title' => 'Official', 'url' => 'https://example.com', 'publisher' => null]],
            ];
        }

        public function analyze(Game $game, array $hardware, array $requirements, int $userId): array
        {
            $this->calls[] = 'analyze';

            return [
                'verdict' => 'medium',
                'summary' => 'Le jeu devrait fonctionner en qualité moyenne.',
                'component_checks' => [],
                'bottlenecks' => [],
                'advice' => [],
                'disclaimer' => 'Estimation IA.',
            ];
        }
    };

    (new AnalyzeCompatibilityScan($scan->id))->handle($provider);

    $scan->refresh();
    expect($provider->calls)->toBe(['research', 'analyze'])
        ->and($scan->status)->toBe(CompatibilityScan::STATUS_COMPLETED)
        ->and($scan->result_payload['verdict'])->toBe('medium')
        ->and($scan->hardware_payload)->toBeNull()
        ->and($scan->researched_at)->not->toBeNull()
        ->and($scan->completed_at)->not->toBeNull();
});
