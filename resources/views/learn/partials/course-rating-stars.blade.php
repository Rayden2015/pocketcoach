@php
    $rating = $rating ?? null;
    $count = $count ?? null;
    $size = $size ?? 'sm';
    $starClass = $size === 'lg' ? 'h-5 w-5' : 'h-4 w-4';
@endphp
@if ($rating !== null && $rating > 0)
    <span class="inline-flex flex-wrap items-center gap-1 {{ $class ?? '' }}" title="{{ $count !== null ? number_format($rating, 1).' out of 5 ('.$count.' review'.($count === 1 ? '' : 's').')' : number_format($rating, 1).' out of 5' }}">
        <span class="inline-flex text-amber-500" aria-hidden="true">
            @for ($i = 1; $i <= 5; $i++)
                @if ($rating >= $i - 0.25)
                    <svg class="{{ $starClass }}" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                @elseif ($rating >= $i - 0.75)
                    <svg class="{{ $starClass }}" viewBox="0 0 20 20"><defs><linearGradient id="half-{{ $i }}-{{ md5((string) ($rating ?? 0)) }}"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#d6d3d1"/></linearGradient></defs><path fill="url(#half-{{ $i }}-{{ md5((string) ($rating ?? 0)) }})" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                @else
                    <svg class="{{ $starClass }} text-stone-300" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                @endif
            @endfor
        </span>
        <span class="text-xs font-medium text-stone-600">{{ number_format($rating, 1) }}@if ($count !== null)<span class="text-stone-400"> ({{ $count }})</span>@endif</span>
    </span>
@endif
