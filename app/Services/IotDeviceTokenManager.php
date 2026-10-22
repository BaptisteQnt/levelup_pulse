<?php

namespace App\Services;

class IotDeviceTokenManager
{
    public function generate(): string
    {
        $bytes = max(32, (int) config('iot.token_bytes', 32));

        return 'lup_iot_'.bin2hex(random_bytes($bytes));
    }

    public function hash(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
