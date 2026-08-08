@extends('layouts.app')

@section('title', $tenant->name.' — course reviews')

@section('content')
    @include('coach.partials.header')

    <div class="mb-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-semibold text-stone-900">Course reviews</h2>
        <p class="mt-1 text-sm text-stone-600">Star ratings and comments from enrolled learners. Each learner can submit one review per course.</p>
        <form method="GET" action="{{ route('coach.course-reviews.index', $tenant) }}" class="mt-4 flex flex-wrap items-end gap-3">
            <div class="min-w-[12rem] flex-1">
                <label for="filter-course" class="block text-xs font-medium uppercase tracking-wide text-stone-500">Filter by course</label>
                <select id="filter-course" name="course" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" onchange="this.form.submit()">
                    <option value="">All courses</option>
                    @foreach ($coursesForFilter as $c)
                        <option value="{{ $c->id }}" @selected($selectedCourseId === $c->id)>{{ $c->title }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($reviews->isEmpty())
        <p class="text-sm text-stone-600">No reviews yet.</p>
    @else
        <div class="overflow-x-auto rounded-2xl border border-stone-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Learner</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Rating</th>
                        <th class="px-4 py-3">Comment</th>
                        <th class="px-4 py-3">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($reviews as $review)
                        <tr>
                            <td class="px-4 py-3 align-top text-stone-900">{{ $review->user->name ?? $review->user->email }}</td>
                            <td class="px-4 py-3 align-top text-stone-800">{{ $review->course->title }}</td>
                            <td class="px-4 py-3 align-top">
                                @include('learn.partials.course-rating-stars', ['rating' => $review->rating, 'size' => 'sm'])
                            </td>
                            <td class="max-w-md px-4 py-3 align-top text-stone-700">
                                @if ($review->comment)
                                    <p class="whitespace-pre-wrap">{{ $review->comment }}</p>
                                @else
                                    <span class="text-stone-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-stone-500">
                                {{ $review->updated_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $reviews->links() }}</div>
    @endif
@endsection
