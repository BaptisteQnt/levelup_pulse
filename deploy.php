<?php

namespace Deployer;

require 'recipe/laravel.php';

/*
|--------------------------------------------------------------------------
| Informations du projet
|--------------------------------------------------------------------------
*/

set('application', 'LevelUP Pulse');
set('repository', 'git@github.com:BaptisteQnt/levelup_pulse.git');
set('branch', 'main');
set('keep_releases', 10);

set('composer_options', '--no-dev --prefer-dist --no-interaction --optimize-autoloader');

/*
|--------------------------------------------------------------------------
| Fichiers et dossiers partagés
|--------------------------------------------------------------------------
*/

set('shared_files', [
    '.env',
    '.twitch',
]);

set('shared_dirs', [
    'storage',
]);

set('writable_dirs', [
    'storage',
    'bootstrap/cache',
]);

/*
|--------------------------------------------------------------------------
| Serveur de production
|--------------------------------------------------------------------------
*/

host('production')
    ->setHostname('levelup-vps')
    ->setRemoteUser('deploy')
    ->setDeployPath('/var/www/levelup-pulse');

/*
|--------------------------------------------------------------------------
| Build des assets
|--------------------------------------------------------------------------
*/

task('build:assets', function () {
    run('cd {{release_path}} && npm ci');
    run('cd {{release_path}} && npm run build');
});

before('artisan:optimize', 'build:assets');

/*
|--------------------------------------------------------------------------
| Sécurité
|--------------------------------------------------------------------------
*/

after('deploy:failed', 'deploy:unlock');