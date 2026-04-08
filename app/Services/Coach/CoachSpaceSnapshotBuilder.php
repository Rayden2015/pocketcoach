<?php

namespace App\Services\Coach;

use App\Enums\TenantRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Program;
use App\Models\ReflectionPrompt;
use App\Models\TenantMembership;

final class CoachSpaceSnapshotBuilder
{
    /**
     * @return array{
     *   programs_live: int,
     *   programs_draft: int,
     *   courses_live: int,
     *   active_enrollments: int,
     *   learners_with_enrollment: int,
     *   learner_members: int,
     *   lesson_completions_7d: int,
     *   reflection_prompts_live: int,
     * }
     */
    public function build(int $tenantId): array
    {
        return [
            'programs_live' => Program::query()
                ->where('tenant_id', $tenantId)
                ->where('is_published', true)
                ->count(),
            'programs_draft' => Program::query()
                ->where('tenant_id', $tenantId)
                ->where('is_published', false)
                ->count(),
            'courses_live' => Course::query()
                ->where('tenant_id', $tenantId)
                ->where('is_published', true)
                ->count(),
            'active_enrollments' => Enrollment::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->count(),
            'learners_with_enrollment' => Enrollment::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->distinct()
                ->count('user_id'),
            'learner_members' => TenantMembership::query()
                ->where('tenant_id', $tenantId)
                ->where('role', TenantRole::Learner->value)
                ->count(),
            'lesson_completions_7d' => LessonProgress::query()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', now()->subDays(7))
                ->count(),
            'reflection_prompts_live' => ReflectionPrompt::query()
                ->where('tenant_id', $tenantId)
                ->where('is_published', true)
                ->count(),
        ];
    }

    /**
     * @param  list<int>  $tenantIds
     * @return array<int, array<string, int>>
     */
    public function buildMany(array $tenantIds): array
    {
        $out = [];
        foreach ($tenantIds as $id) {
            $out[$id] = $this->build((int) $id);
        }

        return $out;
    }
}
