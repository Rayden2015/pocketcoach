<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LearnerCourseReviewController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function upsert(Request $request, Tenant $tenant, Course $course): JsonResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        $user = $request->user();
        abort_unless($user !== null, 403);
        if (! $this->access->canAccessCourse($user, $course)) {
            Log::warning('course_review.denied', [
                'channel' => 'api',
                'reason' => 'not_enrolled',
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
                'course_id' => $course->id,
                'course_slug' => $course->slug,
                'user_id' => $user->id,
            ]);
            abort(403);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:65535'],
        ]);

        $now = now();
        $review = CourseReview::query()->firstOrNew([
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
        $review->tenant_id = $tenant->id;
        $review->rating = (int) $validated['rating'];
        $review->comment = isset($validated['comment']) && trim($validated['comment']) !== ''
            ? trim($validated['comment'])
            : null;
        if ($review->first_submitted_at === null) {
            $review->first_submitted_at = $now;
        }
        $review->save();

        Log::info('course_review.saved', [
            'channel' => 'api',
            'review_id' => $review->id,
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'course_id' => $course->id,
            'course_slug' => $course->slug,
            'user_id' => $user->id,
            'rating' => $review->rating,
            'has_comment' => $review->comment !== null,
            'comment_length' => $review->comment !== null ? mb_strlen($review->comment) : 0,
            'created' => $review->wasRecentlyCreated,
        ]);

        return response()->json([
            'data' => $this->serializeReview($review),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeReview(CourseReview $review): array
    {
        return [
            'rating' => $review->rating,
            'comment' => $review->comment,
            'first_submitted_at' => $review->first_submitted_at?->toIso8601String(),
            'updated_at' => $review->updated_at?->toIso8601String(),
        ];
    }
}
