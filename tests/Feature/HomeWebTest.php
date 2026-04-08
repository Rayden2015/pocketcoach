<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_spaces_with_published_programs(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Open Space', 'slug' => 'open']);
        Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Public program',
            'slug' => 'pp',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        Tenant::query()->create(['name' => 'Hidden Space', 'slug' => 'hidden']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Open Space', false)
            ->assertSee('Browse catalog', false)
            ->assertDontSee('Hidden Space', false);
    }

    public function test_home_hides_space_without_published_program(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Draft Only', 'slug' => 'draft']);
        Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Draft',
            'slug' => 'd',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Draft Only', false);
    }
}
