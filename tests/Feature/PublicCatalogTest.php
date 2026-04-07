<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_catalog(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $this->get('/t/catalog')
            ->assertOk()
            ->assertSee('P', false);
    }

    public function test_unknown_tenant_slug_returns_not_found(): void
    {
        $this->get('/does-not-exist/catalog')->assertNotFound();
    }

    public function test_unpublished_programs_are_hidden(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Live',
            'slug' => 'live',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Draft prog',
            'slug' => 'draft',
            'sort_order' => 1,
            'is_published' => false,
        ]);

        $this->get('/t/catalog')
            ->assertOk()
            ->assertSee('Live', false)
            ->assertDontSee('Draft prog', false);
    }

    public function test_unpublished_courses_are_hidden(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Prog',
            'slug' => 'prog',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Visible course',
            'slug' => 'visible',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Secret course',
            'slug' => 'secret',
            'sort_order' => 1,
            'is_published' => false,
        ]);

        $this->get('/t/catalog')
            ->assertOk()
            ->assertSee('Visible course', false)
            ->assertDontSee('Secret course', false);
    }

    public function test_shows_empty_state_when_nothing_is_published(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Draft only',
            'slug' => 'draft',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->get('/t/catalog')
            ->assertOk()
            ->assertSee('Nothing published yet.', false)
            ->assertDontSee('Draft only', false);
    }

    public function test_published_program_with_only_unpublished_courses_lists_program_without_course_titles(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Outer',
            'slug' => 'outer',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Hidden inner',
            'slug' => 'hidden-inner',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->get('/t/catalog')
            ->assertOk()
            ->assertSee('Outer', false)
            ->assertDontSee('Hidden inner', false);
    }
}
