<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoachWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_program_via_browser(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Coach Space', 'slug' => 'cs']);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $this->post('/cs/coach/programs', [
            'title' => 'My program',
            'summary' => 'Hello',
            'sort_order' => 0,
            'is_published' => '1',
        ])->assertRedirect(route('coach.programs.index', $tenant));

        $this->assertDatabaseHas('programs', [
            'tenant_id' => $tenant->id,
            'title' => 'My program',
            'is_published' => true,
        ]);
    }

    public function test_learner_cannot_post_program(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);

        $this->actingAs($user);

        $this->post('/t/coach/programs', [
            'title' => 'X',
        ])->assertForbidden();
    }

    public function test_staff_can_create_course_under_program(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->staffWithTenant('cs');
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Prog',
            'slug' => 'prog',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->actingAs($user);

        $this->post("/{$tenant->slug}/coach/courses", [
            'program_id' => $program->id,
            'title' => 'Foundations',
            'summary' => 'Intro',
            'sort_order' => 1,
            'is_published' => '1',
        ])->assertRedirect(route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $program->id]));

        $this->assertDatabaseHas('courses', [
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Foundations',
            'slug' => 'foundations',
            'is_published' => true,
        ]);
    }

    public function test_staff_can_create_module_under_course(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->staffWithTenant('cs');
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 0,
            'is_published' => false,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->actingAs($user);

        $this->post("/{$tenant->slug}/coach/modules", [
            'course_id' => $course->id,
            'title' => 'Week one',
            'sort_order' => 2,
            'is_published' => '1',
        ])->assertRedirect(route('coach.modules.index', ['tenant' => $tenant, 'course_id' => $course->id]));

        $this->assertDatabaseHas('modules', [
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'Week one',
            'slug' => 'week-one',
            'is_published' => true,
        ]);
    }

    public function test_staff_can_create_lesson_under_module(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->staffWithTenant('cs');
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 0,
            'is_published' => false,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 0,
            'is_published' => false,
        ]);
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->actingAs($user);

        $this->get("/{$tenant->slug}/coach/modules")->assertOk()->assertSee('Pick a course', false);
        $this->get("/{$tenant->slug}/coach/lessons")->assertOk()->assertSee('inside a module', false);

        $this->post("/{$tenant->slug}/coach/lessons", [
            'module_id' => $module->id,
            'title' => 'Welcome',
            'body' => 'Hello learner.',
            'lesson_type' => 'text',
            'material_source' => 'none',
            'sort_order' => 0,
            'is_published' => '1',
        ])->assertRedirect(route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $module->id]));

        $this->assertDatabaseHas('lessons', [
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Welcome',
            'slug' => 'welcome',
            'lesson_type' => 'text',
            'is_published' => true,
        ]);
    }

    public function test_staff_can_create_pdf_lesson_with_upload(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->staffWithTenant('cs');
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 0,
            'is_published' => false,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 0,
            'is_published' => false,
        ]);
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        Storage::fake('public');
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('handout.pdf', 120);

        $this->post("/{$tenant->slug}/coach/lessons", [
            'module_id' => $module->id,
            'title' => 'Reading',
            'lesson_type' => 'pdf',
            'material_source' => 'upload',
            'material_file' => $file,
            'sort_order' => 0,
            'is_published' => '1',
        ])->assertRedirect(route('coach.lessons.index', ['tenant' => $tenant, 'module_id' => $module->id]));

        $lesson = Lesson::query()->firstWhere('title', 'Reading');
        $this->assertNotNull($lesson);
        $this->assertNotNull($lesson->media_disk_path);
        $this->assertNull($lesson->media_url);
        Storage::disk('public')->assertExists($lesson->media_disk_path);
    }

    public function test_learner_cannot_post_course(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'learner',
        ]);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->actingAs($user);

        $this->post("/{$tenant->slug}/coach/courses", [
            'program_id' => $program->id,
            'title' => 'Nope',
        ])->assertForbidden();
    }

    public function test_staff_cannot_attach_foreign_tenant_program_to_course(): void
    {
        $tenantA = Tenant::query()->create(['name' => 'A', 'slug' => 'a']);
        $tenantB = Tenant::query()->create(['name' => 'B', 'slug' => 'b']);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        $programB = Program::query()->create([
            'tenant_id' => $tenantB->id,
            'title' => 'Other',
            'slug' => 'other',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $this->actingAs($user);

        $this->post('/a/coach/courses', [
            'program_id' => $programB->id,
            'title' => 'Bad',
        ])->assertSessionHasErrors('program_id');
    }

    /**
     * @return array{tenant: Tenant, user: User}
     */
    private function staffWithTenant(string $slug): array
    {
        $tenant = Tenant::query()->create(['name' => 'Coach Space', 'slug' => $slug]);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        return ['tenant' => $tenant, 'user' => $user];
    }
}
