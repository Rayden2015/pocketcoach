{{--
  $tenant, $course, $canAccess, $currentLesson (nullable), $completedLessonIds (iterable of ids)
--}}
@php($completed = collect($completedLessonIds ?? []))
<div class="rounded-2xl border border-stone-200/80 bg-white shadow-sm">
    <div class="border-b border-stone-100 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Course content</p>
        <p class="mt-0.5 text-sm font-semibold text-stone-900">{{ $course->title }}</p>
    </div>
    <div class="max-h-[min(70vh,32rem)] overflow-y-auto">
        @if ($course->rootLessons->isNotEmpty())
            <div class="border-b border-stone-100">
                <p class="bg-stone-50 px-4 py-2 text-xs font-semibold text-stone-600">Lessons</p>
                <ol class="divide-y divide-stone-100">
                    @foreach ($course->rootLessons as $les)
                        @php($isCurrent = isset($currentLesson) && $currentLesson && $currentLesson->is($les))
                        @php($done = $completed->contains($les->id))
                        <li>
                            @if ($canAccess)
                                <a href="{{ route('learn.lesson', [$tenant, $les]) }}"
                                    class="flex items-start gap-2 px-3 py-2.5 text-sm transition {{ $isCurrent ? 'bg-teal-50 text-teal-950' : 'text-stone-800 hover:bg-stone-50' }}">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $done ? 'border-teal-500 bg-teal-500 text-white' : ($isCurrent ? 'border-teal-400 bg-white' : 'border-stone-300 bg-white text-stone-400') }}">
                                        @if ($done)
                                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @else
                                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                                        @endif
                                    </span>
                                    <span class="min-w-0 leading-snug {{ $isCurrent ? 'font-semibold' : '' }}">{{ $les->title }}</span>
                                </a>
                            @else
                                <span class="flex items-start gap-2 px-3 py-2.5 text-sm text-stone-400">
                                    <span class="mt-0.5 h-5 w-5 shrink-0 rounded-full border border-stone-200"></span>
                                    {{ $les->title }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @foreach ($course->modules as $module)
            <div class="border-b border-stone-100 last:border-b-0">
                <p class="bg-stone-50 px-4 py-2 text-xs font-semibold text-stone-600">{{ $module->title }}</p>
                <ol class="divide-y divide-stone-100">
                    @foreach ($module->lessons as $les)
                        @php($isCurrent = isset($currentLesson) && $currentLesson && $currentLesson->is($les))
                        @php($done = $completed->contains($les->id))
                        <li>
                            @if ($canAccess)
                                <a href="{{ route('learn.lesson', [$tenant, $les]) }}"
                                    class="flex items-start gap-2 px-3 py-2.5 text-sm transition {{ $isCurrent ? 'bg-teal-50 text-teal-950' : 'text-stone-800 hover:bg-stone-50' }}">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $done ? 'border-teal-500 bg-teal-500 text-white' : ($isCurrent ? 'border-teal-400 bg-white' : 'border-stone-300 bg-white text-stone-400') }}">
                                        @if ($done)
                                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @else
                                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                                        @endif
                                    </span>
                                    <span class="min-w-0 leading-snug {{ $isCurrent ? 'font-semibold' : '' }}">{{ $les->title }}</span>
                                </a>
                            @else
                                <span class="flex items-start gap-2 px-3 py-2.5 text-sm text-stone-400">
                                    <span class="mt-0.5 h-5 w-5 shrink-0 rounded-full border border-stone-200"></span>
                                    {{ $les->title }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
    </div>
</div>
