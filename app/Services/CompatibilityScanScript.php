<?php

namespace App\Services;

use App\Models\CompatibilityScan;
use RuntimeException;

class CompatibilityScanScript
{
    public function render(CompatibilityScan $scan, string $plainToken): string
    {
        $template = file_get_contents(resource_path('scripts/levelup-pc-scan.ps1'));

        if ($template === false) {
            throw new RuntimeException('Compatibility scan script template is unavailable.');
        }

        return str_replace(
            ['__SCAN_UUID__', '__SCAN_TOKEN__', '__UPLOAD_URL__', '__RESULT_URL__'],
            [
                $this->powerShellLiteral($scan->uuid),
                $this->powerShellLiteral($plainToken),
                $this->powerShellLiteral(route('api.compatibility-scans.hardware.store', $scan)),
                $this->powerShellLiteral(route('games.show', [
                    'slug' => $scan->game->slug,
                    'scan' => $scan->uuid,
                ]).'#compatibility-scan'),
            ],
            $template,
        );
    }

    private function powerShellLiteral(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
