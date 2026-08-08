<?php

namespace App\Jobs;

use App\Models\SpaceAnnouncement;
use App\Models\User;
use App\Notifications\SpaceAnnouncementPublishedNotification;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyTenantMembersOfSpaceAnnouncement
{
    use Queueable;

    public function __construct(
        public int $announcementId,
    ) {}

    public function handle(): void
    {
        $announcement = SpaceAnnouncement::query()
            ->whereKey($this->announcementId)
            ->with('tenant')
            ->first();

        if ($announcement === null || ! $announcement->is_published || $announcement->published_at === null) {
            return;
        }

        $tenantId = $announcement->tenant_id;

        User::query()
            ->whereHas('memberships', fn ($q) => $q->where('tenant_id', $tenantId))
            ->chunkById(100, function ($users) use ($announcement): void {
                Notification::send($users, new SpaceAnnouncementPublishedNotification($announcement));
            });
    }
}
