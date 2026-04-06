<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\ContinueLearningService;
use Illuminate\Http\RedirectResponse;

class LearnContinueController extends Controller
{
    public function __construct(
        private ContinueLearningService $continueLearning,
    ) {}

    public function show(Tenant $tenant): RedirectResponse
    {
        $next = $this->continueLearning->nextForUserInTenant(auth()->user(), $tenant->id);

        if ($next === null) {
            return redirect()
                ->route('learn.catalog', $tenant)
                ->with('status', 'No enrolled lessons yet, or everything is complete.');
        }

        return redirect()->route('learn.lesson', [$tenant, $next['lesson']]);
    }
}
