<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Game;

class DummyGameSeeder extends Seeder
{
    public function run(): void
    {
        // adapte les colonnes si besoin
        // Game::updateOrCreate(
        //     ['slug' => 'starfield'],
        //     [
        //         'title'       => 'Starfield',
        //         'cover_url'   => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co2x9k.jpg',
        //         'storyline'   => 'In the year 2330 humanity ventures beyond the solar system as part of the spacefaring organization Constellation.',
        //         'summary'     => 'Starfield is a next-generation role-playing game set among the stars. Create any character you want and explore with unparalleled freedom as you embark on an epic journey to answer humanity’s greatest mystery.',
        //         'description' => 'In the year 2330 humanity ventures beyond the solar system as part of the spacefaring organization Constellation.'
        //     ]
        // );

        // Game::updateOrCreate(
        //     ['slug' => 'elden-ring'],
        //     [
        //         'title'       => 'ELDEN RING',
        //         'cover_url'   => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co1x3h.jpg',
        //         'storyline'   => 'Guided by the Greater Will, the Tarnished traverse the Lands Between to seek the shattered fragments of the Elden Ring.',
        //         'summary'     => 'Rise, Tarnished, and be guided by grace to brandish the power of the Elden Ring and become an Elden Lord in the Lands Between.',
        //         'description' => 'Guided by the Greater Will, the Tarnished traverse the Lands Between to seek the shattered fragments of the Elden Ring.'
        //     ]
        // );
    }
}
