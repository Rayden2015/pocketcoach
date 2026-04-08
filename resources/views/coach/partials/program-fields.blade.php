@php($p = $program)
<div>
    <label for="title" class="block text-sm font-medium text-stone-700">Title</label>
    <input id="title" name="title" type="text" required value="{{ old('title', $p->title ?? '') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<div>
    <label for="slug" class="block text-sm font-medium text-stone-700">Slug</label>
    <input id="slug" name="slug" type="text" value="{{ old('slug', $p->slug ?? '') }}"
        @if ($p) required @endif
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
    @if (! $p)
        <p class="mt-1 text-xs text-stone-500">Leave blank to auto-generate from title.</p>
    @endif
</div>
<div>
    <label for="summary" class="block text-sm font-medium text-stone-700">Summary</label>
    <textarea id="summary" name="summary" rows="3"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">{{ old('summary', $p->summary ?? '') }}</textarea>
</div>
<div>
    <label for="sort_order" class="block text-sm font-medium text-stone-700">Sort order</label>
    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $p->sort_order ?? 0) }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<label class="flex items-center gap-2 text-sm text-stone-700">
    <input type="hidden" name="is_featured" value="0">
    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $p?->is_featured ?? false))>
    Featured (shown first on public catalog when enabled for the space)
</label>
<label class="flex items-center gap-2 text-sm text-stone-700">
    <input type="hidden" name="is_published" value="0">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $p?->is_published ?? false))>
    Published (visible in catalog)
</label>
