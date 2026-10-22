<?php

use App\Models\IotDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a device for an existing owner with only a token hash stored', function () {
    $user = User::factory()->create();

    $this->artisan('iot:device', [
        'action' => 'create',
        'device_id' => 'LUP-ESP32-001',
        '--user' => (string) $user->id,
    ])->assertSuccessful();

    $device = IotDevice::query()->where('device_id', 'LUP-ESP32-001')->firstOrFail();

    expect($device->user_id)->toBe($user->id)
        ->and($device->token_hash)->toHaveLength(64)
        ->and($device->token_hash)->not->toStartWith('lup_iot_')
        ->and($device->is_active)->toBeTrue();
});

it('renews and revokes a device token', function () {
    $device = IotDevice::factory()->create([
        'device_id' => 'LUP-ESP32-001',
        'revoked_at' => now(),
    ]);
    $oldHash = $device->token_hash;

    $this->artisan('iot:device', [
        'action' => 'renew',
        'device_id' => $device->device_id,
    ])->assertSuccessful();

    $device->refresh();
    expect($device->token_hash)->not->toBe($oldHash)
        ->and($device->revoked_at)->toBeNull();

    $this->artisan('iot:device', [
        'action' => 'revoke',
        'device_id' => $device->device_id,
    ])->assertSuccessful();

    expect($device->refresh()->revoked_at)->not->toBeNull();
});

it('disables and enables a non-revoked device', function () {
    $device = IotDevice::factory()->create(['device_id' => 'LUP-ESP32-001']);

    $this->artisan('iot:device', [
        'action' => 'disable',
        'device_id' => $device->device_id,
    ])->assertSuccessful();
    expect($device->refresh()->is_active)->toBeFalse();

    $this->artisan('iot:device', [
        'action' => 'enable',
        'device_id' => $device->device_id,
    ])->assertSuccessful();
    expect($device->refresh()->is_active)->toBeTrue();
});
