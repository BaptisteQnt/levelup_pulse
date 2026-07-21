<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TelescopeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_admin_cannot_view_telescope(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertFalse(Gate::forUser($admin)->allows('viewTelescope'));
    }

    public function test_security_officer_can_view_telescope(): void
    {
        $securityOfficer = User::factory()->create(['is_security_officer' => true]);

        $this->assertTrue(Gate::forUser($securityOfficer)->allows('viewTelescope'));
    }

    public function test_super_admin_can_view_telescope(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewTelescope'));
    }
}
