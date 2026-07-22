<?php

namespace App\Console\Commands;

use App\Models\CompatibilityScan;
use Illuminate\Console\Command;

class PurgeCompatibilityScans extends Command
{
    protected $signature = 'compatibility-scans:purge';

    protected $description = 'Delete expired PC compatibility scan data';

    public function handle(): int
    {
        $deleted = CompatibilityScan::query()
            ->where('purge_at', '<=', now())
            ->delete();

        $this->info("{$deleted} compatibility scan(s) deleted.");

        return self::SUCCESS;
    }
}
