<?php

namespace App\Services;

use App\Enums\TenantRole;
use App\Models\LessonProgress;
use App\Models\ReflectionPrompt;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\Coach\CoachSpaceSnapshotBuilder;

final class TenantHomeDashboardService
{
    public function __construct(
        private CourseAccessService $access,
        private ContinueLearningService $continueLearning,
        private CoachSpaceSnapshotBuilder $coachSnapshots,
    ) {}

    /**
     * @return array{
     *   membership: array{role: string, is_staff: bool}|null,
     *   learner: array<string, mixed>,
     *   coach: array<string, mixed>|null,
     * }
     */
    public function build(User $user, Tenant $tenant): array
    {
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        $membershipPayload = $membership === null ? null : [
            'role' => $membership->role,
            'is_staff' => in_array($membership->role, TenantRole::staffValues(), true),
        ];

        $learner = $this->learnerSection($user, $tenant);

        $coach = ($membershipPayload['is_staff'] ?? false)
            ? $this->coachSection($tenant)
            : null;

        return [
            'membership' => $membershipPayload,
            'learner' => $learner,
            'coach' => $coach,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function learnerSection(User $user, Tenant $tenant): array
    {
        $courseIds = $this->access->accessibleCourseIdsForUserInTenant($user, $tenant->id);

        $seven = now()->subDays(7);
        $thirty = now()->subDays(30);

        $lessonsCompleted7d = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $seven)
            ->count();

        $lessonsCompleted30d = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $thirty)
            ->count();

        $coursesCompleted = 0;
        $coursesInProgress = 0;
        $coursesNotStarted = 0;

        foreach ($courseIds as $cid) {
            $lessonIds = CourseCurriculumService::publishedLessonIdsForCourse((int) $cid);
            $total = $lessonIds->count();
            if ($total === 0) {
                continue;
            }
            $completed = LessonProgress::query()
                ->where('user_id', $user->id)
                ->where('tenant_id', $tenant->id)
                ->whereIn('lesson_id', $lessonIds)
                ->whereNotNull('completed_at')
                ->count();

            if ($completed >= $total) {
                $coursesCompleted++;
            } elseif ($completed > 0) {
                $coursesInProgress++;
            } else {
                $coursesNotStarted++;
            }
        }

        $continue = $this->continueLearning->nextForUserInTenant($user, $tenant->id);
        $continuePayload = null;
        if ($continue !== null) {
            $lesson = $continue['lesson'];
            $course = $continue['course'];
            $continuePayload = [
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                ],
                'lesson' => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                ],
            ];
        }

        return [
            'lessons_completed_7d' => $lessonsCompleted7d,
            'lessons_completed_30d' => $lessonsCompleted30d,
            'courses_enrolled' => count($courseIds),
            'courses_completed' => $coursesCompleted,
            'courses_in_progress' => $coursesInProgress,
            'courses_not_started' => $coursesNotStarted,
            'continue' => $continuePayload,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function coachSection(Tenant $tenant): array
    {
        $snapshot = $this->coachSnapshots->build($tenant->id);

        $scheduledPending = ReflectionPrompt::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', false)
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '>', now())
            ->count();

        return array_merge($snapshot, [
            'scheduled_reflections_pending' => $scheduledPending,
        ]);
    }
}
