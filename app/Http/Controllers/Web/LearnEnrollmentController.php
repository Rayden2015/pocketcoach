<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\EnrollmentService;
use App\Services\FreeProductLookup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LearnEnrollmentController extends Controller
{
    public function __construct(
        private FreeProductLookup $freeProducts,
        private EnrollmentService $enrollments,
    ) {}

    public function store(Request $request, Tenant $tenant, Course $course): RedirectResponse
    {
        abort_unless($course->tenant_id === $tenant->id, 404);

        $productId = $this->freeProducts->productIdForCourse($tenant, $course);
        if ($productId === null) {
            return redirect()
                ->route('learn.course', [$tenant, $course])
                ->withErrors(['enroll' => 'There is no free enrollment offer for this course. Ask the space admin.']);
        }

        $product = Product::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($productId)
            ->firstOrFail();

        if ($product->type !== Product::TYPE_FREE || ! $product->is_active) {
            return redirect()
                ->route('learn.course', [$tenant, $course])
                ->withErrors(['enroll' => 'This offer is not available.']);
        }

        if ($product->course_id === null && $product->program_id === null) {
            return redirect()
                ->route('learn.course', [$tenant, $course])
                ->withErrors(['enroll' => 'This product is not linked to a course or program.']);
        }

        $this->enrollments->enrollFromProduct($request->user(), $tenant, $product, 'free');

        return redirect()
            ->route('learn.course', [$tenant, $course])
            ->with('status', 'You are enrolled. Open any lesson below to get started.');
    }
}
