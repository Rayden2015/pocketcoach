@php
    $m = $memberships->get($tenant->id);
    $isStaff = $m && in_array($m->role, \App\Enums\TenantRole::staffValues(), true);
    $snap = ($coachSnapshots ?? [])[$tenant->id] ?? null;
@endphp
<li class="rounded-2xl border border-stone-200 bg-stone-50/40 p-3 shadow-sm ring-1 ring-stone-100/80 sm:p-4 @if ($isStaff) ring-teal-100/60 @endif">
    <div class="flex flex-col gap-3">
        <div class="min-w-0 rounded-2xl border px-3 py-3 shadow-sm @if ($isStaff) border-teal-100 bg-teal-50/60 @else border-stone-200 bg-white @endif">
            @if ($isStaff)
                <p class="text-[10px] font-semibold uppercase tracking-wide text-teal-800">Space you lead</p>
            @else
                <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Learning as a member</p>
            @endif
            <h2 class="mt-1 text-xl font-bold tracking-tight text-stone-900">{{ $tenant->name }}</h2>
            <p class="mt-1 text-[10px] leading-snug text-stone-600">{{ $tenant->slug }} @if ($m) · <span class="font-medium text-stone-800">{{ $m->role }}</span> @else · <span class="text-amber-700">Enrolled only — join as member from catalog if needed</span> @endif</p>
            <p class="mt-2 break-all text-[10px] font-semibold uppercase tracking-wide text-stone-500">
                Public link
            </p>
            <p class="mt-0.5 break-all text-[10px] leading-snug text-stone-600">
                <a href="{{ route('public.catalog', $tenant) }}" class="font-medium text-teal-800 underline decoration-teal-500/50 underline-offset-2 hover:text-teal-900 hover:decoration-teal-600">{{ $tenant->publicUrl('catalog') }}</a>
            </p>
        </div>

        @if ($isStaff && $snap)
            <div class="grid w-full grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3" role="list">
                <div class="min-w-0 rounded-2xl border border-stone-200 bg-white px-3 py-3 shadow-sm" role="listitem">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Learners enrolled</p>
                    <p class="mt-1 text-xl font-bold tabular-nums text-stone-900">{{ $snap['learners_with_enrollment'] }}</p>
                    <p class="text-[10px] leading-snug text-stone-500">{{ $snap['active_enrollments'] }} active seats</p>
                </div>
                <div class="min-w-0 rounded-2xl border border-stone-200 bg-white px-3 py-3 shadow-sm" role="listitem">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Members (learner role)</p>
                    <p class="mt-1 text-xl font-bold tabular-nums text-stone-900">{{ $snap['learner_members'] }}</p>
                </div>
                <div class="min-w-0 rounded-2xl border border-stone-200 bg-white px-3 py-3 shadow-sm" role="listitem">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Programs live / draft</p>
                    <p class="mt-1 text-xl font-bold tabular-nums text-stone-900">{{ $snap['programs_live'] }} / {{ $snap['programs_draft'] }}</p>
                </div>
                <div class="min-w-0 rounded-2xl border border-stone-200 bg-white px-3 py-3 shadow-sm" role="listitem">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Completions (7d)</p>
                    <p class="mt-1 text-xl font-bold tabular-nums text-stone-900">{{ $snap['lesson_completions_7d'] }}</p>
                    <p class="text-[10px] leading-snug text-stone-500">{{ $snap['courses_live'] }} courses · {{ $snap['reflection_prompts_live'] }} live prompts</p>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-4 flex flex-wrap gap-2 text-sm">
        @if ($isStaff)
            <a href="{{ route('coach.programs.index', $tenant) }}" class="rounded-full bg-teal-600 px-4 py-2 font-medium text-white hover:bg-teal-700">Programs &amp; courses</a>
            <a href="{{ route('coach.reflections.index', $tenant) }}" class="rounded-full border border-amber-200 bg-amber-50 px-4 py-2 font-medium text-amber-950 hover:bg-amber-100">Daily reflections</a>
            <a href="{{ route('coach.learner-submissions.index', $tenant) }}" class="rounded-full border border-stone-300 bg-white px-3 py-2 font-medium text-stone-800 hover:border-amber-300">Learner submissions</a>
            <a href="{{ route('public.catalog', $tenant) }}" class="rounded-full border border-stone-300 px-3 py-2 text-stone-800 hover:border-teal-400" title="Published programs and courses visitors can browse (sign-in optional). Drafts and unpublished items stay in the coach workspace only.">Catalog</a>
        @endif
        <a href="{{ route('learn.catalog', $tenant) }}" class="rounded-full border border-stone-300 px-3 py-2 hover:border-teal-400 hover:text-teal-800">Learner catalog</a>
        <a href="{{ route('learn.continue', $tenant) }}" class="rounded-full bg-stone-900 px-3 py-2 text-white hover:bg-stone-800">Continue learning</a>
    </div>
</li>
