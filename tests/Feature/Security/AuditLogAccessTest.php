<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_admin_cannot_access_security_audit_logs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('security.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_security_officer_can_access_security_audit_logs(): void
    {
        $securityOfficer = User::factory()->create(['is_security_officer' => true]);

        $this->actingAs($securityOfficer)
            ->get(route('security.audit-logs.index'))
            ->assertOk();
    }

    public function test_super_admin_can_access_security_audit_logs(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($superAdmin)
            ->get(route('security.audit-logs.index'))
            ->assertOk();
    }
}
