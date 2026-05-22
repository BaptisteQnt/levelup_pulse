<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IGDBService;
use App\Models\Game;
use App\Jobs\TranslateGameTexts;
use Illuminate\Support\Str;

class ImportGamesFromIGDB extends Command
{
    protected $signature = 'games:import {search}';
    protected $description = 'Import games from IGDB by keyword';

    public function handle(IGDBService $igdb): int
    {
        $games = $igdb->fetchGames($this->argument('search'));

        foreach ($games as $g) {
            $summary = $g['summary'] ?? null;
            $storyline = $g['storyline'] ?? null;

            $game = Game::updateOrCreate(
                ['slug' => Str::slug($g['name'])],
                [
                    'title' => $g['name'],
                    'twitch_id' => $g['id'],
                    'cover_url' => $g['cover']['url'] ?? null,
                    'summary' => $summary,
                    'storyline' => $storyline,
                    'description' => $storyline ?? $summary ?? null,
                ]
            );

            if (filled($game->summary) || filled($game->storyline) || filled($game->description)) {
                TranslateGameTexts::dispatchSync($game->id);
            }
        }

        $this->info('Games imported successfully (translations dispatched).');
        return Command::SUCCESS;
    }
}
