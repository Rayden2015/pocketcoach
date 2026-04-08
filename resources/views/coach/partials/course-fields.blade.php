@php($c = $course)
<div>
    <label for="course_title" class="block text-sm font-medium text-stone-700">Title</label>
    <input id="course_title" name="title" type="text" required value="{{ old('title', $c->title ?? '') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<div>
    <label for="course_slug" class="block text-sm font-medium text-stone-700">Slug</label>
    <input id="course_slug" name="slug" type="text" value="{{ old('slug', $c->slug ?? '') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
    @if (! $c)
        <p class="mt-1 text-xs text-stone-500">Leave blank to auto-generate.</p>
    @endif
</div>
<div>
    <label for="course_summary" class="block text-sm font-medium text-stone-700">Summary</label>
    <textarea id="course_summary" name="summary" rows="3"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">{{ old('summary', $c->summary ?? '') }}</textarea>
</div>
<div>
    <label for="course_sort" class="block text-sm font-medium text-stone-700">Sort order</label>
    <input id="course_sort" name="sort_order" type="number" min="0" value="{{ old('sort_order', $c->sort_order ?? 0) }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<label class="flex items-center gap-2 text-sm text-stone-700">
    <input type="hidden" name="is_featured" value="0">
    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $c?->is_featured ?? false))>
    Featured course (highlighted in catalog)
</label>
<label class="flex items-center gap-2 text-sm text-stone-700">
    <input type="hidden" name="is_published" value="0">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $c?->is_published ?? false))>
    Published
</label>
