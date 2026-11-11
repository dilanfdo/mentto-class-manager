<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'secret123', 'role' => 'platform_admin']);
    }

    private function client(string $name = 'Amila Maths', string $type = 'tutor'): Tenant
    {
        return app(TenantProvisioner::class)->create([
            'name' => $name, 'type' => $type, 'contact_name' => 'Owner', 'contact_email' => strtolower(str_replace(' ', '', $name)).'@example.test',
        ], 'secret123');
    }

    public function test_admin_creates_a_client_with_default_features_and_owner_login(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson('/api/admin/tenants', [
            'name' => 'Bright Future Institute', 'type' => 'institute', 'contact_name' => 'Office',
            'contact_email' => 'office@example.test', 'owner_password' => 'secret123',
        ])->assertCreated();

        $features = $response->json('data.features');
        $this->assertContains('halls', $features);
        $this->assertContains('staff', $features);
        $this->assertNotContains('sms', $features);
        $this->assertSame('bright-future-institute', $response->json('data.slug'));
        $this->assertDatabaseHas('users', ['email' => 'office@example.test', 'role' => 'owner']);
    }

    public function test_admin_lists_clients_with_features_students_and_monthly_estimate(): void
    {
        Sanctum::actingAs($this->admin());
        $this->client();

        $row = $this->getJson('/api/admin/tenants')->assertOk()->json('data.0');

        $this->assertSame('Amila Maths', $row['name']);
        $this->assertSame(['exams'], $row['addons']);
        // Starter 5,500 + exams 1,000 + fixed 2,500, matching the public quote
        $this->assertSame(9000, $row['monthly_estimate']);
    }

    public function test_admin_switches_a_feature_off_and_the_client_loses_access(): void
    {
        $tenant = $this->client();
        Sanctum::actingAs($this->admin());

        $features = $this->putJson("/api/admin/tenants/$tenant->id/features/exams", ['enabled' => false])->assertOk()->json('features');
        $this->assertNotContains('exams', $features);
        $this->assertDatabaseHas('tenant_features', ['tenant_id' => $tenant->id, 'feature' => 'exams', 'enabled' => false]);

        Sanctum::actingAs($tenant->users()->first());
        $this->getJson('/api/exams')->assertForbidden();
        $this->assertNotContains('exams', $this->getJson('/api/me')->assertOk()->json('features'));
    }

    public function test_admin_switches_an_addon_on(): void
    {
        $tenant = $this->client();
        Sanctum::actingAs($this->admin());

        $features = $this->putJson("/api/admin/tenants/$tenant->id/features/sms", ['enabled' => true])->assertOk()->json('features');

        $this->assertContains('sms', $features);
    }

    public function test_locked_and_unknown_features_cannot_be_changed(): void
    {
        $tenant = $this->client();
        Sanctum::actingAs($this->admin());

        $this->putJson("/api/admin/tenants/$tenant->id/features/students", ['enabled' => false])->assertStatus(422);
        $this->putJson("/api/admin/tenants/$tenant->id/features/teleport", ['enabled' => true])->assertNotFound();
    }

    public function test_suspending_a_client_blocks_their_app(): void
    {
        $tenant = $this->client();
        Sanctum::actingAs($this->admin());
        $this->patchJson("/api/admin/tenants/$tenant->id", ['status' => 'suspended'])->assertOk();

        Sanctum::actingAs($tenant->users()->first());
        $this->getJson('/api/students')->assertForbidden();
    }

    public function test_overview_counts_clients_and_feature_adoption(): void
    {
        $this->client('One');
        $this->client('Two', 'institute');
        Sanctum::actingAs($this->admin());

        $overview = $this->getJson('/api/admin/overview')->assertOk()->json();

        $this->assertSame(2, $overview['clients']['total']);
        $halls = collect($overview['feature_adoption'])->firstWhere('key', 'halls');
        $this->assertSame(1, $halls['clients']);
    }

    public function test_client_users_cannot_reach_admin_routes(): void
    {
        Sanctum::actingAs($this->client()->users()->first());

        $this->getJson('/api/admin/tenants')->assertForbidden();
    }
}
