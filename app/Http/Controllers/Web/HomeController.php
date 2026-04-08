<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $spaces = Tenant::query()
            ->where(function ($q): void {
                $q->where('status', Tenant::STATUS_ACTIVE)
                    ->orWhereNull('status');
            })
            ->withCount([
                'programs as published_programs_count' => function ($q): void {
                    $q->where('is_published', true);
                },
            ])
            ->whereHas('programs', function ($q): void {
                $q->where('is_published', true);
            })
            ->orderBy('name')
            ->get();

        return view('home', [
            'spaces' => $spaces,
        ]);
    }
}
