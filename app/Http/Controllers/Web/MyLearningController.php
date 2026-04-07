<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\MyLearningOverviewService;
use Illuminate\View\View;

class MyLearningController extends Controller
{
    public function __construct(
        private MyLearningOverviewService $overview,
    ) {}

    public function index(): View
    {
        $courses = $this->overview->coursesForUser(auth()->user());

        return view('my-learning.index', [
            'courses' => $courses,
        ]);
    }
}
