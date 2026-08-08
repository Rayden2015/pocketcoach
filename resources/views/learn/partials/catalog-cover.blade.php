@php
    $imageUrl = $imageUrl ?? null;
    $title = $title ?? '';
    $size = $size ?? 'card';
    $classes = match ($size) {
        'hero' => 'aspect-[21/9] w-full rounded-2xl',
        'program' => 'h-32 w-full rounded-xl sm:h-40',
        default => 'h-20 w-28 shrink-0 rounded-lg',
    };
@endphp
<div class="{{ $classes }} overflow-hidden bg-gradient-to-br from-stone-200 via-stone-100 to-teal-100 {{ $class ?? '' }}">
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="" class="h-full w-full object-cover">
    @else
        <div class="flex h-full w-full items-center justify-center text-stone-400" aria-hidden="true">
            <svg class="{{ $size === 'card' ? 'h-8 w-8' : 'h-12 w-12' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
    @endif
</div>
