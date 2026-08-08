<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_learner_can_submit_course_review_via_web(): void
    {
        $data = $this->seedEnrolledCourse();
        $this->actingAs($data['user']);

        $this->post(route('learn.course.review', [$data['tenant'], $data['course']]), [
            'rating' => 5,
            'comment' => 'Excellent course.',
        ])->assertRedirect(route('learn.course', [$data['tenant'], $data['course']]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('course_reviews', [
            'tenant_id' => $data['tenant']->id,
            'course_id' => $data['course']->id,
            'user_id' => $data['user']->id,
            'rating' => 5,
            'comment' => 'Excellent course.',
        ]);
    }

    public function test_non_enrolled_learner_cannot_submit_review(): void
    {
        $data = $this->seedPublishedCourse();
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
        $this->actingAs($user);

        $this->post(route('learn.course.review', [$data['tenant'], $data['course']]), [
            'rating' => 4,
        ])->assertForbidden();
    }

    public function test_api_upsert_review_updates_existing_row(): void
    {
        $data = $this->seedEnrolledCourse();
        Sanctum::actingAs($data['user']);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/courses/{$data['course']->id}/review", [
            'rating' => 3,
            'comment' => 'Good start.',
        ])->assertOk()
            ->assertJsonPath('data.rating', 3)
            ->assertJsonPath('data.comment', 'Good start.');

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/courses/{$data['course']->id}/review", [
            'rating' => 5,
            'comment' => 'Even better now.',
        ])->assertOk()
            ->assertJsonPath('data.rating', 5);

        $this->assertSame(1, CourseReview::query()->count());
        $this->assertDatabaseHas('course_reviews', [
            'course_id' => $data['course']->id,
            'user_id' => $data['user']->id,
            'rating' => 5,
            'comment' => 'Even better now.',
        ]);
    }

    public function test_course_api_includes_review_summary_and_my_review(): void
    {
        $data = $this->seedEnrolledCourse();
        CourseReview::query()->create([
            'tenant_id' => $data['tenant']->id,
            'course_id' => $data['course']->id,
            'user_id' => $data['user']->id,
            'rating' => 4,
            'comment' => 'Solid.',
            'first_submitted_at' => now(),
        ]);
        Sanctum::actingAs($data['user']);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/courses/{$data['course']->id}")
            ->assertOk()
            ->assertJsonPath('data.reviews_summary.average_rating', 4)
            ->assertJsonPath('data.reviews_summary.reviews_count', 1)
            ->assertJsonPath('data.my_review.rating', 4)
            ->assertJsonPath('data.my_review.comment', 'Solid.');
    }

    public function test_staff_can_view_course_reviews_index(): void
    {
        $data = $this->seedEnrolledCourse();
        CourseReview::query()->create([
            'tenant_id' => $data['tenant']->id,
            'course_id' => $data['course']->id,
            'user_id' => $data['user']->id,
            'rating' => 5,
            'comment' => 'Loved it.',
            'first_submitted_at' => now(),
        ]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $this->actingAs($coach);

        $this->get(route('coach.course-reviews.index', $data['tenant']))
            ->assertOk()
            ->assertSee('Loved it', false);
    }

    /**
     * @return array{tenant: Tenant, course: Course, user: User}
     */
    private function seedEnrolledCourse(): array
    {
        $published = $this->seedPublishedCourse();
        Enrollment::query()->create([
            'tenant_id' => $published['tenant']->id,
            'user_id' => $published['user']->id,
            'course_id' => $published['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);

        return $published;
    }

    /**
     * @return array{tenant: Tenant, course: Course, user: User}
     */
    private function seedPublishedCourse(): array
    {
        $tenant = Tenant::query()->create(['name' => 'Review Space', 'slug' => 'reviews']);
        $user = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Program',
            'slug' => 'program',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Reviewable',
            'slug' => 'reviewable',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        return compact('tenant', 'course', 'user');
    }
}
