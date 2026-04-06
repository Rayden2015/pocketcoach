<?php

namespace Tests\Feature\Api;

use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminProgramsApiTest extends TestCase
{
    use RefreshDatabase;

    private function staffUserForTenant(Tenant $tenant, string $role = 'owner'): User
    {
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }

    public function test_staff_can_create_list_show_update_delete_program(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $user = $this->staffUserForTenant($tenant);
        Sanctum::actingAs($user);

        $create = $this->postJson("/api/v1/tenants/{$tenant->slug}/admin/programs", [
            'title' => 'Alpha',
            'summary' => 'Sum',
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $create->assertCreated()->assertJsonPath('data.title', 'Alpha');
        $id = $create->json('data.id');

        $this->getJson("/api/v1/tenants/{$tenant->slug}/admin/programs")
            ->assertOk()
            ->assertJsonFragment(['title' => 'Alpha']);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/admin/programs/{$id}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'alpha');

        $this->putJson("/api/v1/tenants/{$tenant->slug}/admin/programs/{$id}", [
            'title' => 'Beta',
            'is_published' => false,
        ])->assertOk()->assertJsonPath('data.title', 'Beta');

        $this->deleteJson("/api/v1/tenants/{$tenant->slug}/admin/programs/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('programs', ['id' => $id]);
    }

    public function test_learner_cannot_create_program(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/tenants/{$tenant->slug}/admin/programs", [
            'title' => 'Nope',
        ])->assertForbidden();
    }

    public function test_show_returns_404_for_other_tenant_program(): void
    {
        $tenantA = Tenant::query()->create(['name' => 'A', 'slug' => 'a']);
        $tenantB = Tenant::query()->create(['name' => 'B', 'slug' => 'b']);
        $programB = Program::query()->create([
            'tenant_id' => $tenantB->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        Sanctum::actingAs($this->staffUserForTenant($tenantA));

        $this->getJson("/api/v1/tenants/{$tenantA->slug}/admin/programs/{$programB->id}")
            ->assertNotFound();
    }
}
