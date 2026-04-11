@php
    $coachFirstProgramId = \App\Models\Program::query()
        ->where('tenant_id', $tenant->id)
        ->orderBy('sort_order')
        ->value('id');
@endphp
<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-teal-700">Coach workspace</p>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $tenant->name }}</h1>
            <p class="mt-1 text-xs text-stone-500" title="Programs contain courses. Courses may use modules and/or top-level lessons. Reflection prompts are managed under Daily reflections.">Curriculum &amp; reflections</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('public.catalog', $tenant) }}" class="text-sm font-medium text-teal-800 hover:underline" title="Visitor-facing page: published catalog only.">Catalog</a>
            <a href="{{ route('learn.catalog', $tenant) }}" class="text-sm font-medium text-stone-600 hover:text-teal-800">View as learner</a>
            <a href="{{ route('my-coaching') }}" class="text-sm text-teal-700 hover:underline">My coaching</a>
        </div>
    </div>
    <nav class="mt-4 flex flex-wrap gap-x-1 gap-y-2 border-b border-stone-200 pb-3 text-sm" aria-label="Coach sections">
        <a href="{{ route('coach.programs.index', $tenant) }}" class="rounded-full px-3 py-1.5 hover:bg-stone-100 {{ request()->routeIs('coach.programs.*') ? 'bg-teal-50 font-medium text-teal-900' : 'text-stone-600' }}">Programs</a>
        <a href="{{ $coachFirstProgramId ? route('coach.courses.index', ['tenant' => $tenant, 'program_id' => $coachFirstProgramId]) : route('coach.programs.index', $tenant) }}" class="rounded-full px-3 py-1.5 hover:bg-stone-100 {{ request()->routeIs('coach.courses.*') && ! request()->routeIs('coach.courses.standalone.*') ? 'bg-teal-50 font-medium text-teal-900' : 'text-stone-600' }}">Program courses</a>
        <a href="{{ route('coach.courses.standalone.index', $tenant) }}" class="rounded-full px-3 py-1.5 hover:bg-stone-100 {{ request()->routeIs('coach.courses.standalone.*') ? 'bg-teal-50 font-medium text-teal-900' : 'text-stone-600' }}">Single courses</a>
        <a href="{{ route('coach.modules.index', $tenant) }}" class="rounded-full px-3 py-1.5 hover:bg-stone-100 {{ request()->routeIs('coach.modules.*') ? 'bg-teal-50 font-medium text-teal-900' : 'text-stone-600' }}">Modules</a>
        <a href="{{ route('coach.lessons.index', $tenant) }}" class="rounded-full px-3 py-1.5 hover:bg-stone-100 {{ request()->routeIs('coach.lessons.*') ? 'bg-teal-50 font-medium text-teal-900' : 'text-stone-600' }}">Lessons</a>
        <a href="{{ route('coach.reflections.index', $tenant) }}" class="rounded-full px-3 py-1.5 hover:bg-stone-100 {{ request()->routeIs('coach.reflections.*') && ! request()->routeIs('coach.reflections.submissions.*') ? 'bg-amber-50 font-medium text-amber-950' : 'text-stone-600' }}">Prompts</a>
        <a href="{{ route('coach.learner-submissions.index', $tenant) }}" class="rounded-full px-3 py-1.5 hover:bg-stone-100 {{ request()->routeIs('coach.learner-submissions.*') ? 'bg-amber-50 font-medium text-amber-950' : 'text-stone-600' }}">Learner submissions</a>
    </nav>
</div>
