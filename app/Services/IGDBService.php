<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IGDBService
{
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $credentials = $this->loadCredentialsFromFile();
        $this->clientId = $credentials['client_id'];
        $this->clientSecret = $credentials['client_secret'];
    }

    /**
     * Charge les identifiants depuis le fichier `.twitch`
     */
    protected function loadCredentialsFromFile(): array
    {
        $path = base_path('.twitch');

        if (!file_exists($path)) {
            throw new \RuntimeException('Fichier .twitch manquant à la racine du projet.');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $credentials = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '#')) continue;
            [$key, $value] = explode('=', $line, 2);
            $credentials[trim($key)] = trim($value);
        }

        if (!isset($credentials['CLI'], $credentials['SECRET'])) {
            throw new \RuntimeException('CLIENT_ID ou CLIENT_SECRET manquant dans le fichier .twitch.');
        }

        return [
            'client_id' => $credentials['CLI'],
            'client_secret' => $credentials['SECRET'],
        ];
    }

    public function getAccessToken(): string
    {
        return Cache::remember('igdb_token', 3600, function () {
            $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ]);

            if (!$response->ok()) {
                throw new \RuntimeException('Erreur lors de la récupération du token : ' . $response->body());
            }

            return $response->json()['access_token'];
        });
    }

    public function fetchGames(string $search): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Client-ID' => $this->clientId,
            'Authorization' => 'Bearer ' . $token,
        ])->withBody("
            search \"{$search}\";
            fields id,name,slug,cover.url,summary,storyline;
            limit 5;
        ", 'text/plain')->post('https://api.igdb.com/v4/games');

        if (!$response->ok()) {
            throw new \RuntimeException('Erreur lors de la récupération des jeux : ' . $response->body());
        }

        return $response->json();
    }
}
