<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseReviewController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $rawCourse = $request->query('course');
        $courseId = null;
        if ($rawCourse !== null && $rawCourse !== '' && ctype_digit((string) $rawCourse)) {
            $courseId = (int) $rawCourse;
        }

        $coursesForFilter = Course::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('title')
            ->get(['id', 'title']);

        $query = CourseReview::query()
            ->where('tenant_id', $tenant->id)
            ->with(['user', 'course'])
            ->orderByDesc('updated_at');

        if ($courseId !== null) {
            $query->where('course_id', $courseId);
        }

        $reviews = $query->paginate(25)->withQueryString();

        return view('coach.course-reviews.index', [
            'tenant' => $tenant,
            'reviews' => $reviews,
            'coursesForFilter' => $coursesForFilter,
            'selectedCourseId' => $courseId,
        ]);
    }
}
