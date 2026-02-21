@if ($rating['count'] === 0)
    <section>
        <h2 class="text-xl font-semibold text-gray-900">Reviews</h2>
        <p class="mt-3 text-gray-500">No reviews yet. Be the first to leave a review!</p>
    </section>
@else
    <section>
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Reviews</h2>
            <div class="flex items-center gap-1.5 text-sm">
                {{-- Star icon --}}
                <svg class="size-4 fill-amber-400 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <span class="font-medium text-gray-900">{{ number_format($rating['average'], 1) }}</span>
                <span class="text-gray-500">({{ $rating['count'] }})</span>
            </div>
        </div>

        <div class="mt-4">
            @include('listing.partials.rating-breakdown')
        </div>

        <div class="mt-6 space-y-3">
            @foreach ($reviews as $review)
                @include('listing.partials.review-card', ['review' => $review])
            @endforeach
        </div>

        @if ($hasMoreReviews)
            <p class="mt-4 text-center text-sm text-gray-500">More reviews available</p>
        @endif
    </section>
@endif
