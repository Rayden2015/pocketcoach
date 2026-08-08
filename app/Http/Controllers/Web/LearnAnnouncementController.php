<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SpaceAnnouncement;
use App\Models\Tenant;
use Illuminate\View\View;

class LearnAnnouncementController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $announcements = SpaceAnnouncement::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->paginate(20);

        return view('learn.announcements.index', [
            'tenant' => $tenant,
            'announcements' => $announcements,
        ]);
    }

    public function show(Tenant $tenant, SpaceAnnouncement $announcement): View
    {
        abort_unless($announcement->tenant_id === $tenant->id, 404);
        abort_unless($announcement->is_published && $announcement->published_at !== null, 404);

        return view('learn.announcements.show', [
            'tenant' => $tenant,
            'announcement' => $announcement,
        ]);
    }
}
