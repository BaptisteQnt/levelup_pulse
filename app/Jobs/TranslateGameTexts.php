<?php
namespace App\Jobs;

use App\Models\Game;
use App\Models\GameTranslation;
use App\Services\Translator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranslateGameTexts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $gameId) {}

    public function handle(Translator $translator): void
    {
        $game = Game::findOrFail($this->gameId);

        $storyline = trim((string) ($game->storyline ?? ''));
        $summary   = trim((string) ($game->summary ?? ''));

        if ($storyline === '' && $summary === '') {
            $summary = trim((string) ($game->description ?? ''));
        }

        $payload = [
            'storyline' => $storyline !== '' ? $storyline : null,
            'summary'   => $summary !== '' ? $summary : null,
        ];

        if ($payload['storyline'] === null && $payload['summary'] === null) {
            return; // rien à traduire
        }

        $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
        $existing = GameTranslation::where('game_id', $game->id)
            ->where('lang', 'fr')
            ->first();

        if ($existing && $existing->source_hash === $hash) {
            return; // déjà à jour
        }

        $translatedStoryline = $this->translateField($payload['storyline'], $translator);
        $translatedSummary   = $this->translateField($payload['summary'], $translator);

        GameTranslation::updateOrCreate(
            ['game_id' => $game->id, 'lang' => 'fr'],
            [
                'summary'     => $translatedSummary,
                'storyline'   => $translatedStoryline,
                'provider'    => class_basename($translator),
                'source_hash' => $hash,
            ]
        );
    }

    private function translateField(?string $text, Translator $translator): ?string
    {
        if ($text === null) {
            return null;
        }

        $chunks = $this->chunkText($text, 4000);
        if (empty($chunks)) {
            return null;
        }

        return collect($chunks)->map(
            fn ($chunk) => $translator->translate($chunk, 'fr', 'en')
        )->implode("\n");
    }

    private function chunkText(string $text, int $limit): array
    {
        // coupe proprement aux sauts de ligne quand possible
        $out = [];
        $current = '';
        foreach (preg_split("/(\r?\n){1,}/", $text) as $para) {
            $candidate = $current === '' ? $para : $current . "\n\n" . $para;
            if (mb_strlen($candidate) <= $limit) {
                $current = $candidate;
            } else {
                if ($current !== '') $out[] = $current;
                $current = $para;
                if (mb_strlen($current) > $limit) {
                    // hard split si un paragraphe est géant
                    $out = array_merge($out, str_split($current, $limit));
                    $current = '';
                }
            }
        }
        if ($current !== '') $out[] = $current;
        return $out;
    }
}
