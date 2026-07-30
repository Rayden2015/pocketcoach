<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnLessonStudioWebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tenant: Tenant, course: Course, imageLesson: Lesson, textLesson: Lesson, user: User}
     */
    private function enrolledLearnerWithMediaLesson(): array
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $imageLesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Chart',
            'slug' => 'chart',
            'lesson_type' => Lesson::TYPE_IMAGE,
            'media_url' => 'https://example.com/slide.png',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $textLesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Reading',
            'slug' => 'reading',
            'lesson_type' => Lesson::TYPE_TEXT,
            'body' => 'Hello',
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'course_id' => $course->id,
            'source' => 'test',
            'status' => 'active',
        ]);

        return compact('tenant', 'course', 'imageLesson', 'textLesson', 'user');
    }

    public function test_studio_renders_for_image_lessons(): void
    {
        $s = $this->enrolledLearnerWithMediaLesson();
        $this->actingAs($s['user']);

        $this->get(route('learn.lesson.studio', [$s['tenant'], $s['imageLesson']]))
            ->assertOk()
            ->assertSee('Exit studio')
            ->assertSee('Fullscreen')
            ->assertSee('Your notes')
            ->assertSee('https://example.com/slide.png', false);
    }

    public function test_lesson_page_links_to_studio_for_media_lessons(): void
    {
        $s = $this->enrolledLearnerWithMediaLesson();
        $this->actingAs($s['user']);

        $this->get(route('learn.lesson', [$s['tenant'], $s['imageLesson']]))
            ->assertOk()
            ->assertSee('Open studio')
            ->assertSee(route('learn.lesson.studio', [$s['tenant'], $s['imageLesson']], false));
    }

    public function test_studio_redirects_for_text_lessons(): void
    {
        $s = $this->enrolledLearnerWithMediaLesson();
        $this->actingAs($s['user']);

        $this->get(route('learn.lesson.studio', [$s['tenant'], $s['textLesson']]))
            ->assertRedirect(route('learn.lesson', [$s['tenant'], $s['textLesson']]));
    }

    public function test_save_notes_from_studio_returns_to_studio(): void
    {
        $s = $this->enrolledLearnerWithMediaLesson();
        $this->actingAs($s['user']);

        $this->post(route('learn.lesson.progress', [$s['tenant'], $s['imageLesson']]), [
            'notes' => 'Detail from the chart',
            'intent' => 'save_notes',
            'return_to' => 'studio',
        ])->assertRedirect(route('learn.lesson.studio', [$s['tenant'], $s['imageLesson']]));

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $s['user']->id,
            'lesson_id' => $s['imageLesson']->id,
            'notes' => 'Detail from the chart',
        ]);
    }
}
