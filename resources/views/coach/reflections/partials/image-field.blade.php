@php
    $imageUrl = isset($prompt) ? $prompt->resolvedImageUrl() : null;
@endphp
<div>
    <label for="image" class="block text-sm font-medium text-stone-700">Image (optional)</label>
    <p class="mt-1 text-xs text-stone-500">JPG, PNG, GIF, or WebP — up to 10 MB. Shown to learners with the prompt and included in email notifications.</p>
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="" class="mt-3 max-h-48 rounded-lg border border-stone-200 object-contain">
        <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-stone-700">
            <input type="checkbox" name="remove_image" value="1" class="rounded border-stone-300 text-teal-600 focus:ring-teal-500">
            Remove current image
        </label>
    @endif
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp"
        class="mt-2 block w-full text-sm text-stone-600 file:mr-3 file:rounded-full file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-800 hover:file:bg-teal-100">
    @error('image')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
