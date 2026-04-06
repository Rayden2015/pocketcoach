<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->with([
                'courses' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $programs->map(fn (Program $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'summary' => $p->summary,
                'courses' => $p->courses->map(fn ($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'slug' => $c->slug,
                    'summary' => $c->summary,
                ]),
            ]),
        ]);
    }
}
