@php
    $selectedRating = (int) old('rating', $myReview?->rating ?? 0);
@endphp
<section class="mt-8 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-stone-900">Course review</h2>
            <p class="mt-1 text-sm text-stone-600">Share a star rating and optional comment to help your coach and future learners.</p>
        </div>
        @if (($reviewsSummary['reviews_count'] ?? 0) > 0)
            @include('learn.partials.course-rating-stars', [
                'rating' => $reviewsSummary['average_rating'],
                'count' => $reviewsSummary['reviews_count'],
                'size' => 'lg',
            ])
        @endif
    </div>

    <form method="POST" action="{{ route('learn.course.review', [$tenant, $course]) }}" class="mt-5 space-y-4">
        @csrf
        <fieldset>
            <legend class="text-sm font-medium text-stone-700">Your rating</legend>
            <div class="mt-2 flex flex-wrap gap-2">
                @for ($star = 5; $star >= 1; $star--)
                    <label class="cursor-pointer">
                        <input type="radio" name="rating" value="{{ $star }}" class="peer sr-only" @checked($selectedRating === $star) required>
                        <span class="inline-flex items-center gap-1 rounded-full border border-stone-200 px-3 py-1.5 text-sm text-stone-600 transition peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:text-amber-950 hover:border-stone-300">
                            {{ $star }}
                            <svg class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </span>
                    </label>
                @endfor
            </div>
            @error('rating')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>
        <div>
            <label for="review_comment" class="block text-sm font-medium text-stone-700">Comment (optional)</label>
            <textarea id="review_comment" name="comment" rows="4" maxlength="65535"
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                placeholder="What did you find most valuable?">{{ old('comment', $myReview?->comment ?? '') }}</textarea>
            @error('comment')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700">
            {{ $myReview ? 'Update review' : 'Submit review' }}
        </button>
    </form>
</section>
