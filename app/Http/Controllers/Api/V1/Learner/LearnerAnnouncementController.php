<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\SpaceAnnouncement;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class LearnerAnnouncementController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        $rows = SpaceAnnouncement::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (SpaceAnnouncement $a) => $this->serialize($a))->values()->all(),
        ]);
    }

    public function show(Tenant $tenant, SpaceAnnouncement $announcement): JsonResponse
    {
        abort_unless($announcement->tenant_id === $tenant->id, 404);
        abort_unless($announcement->is_published && $announcement->published_at !== null, 404);

        return response()->json([
            'data' => $this->serialize($announcement, includeBody: true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SpaceAnnouncement $announcement, bool $includeBody = false): array
    {
        $payload = [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'published_at' => $announcement->published_at?->toIso8601String(),
        ];

        if ($includeBody) {
            $payload['body'] = $announcement->body;
        } else {
            $payload['preview'] = str(strip_tags($announcement->body))->limit(200)->toString();
        }

        return $payload;
    }
}
