<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Tenant;
use App\Services\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LearnCourseReviewController extends Controller
{
    public function __construct(
        private CourseAccessService $access,
    ) {}

    public function store(Request $request, Tenant $tenant, Course $course): RedirectResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        $user = $request->user();
        abort_unless($user !== null, 403);
        if (! $this->access->canAccessCourse($user, $course)) {
            Log::warning('course_review.denied', [
                'channel' => 'web',
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
            'channel' => 'web',
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

        return redirect()
            ->route('learn.course', [$tenant, $course])
            ->with('status', $review->wasRecentlyCreated ? 'Thanks for your review.' : 'Your review was updated.');
    }
}
