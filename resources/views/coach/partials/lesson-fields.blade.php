@php($l = $lesson)
@php($existingUrl = $l?->media_url)
@php($existingUpload = $l?->media_disk_path)
@php(
    $defaultSource = old('material_source', $l
        ? ($existingUrl ? 'url' : ($existingUpload ? 'upload' : 'none'))
        : 'none')
)
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
    <label for="lesson_type_select" class="block text-sm font-medium text-stone-700">Lesson type</label>
    <select id="lesson_type_select" name="lesson_type" required
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        @foreach (\App\Models\Lesson::TYPES as $t)
            <option value="{{ $t }}" @selected(old('lesson_type', $l->lesson_type ?? 'text') === $t)>
                {{ ucfirst($t) }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-stone-500">Text lessons use the body below; other types need a file or an external link.</p>
</div>

<div class="rounded-xl border border-stone-200 bg-stone-50/80 p-4">
    <p class="text-sm font-medium text-stone-800">Lesson material</p>
    <p class="mt-1 text-xs text-stone-500">Upload a file stored in this app, or paste a URL (hosted elsewhere). Google Drive and private YouTube flows can plug in here later.</p>

    <fieldset class="mt-3 space-y-2">
        <legend class="sr-only">Material source</legend>
        <label class="flex cursor-pointer items-center gap-2 text-sm text-stone-700">
            <input type="radio" name="material_source" value="none" id="material_source_none" class="text-teal-600 focus:ring-teal-500"
                @checked($defaultSource === 'none')>
            <span>No separate media <span class="text-stone-500">(text lessons only)</span></span>
        </label>
        <label class="flex cursor-pointer items-center gap-2 text-sm text-stone-700">
            <input type="radio" name="material_source" value="upload" id="material_source_upload" class="text-teal-600 focus:ring-teal-500"
                @checked($defaultSource === 'upload')>
            <span>Upload file</span>
        </label>
        <label class="flex cursor-pointer items-center gap-2 text-sm text-stone-700">
            <input type="radio" name="material_source" value="url" id="material_source_url" class="text-teal-600 focus:ring-teal-500"
                @checked($defaultSource === 'url')>
            <span>External URL</span>
        </label>
    </fieldset>

    <div id="material_file_wrap" class="mt-4 hidden">
        <label for="material_file" class="block text-sm font-medium text-stone-700">File</label>
        <input id="material_file" name="material_file" type="file"
            class="mt-1 block w-full text-sm text-stone-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-800 hover:file:bg-teal-100">
        @if ($existingUpload)
            <p class="mt-1 text-xs text-stone-600">Current upload is kept unless you choose a new file.</p>
        @endif
    </div>

    <div id="material_url_wrap" class="mt-4 hidden">
        <label for="media_url" class="block text-sm font-medium text-stone-700">URL</label>
        <input id="media_url" name="media_url" type="url" inputmode="url" placeholder="https://…"
            value="{{ old('media_url', $l->media_url ?? '') }}"
            class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
    </div>

    <div id="lesson_material_preview" class="mt-4 hidden rounded-xl border border-dashed border-stone-300 bg-white p-3">
        <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Preview</p>
        <div id="lesson_material_preview_inner" class="mt-2"></div>
    </div>
</div>

<div>
    <label for="body" class="block text-sm font-medium text-stone-700">Body</label>
    <textarea id="body" name="body" rows="10"
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 font-mono text-sm">{{ old('body', $l->body ?? '') }}</textarea>
    <p class="mt-1 text-xs text-stone-500">Main written content (especially for text lessons).</p>
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

<script>
(function () {
    const typeSelect = document.getElementById('lesson_type_select');
    const materialNone = document.getElementById('material_source_none');
    const materialUpload = document.getElementById('material_source_upload');
    const materialUrl = document.getElementById('material_source_url');
    const fileWrap = document.getElementById('material_file_wrap');
    const urlWrap = document.getElementById('material_url_wrap');
    const fileInput = document.getElementById('material_file');
    const urlInput = document.getElementById('media_url');
    const preview = document.getElementById('lesson_material_preview');
    const previewInner = document.getElementById('lesson_material_preview_inner');

    const fileAccept = {
        text: '.pdf,.jpg,.jpeg,.png,.gif,.webp',
        pdf: '.pdf,application/pdf',
        video: 'video/*,.mp4,.webm,.mov',
        audio: 'audio/*,.mp3,.m4a,.wav,.ogg,.aac',
        image: 'image/*,.jpg,.jpeg,.png,.gif,.webp',
    };

    function syncTypeToMaterial() {
        const type = typeSelect.value;
        if (type !== 'text') {
            materialNone.disabled = true;
            if (materialNone.checked) {
                materialUpload.checked = true;
            }
        } else {
            materialNone.disabled = false;
        }
        fileInput.accept = fileAccept[type] || '';
        syncSourceVisibility();
    }

    function syncSourceVisibility() {
        const src = document.querySelector('input[name="material_source"]:checked')?.value;
        fileWrap.classList.toggle('hidden', src !== 'upload');
        urlWrap.classList.toggle('hidden', src !== 'url');
        if (src === 'none') {
            preview.classList.add('hidden');
            previewInner.innerHTML = '';
        } else {
            updatePreview();
        }
    }

    function appendMediaForUrl(url, type) {
        previewInner.innerHTML = '';
        if (!url) {
            preview.classList.add('hidden');
            return;
        }
        const lowered = url.toLowerCase();
        let embed = null;
        const ytWatch = url.match(/youtube\.com\/watch\?([^#]*&)?v=([a-zA-Z0-9_-]{11})/);
        const ytShort = url.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/);
        if (ytWatch) embed = 'https://www.youtube.com/embed/' + ytWatch[2];
        else if (ytShort) embed = 'https://www.youtube.com/embed/' + ytShort[1];

        if (type === 'video' && embed) {
            const ifr = document.createElement('iframe');
            ifr.src = embed;
            ifr.className = 'aspect-video h-56 w-full rounded-lg bg-black';
            ifr.setAttribute('allowfullscreen', '');
            ifr.title = 'Video preview';
            previewInner.appendChild(ifr);
            preview.classList.remove('hidden');
            return;
        }
        if (type === 'video') {
            const v = document.createElement('video');
            v.src = url;
            v.controls = true;
            v.className = 'max-h-64 w-full rounded-lg bg-black';
            previewInner.appendChild(v);
            preview.classList.remove('hidden');
            return;
        }
        if (type === 'audio') {
            const a = document.createElement('audio');
            a.src = url;
            a.controls = true;
            a.className = 'w-full';
            previewInner.appendChild(a);
            preview.classList.remove('hidden');
            return;
        }
        if (type === 'image' || (type === 'text' && /\.(jpe?g|png|gif|webp)(\?|$)/i.test(lowered))) {
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Preview';
            img.className = 'max-h-64 w-full rounded-lg object-contain bg-stone-100';
            img.onerror = function () {
                previewInner.textContent = 'Could not load image preview.';
            };
            previewInner.appendChild(img);
            preview.classList.remove('hidden');
            return;
        }
        if (type === 'pdf' || /\.pdf(\?|$)/i.test(lowered)) {
            const ifr = document.createElement('iframe');
            ifr.src = url;
            ifr.className = 'h-72 w-full rounded-lg border border-stone-200 bg-stone-50';
            ifr.title = 'PDF preview';
            previewInner.appendChild(ifr);
            preview.classList.remove('hidden');
            return;
        }
        const p = document.createElement('p');
        p.className = 'text-sm text-stone-600';
        p.textContent = 'Open the learner view to verify this URL.';
        previewInner.appendChild(p);
        preview.classList.remove('hidden');
    }

    function updatePreview() {
        const src = document.querySelector('input[name="material_source"]:checked')?.value;
        const type = typeSelect.value;
        if (src === 'url') {
            appendMediaForUrl(urlInput.value.trim(), type);
            return;
        }
        if (src === 'upload' && fileInput.files && fileInput.files[0]) {
            const f = fileInput.files[0];
            const u = URL.createObjectURL(f);
            previewInner.innerHTML = '';
            if (type === 'image' || f.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = u;
                img.className = 'max-h-64 w-full rounded-lg object-contain bg-stone-100';
                previewInner.appendChild(img);
            } else if (type === 'video' || f.type.startsWith('video/')) {
                const v = document.createElement('video');
                v.src = u;
                v.controls = true;
                v.className = 'max-h-64 w-full rounded-lg bg-black';
                previewInner.appendChild(v);
            } else if (type === 'audio' || f.type.startsWith('audio/')) {
                const a = document.createElement('audio');
                a.src = u;
                a.controls = true;
                a.className = 'w-full';
                previewInner.appendChild(a);
            } else if (type === 'pdf' || f.type === 'application/pdf') {
                const ifr = document.createElement('iframe');
                ifr.src = u;
                ifr.className = 'h-72 w-full rounded-lg border border-stone-200 bg-stone-50';
                ifr.title = 'PDF preview';
                previewInner.appendChild(ifr);
            } else {
                previewInner.textContent = f.name;
            }
            preview.classList.remove('hidden');
            return;
        }
        preview.classList.add('hidden');
        previewInner.innerHTML = '';
    }

    typeSelect.addEventListener('change', syncTypeToMaterial);
    [materialNone, materialUpload, materialUrl].forEach(function (el) {
        el.addEventListener('change', syncSourceVisibility);
    });
    fileInput.addEventListener('change', updatePreview);
    urlInput.addEventListener('input', function () {
        if (document.querySelector('input[name="material_source"]:checked')?.value === 'url') {
            appendMediaForUrl(urlInput.value.trim(), typeSelect.value);
        }
    });

    syncTypeToMaterial();
})();
</script>
