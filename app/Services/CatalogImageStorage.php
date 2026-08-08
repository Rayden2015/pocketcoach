<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class CatalogImageStorage
{
    public function storeFromRequest(Request $request, Tenant $tenant, string $kind): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        if (! $file instanceof UploadedFile) {
            return null;
        }

        return $file->store("catalog/{$tenant->id}/{$kind}", 'public');
    }

    public function pathAfterUpdate(Request $request, Tenant $tenant, string $kind, ?string $existing): ?string
    {
        if ($request->boolean('remove_image') && $existing) {
            Storage::disk('public')->delete($existing);

            return null;
        }

        if ($request->hasFile('image')) {
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }

            return $this->storeFromRequest($request, $tenant, $kind);
        }

        return $existing;
    }
}
