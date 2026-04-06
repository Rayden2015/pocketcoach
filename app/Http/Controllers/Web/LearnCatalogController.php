<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\View\View;

class LearnCatalogController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $programs = Program::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->with([
                'courses' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        return view('learn.catalog', [
            'tenant' => $tenant,
            'programs' => $programs,
        ]);
    }
}
