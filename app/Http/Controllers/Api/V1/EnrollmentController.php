<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollments,
    ) {}

    public function free(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::query()->where('tenant_id', $tenant->id)->whereKey($validated['product_id'])->firstOrFail();

        if ($product->type !== Product::TYPE_FREE || ! $product->is_active) {
            return response()->json(['message' => 'Product is not a free offer.'], 422);
        }

        if ($product->course_id === null && $product->program_id === null) {
            return response()->json(['message' => 'Product is not linked to any course or program.'], 422);
        }

        $enrollment = $this->enrollments->enrollFromProduct($request->user(), $tenant, $product, 'free');

        return response()->json(['enrollment' => $enrollment]);
    }
}
