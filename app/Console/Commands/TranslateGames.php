<?php
namespace App\Console\Commands;

use App\Jobs\TranslateGameTexts;
use App\Models\Game;
use Illuminate\Console\Command;

class TranslateGames extends Command
{
    protected $signature = 'games:translate 
        {gameId? : ID du jeu (optionnel si --slug)} 
        {--slug= : Slug du jeu}
        {--all : Traduire tous les jeux ayant un summary/storyline}';

    protected $description = 'Traduit les textes IGDB des jeux (summary/storyline) en FR via DeepL';

    public function handle(): int
    {
        // --all : dispatch sur tous
        if ($this->option('all')) {
            $connection = config('queue.default');
            $driver = config("queue.connections.{$connection}.driver", $connection);
            $runSynchronously = $driver === 'sync';

            $ids = Game::query()
                ->where(function($q) {
                    $q->whereNotNull('summary')
                        ->orWhereNotNull('storyline')
                        ->orWhereNotNull('description');
                })
                ->pluck('id');

            $ids->each(function ($gid) use ($runSynchronously) {
                if ($runSynchronously) {
                    TranslateGameTexts::dispatchSync($gid);
                } else {
                    TranslateGameTexts::dispatch($gid);
                }
            });

            if ($ids->isEmpty()) {
                $this->info('Aucun jeu à traduire.');
            } elseif ($runSynchronously) {
                $this->info(sprintf(
                    'Traductions exécutées immédiatement pour %d jeux (queue=sync).',
                    $ids->count()
                ));
            } else {
                $this->info(sprintf(
                    'Jobs de traduction dispatchés pour %d jeux. Lance un worker (php artisan queue:work).',
                    $ids->count()
                ));
            }

            return self::SUCCESS;
        }

        // --slug=... prioritaire si fourni
        if ($slug = $this->option('slug')) {
            $game = Game::where('slug', $slug)->first();
            if (!$game) {
                $this->error("Aucun jeu avec le slug '{$slug}'.");
                return self::FAILURE;
            }
            TranslateGameTexts::dispatchSync($game->id);
            $this->info("Jeu '{$slug}' (ID {$game->id}) traduit (sync).");
            return self::SUCCESS;
        }

        // Sinon on prend l’argument ID
        $id = (int) $this->argument('gameId');
        if ($id <= 0) {
            $this->error('Fournis un {gameId} ou --slug=...');
            return self::INVALID;
        }

        // On évite le findOrFail qui jette une exception CLI
        $game = Game::find($id);
        if (!$game) {
            $this->error("Aucun jeu avec l'ID {$id}.");
            return self::FAILURE;
        }

        TranslateGameTexts::dispatchSync($game->id);
        $this->info("Jeu {$id} traduit (sync).");
        return self::SUCCESS;
    }
}
