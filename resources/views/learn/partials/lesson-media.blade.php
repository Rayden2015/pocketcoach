@php
    $mediaUrl = $lesson->resolvedMediaUrl();
    $studioMode = $studioMode ?? false;
@endphp
@if ($mediaUrl)
    <div class="lesson-media {{ $studioMode ? 'lesson-media--studio flex h-full w-full min-h-0' : '' }}" data-lesson-media data-media-type="{{ $lesson->lesson_type }}">
        @switch($lesson->lesson_type)
            @case(\App\Models\Lesson::TYPE_VIDEO)
                @if ($embed = \App\Models\Lesson::youtubeEmbedUrl($mediaUrl))
                    <div class="{{ $studioMode ? 'h-full w-full' : 'aspect-video w-full' }} overflow-hidden {{ $studioMode ? '' : 'rounded-xl shadow-inner' }} bg-stone-900">
                        <iframe src="{{ $embed }}" class="h-full w-full" title="{{ $lesson->title }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>
                    </div>
                @else
                    <video
                        src="{{ $mediaUrl }}"
                        controls
                        class="{{ $studioMode ? 'h-full max-h-full w-full object-contain' : 'w-full max-h-[min(70vh,520px)] rounded-xl shadow-lg' }} bg-black"
                        preload="metadata"
                        playsinline
                        controlsList="nodownload"
                    ></video>
                @endif
                @break
            @case(\App\Models\Lesson::TYPE_IMAGE)
                <figure class="{{ $studioMode ? 'flex h-full w-full cursor-grab items-center justify-center overflow-auto bg-stone-950 active:cursor-grabbing' : '' }}" @if ($studioMode) data-studio-image-stage @endif>
                    <img
                        src="{{ $mediaUrl }}"
                        alt="{{ $lesson->title }}"
                        class="{{ $studioMode ? 'max-h-full max-w-full origin-center object-contain transition-transform duration-150 will-change-transform' : 'max-h-[min(70vh,560px)] w-full rounded-xl bg-stone-100 object-contain shadow-sm' }}"
                        data-studio-image
                        draggable="false"
                    >
                </figure>
                @break
            @case(\App\Models\Lesson::TYPE_AUDIO)
                <div class="{{ $studioMode ? 'flex h-full w-full items-center justify-center bg-stone-950 px-6' : 'rounded-xl border border-stone-200 bg-stone-50 p-4' }}">
                    <div class="{{ $studioMode ? 'w-full max-w-xl rounded-2xl border border-white/10 bg-stone-900 p-6 shadow-xl' : 'w-full' }}">
                        @if ($studioMode)
                            <p class="mb-4 text-center text-sm font-medium text-stone-300">{{ $lesson->title }}</p>
                        @endif
                        <audio src="{{ $mediaUrl }}" controls class="w-full" preload="metadata"></audio>
                    </div>
                </div>
                @break
            @case(\App\Models\Lesson::TYPE_PDF)
                <div class="{{ $studioMode ? 'h-full w-full' : 'min-h-[28rem] w-full overflow-hidden rounded-xl border border-stone-200 bg-stone-50 shadow-inner sm:min-h-[32rem]' }}">
                    <iframe src="{{ $mediaUrl }}" class="{{ $studioMode ? 'h-full w-full' : 'h-[28rem] w-full sm:h-[32rem]' }}" title="PDF"></iframe>
                </div>
                @break
            @default
                <div class="{{ $studioMode ? 'h-full w-full' : 'min-h-[20rem] w-full overflow-hidden rounded-xl border border-stone-200 bg-stone-50' }}">
                    <iframe src="{{ $mediaUrl }}" class="{{ $studioMode ? 'h-full w-full' : 'h-[20rem] w-full' }}" title="Lesson material" allowfullscreen loading="lazy"></iframe>
                </div>
        @endswitch
    </div>
@endif
