<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function compatibilityHardwarePayload(): array
{
    return [
        'schema_version' => 1,
        'collected_at' => now()->toIso8601String(),
        'os' => [
            'caption' => 'Microsoft Windows 11 Pro',
            'version' => '10.0.26100',
            'architecture' => '64-bit',
            'directx_version' => '4.09.00.0904',
        ],
        'cpu' => [[
            'name' => 'AMD Ryzen 5 5600X',
            'cores' => 6,
            'logical_processors' => 12,
            'max_clock_mhz' => 4600,
        ]],
        'gpu' => [[
            'name' => 'NVIDIA GeForce RTX 3060',
            'vram_bytes' => 12_884_901_888,
            'vram_is_estimate' => true,
            'driver_version' => '32.0.15.6094',
        ]],
        'memory' => ['total_bytes' => 17_179_869_184],
        'storage' => [
            'volumes' => [[
                'drive' => 'C:',
                'filesystem' => 'NTFS',
                'total_bytes' => 1_000_000_000_000,
                'free_bytes' => 500_000_000_000,
            ]],
            'physical_disks' => [[
                'model' => 'NVMe SSD',
                'media_type' => 'SSD',
                'total_bytes' => 1_000_000_000_000,
            ]],
        ],
    ];
}
