@php($m = $module)
<div>
    <label for="mod_title" class="block text-sm font-medium text-stone-700">Title</label>
    <input id="mod_title" name="title" type="text" required value="{{ old('title', $m->title ?? '') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<div>
    <label for="mod_slug" class="block text-sm font-medium text-stone-700">Slug</label>
    <input id="mod_slug" name="slug" type="text" value="{{ old('slug', $m->slug ?? '') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<div>
    <label for="mod_sort" class="block text-sm font-medium text-stone-700">Sort order</label>
    <input id="mod_sort" name="sort_order" type="number" min="0" value="{{ old('sort_order', $m->sort_order ?? 0) }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<label class="flex items-center gap-2 text-sm text-stone-700">
    <input type="hidden" name="is_published" value="0">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $m->is_published ?? false))>
    Published
</label>
