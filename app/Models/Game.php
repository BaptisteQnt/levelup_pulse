<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'twitch_id',
        'cover_url',
        'description',
        'summary',
        'storyline',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tips()
    {
        return $this->hasMany(Tip::class);
    }

    public function ratings()
    {
        return $this->hasMany(GameRating::class);
    }

    public function translatedDescription(string $lang = 'en'): ?string
    {
        $texts = $this->localizedTexts($lang);

        $parts = array_filter([
            $texts['storyline'] ?? null,
            $texts['summary'] ?? null,
        ], fn ($value) => filled($value));

        return empty($parts) ? null : implode("\n\n", $parts);
    }

    /**
     * Retourne les textes (storyline/summary) adaptés à la langue.
     */
    public function localizedTexts(string $lang = 'en'): array
    {
        if ($lang === 'fr') {
            $tr = \App\Models\GameTranslation::where('game_id', $this->id)
                ->where('lang', 'fr')
                ->first();

            if ($tr && (filled($tr->storyline) || filled($tr->summary))) {
                return [
                    'storyline' => filled($tr->storyline) ? $tr->storyline : null,
                    'summary'   => filled($tr->summary) ? $tr->summary : null,
                ];
            }
        }

        if (filled($this->storyline) || filled($this->summary)) {
            return [
                'storyline' => filled($this->storyline) ? $this->storyline : null,
                'summary'   => filled($this->summary) ? $this->summary : null,
            ];
        }

        $fallback = filled($this->description) ? trim((string) $this->description) : null;

        return [
            'storyline' => null,
            'summary'   => $fallback,
        ];
    }

}


