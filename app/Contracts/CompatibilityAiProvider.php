<?php

namespace App\Contracts;

use App\Models\Game;

interface CompatibilityAiProvider
{
    /**
     * @return array<string, mixed>
     */
    public function researchRequirements(Game $game, int $userId): array;

    /**
     * @param  array<string, mixed>  $hardware
     * @param  array<string, mixed>  $requirements
     * @return array<string, mixed>
     */
    public function analyze(Game $game, array $hardware, array $requirements, int $userId): array;
}
