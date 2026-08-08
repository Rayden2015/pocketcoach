<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyTenantMembersOfSpaceAnnouncement;
use App\Models\SpaceAnnouncement;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaceAnnouncementController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $announcements = SpaceAnnouncement::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return view('coach.announcements.index', [
            'tenant' => $tenant,
            'announcements' => $announcements,
        ]);
    }

    public function create(Tenant $tenant): View
    {
        return view('coach.announcements.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:65535'],
            'publish_now' => ['sometimes', 'boolean'],
        ]);

        $publishNow = $request->boolean('publish_now', true);

        $announcement = SpaceAnnouncement::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => auth()->id(),
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_published' => $publishNow,
            'published_at' => $publishNow ? now() : null,
        ]);

        if ($publishNow) {
            dispatch_sync(new NotifyTenantMembersOfSpaceAnnouncement($announcement->id));
        }

        return redirect()
            ->route('coach.announcements.index', $tenant)
            ->with('status', $publishNow ? 'Announcement published and members notified.' : 'Announcement saved as draft.');
    }

    public function edit(Tenant $tenant, SpaceAnnouncement $announcement): View
    {
        abort_unless($announcement->tenant_id === $tenant->id, 404);

        return view('coach.announcements.edit', [
            'tenant' => $tenant,
            'announcement' => $announcement,
        ]);
    }

    public function update(Request $request, Tenant $tenant, SpaceAnnouncement $announcement): RedirectResponse
    {
        abort_unless($announcement->tenant_id === $tenant->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:65535'],
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('coach.announcements.index', $tenant)
            ->with('status', 'Announcement updated.');
    }

    public function publish(Tenant $tenant, SpaceAnnouncement $announcement): RedirectResponse
    {
        abort_unless($announcement->tenant_id === $tenant->id, 404);

        if ($announcement->is_published) {
            return back()->with('warning', 'This announcement is already published.');
        }

        $announcement->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        dispatch_sync(new NotifyTenantMembersOfSpaceAnnouncement($announcement->id));

        return back()->with('status', 'Announcement published and members notified.');
    }

    public function destroy(Tenant $tenant, SpaceAnnouncement $announcement): RedirectResponse
    {
        abort_unless($announcement->tenant_id === $tenant->id, 404);
        $announcement->delete();

        return redirect()
            ->route('coach.announcements.index', $tenant)
            ->with('status', 'Announcement deleted.');
    }
}
