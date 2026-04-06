@php($l = $lesson)
<div>
    <label for="les_title" class="block text-sm font-medium text-stone-700">Title</label>
    <input id="les_title" name="title" type="text" required value="{{ old('title', $l->title ?? '') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<div>
    <label for="les_slug" class="block text-sm font-medium text-stone-700">Slug</label>
    <input id="les_slug" name="slug" type="text" value="{{ old('slug', $l->slug ?? '') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<div>
    <label for="lesson_type" class="block text-sm font-medium text-stone-700">Type</label>
    <input id="lesson_type" name="lesson_type" type="text" value="{{ old('lesson_type', $l->lesson_type ?? 'text') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
    <p class="mt-1 text-xs text-stone-500">e.g. text, video</p>
</div>
<div>
    <label for="media_url" class="block text-sm font-medium text-stone-700">Embed / media URL</label>
    <input id="media_url" name="media_url" type="text" value="{{ old('media_url', $l->media_url ?? '') }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<div>
    <label for="body" class="block text-sm font-medium text-stone-700">Body</label>
    <textarea id="body" name="body" rows="10"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 font-mono text-sm">{{ old('body', $l->body ?? '') }}</textarea>
</div>
<div>
    <label for="les_sort" class="block text-sm font-medium text-stone-700">Sort order</label>
    <input id="les_sort" name="sort_order" type="number" min="0" value="{{ old('sort_order', $l->sort_order ?? 0) }}"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
</div>
<label class="flex items-center gap-2 text-sm text-stone-700">
    <input type="hidden" name="is_published" value="0">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $l->is_published ?? false))>
    Published
</label>
