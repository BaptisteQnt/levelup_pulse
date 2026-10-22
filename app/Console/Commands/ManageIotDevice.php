<?php

namespace App\Console\Commands;

use App\Models\IotDevice;
use App\Models\User;
use App\Services\IotDeviceTokenManager;
use Illuminate\Console\Command;

class ManageIotDevice extends Command
{
    protected $signature = 'iot:device
        {action : create, renew, revoke, disable or enable}
        {device_id : Public device identifier}
        {--user= : Owner user ID or username; required when creating}';

    protected $description = 'Provision and manage an IoT device credential';

    public function handle(IotDeviceTokenManager $tokens): int
    {
        $action = strtolower((string) $this->argument('action'));
        $deviceId = strtoupper((string) $this->argument('device_id'));

        if (! preg_match('/\A[A-Z0-9][A-Z0-9_-]{0,63}\z/', $deviceId)) {
            $this->error('The device ID must contain only A-Z, 0-9, hyphens and underscores (64 characters maximum).');

            return self::FAILURE;
        }

        return match ($action) {
            'create' => $this->createDevice($deviceId, $tokens),
            'renew' => $this->renewToken($deviceId, $tokens),
            'revoke' => $this->revokeToken($deviceId),
            'disable' => $this->setActive($deviceId, false),
            'enable' => $this->setActive($deviceId, true),
            default => $this->invalidAction(),
        };
    }

    private function createDevice(string $deviceId, IotDeviceTokenManager $tokens): int
    {
        if (IotDevice::query()->where('device_id', $deviceId)->exists()) {
            $this->error("Device {$deviceId} already exists.");

            return self::FAILURE;
        }

        $owner = $this->resolveOwner();

        if (! $owner) {
            return self::FAILURE;
        }

        $plainTextToken = $tokens->generate();

        IotDevice::create([
            'user_id' => $owner->id,
            'device_id' => $deviceId,
            'token_hash' => $tokens->hash($plainTextToken),
            'is_active' => true,
            'token_rotated_at' => now(),
        ]);

        $this->info("Device {$deviceId} created for user {$owner->id}.");
        $this->warn('Copy this token now. It will not be displayed again:');
        $this->line($plainTextToken);

        return self::SUCCESS;
    }

    private function renewToken(string $deviceId, IotDeviceTokenManager $tokens): int
    {
        $device = $this->findDevice($deviceId);

        if (! $device) {
            return self::FAILURE;
        }

        $plainTextToken = $tokens->generate();
        $device->update([
            'token_hash' => $tokens->hash($plainTextToken),
            'token_rotated_at' => now(),
            'revoked_at' => null,
        ]);

        $this->info("Token renewed for {$deviceId}. The previous token is invalid.");
        $this->warn('Copy this token now. It will not be displayed again:');
        $this->line($plainTextToken);

        return self::SUCCESS;
    }

    private function revokeToken(string $deviceId): int
    {
        $device = $this->findDevice($deviceId);

        if (! $device) {
            return self::FAILURE;
        }

        $device->update(['revoked_at' => now()]);
        $this->info("Access revoked for {$deviceId}.");

        return self::SUCCESS;
    }

    private function setActive(string $deviceId, bool $active): int
    {
        $device = $this->findDevice($deviceId);

        if (! $device) {
            return self::FAILURE;
        }

        if ($active && $device->revoked_at !== null) {
            $this->error('A revoked device must receive a renewed token before it can be used again.');

            return self::FAILURE;
        }

        $device->update(['is_active' => $active]);
        $this->info("Device {$deviceId} ".($active ? 'enabled' : 'disabled').'.');

        return self::SUCCESS;
    }

    private function resolveOwner(): ?User
    {
        $reference = (string) $this->option('user');

        if ($reference === '') {
            $this->error('The --user option is required when creating a device.');

            return null;
        }

        $owner = ctype_digit($reference)
            ? User::query()->find((int) $reference)
            : User::query()->where('username', $reference)->first();

        if (! $owner) {
            $this->error("No user matches {$reference}.");
        }

        return $owner;
    }

    private function findDevice(string $deviceId): ?IotDevice
    {
        $device = IotDevice::query()->where('device_id', $deviceId)->first();

        if (! $device) {
            $this->error("Device {$deviceId} does not exist.");
        }

        return $device;
    }

    private function invalidAction(): int
    {
        $this->error('Action must be one of: create, renew, revoke, disable, enable.');

        return self::FAILURE;
    }
}
