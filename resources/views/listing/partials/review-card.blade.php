@php
    $reviewerName = $review->user?->name ?? 'Anonymous';
    $parts = explode(' ', trim($reviewerName));
    $formattedName = count($parts) >= 2
        ? $parts[0] . ' ' . strtoupper(substr(end($parts), 0, 1)) . '.'
        : $parts[0];
@endphp

<div class="rounded-lg border border-gray-200 p-4">
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-1">
                @for ($i = 0; $i < 5; $i++)
                    <svg class="size-4 {{ $i < $review->rating ? 'fill-amber-400 text-amber-400' : 'text-gray-200' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                @endfor
            </div>
            <div class="mt-1 flex items-center gap-2 text-sm">
                <span class="font-medium text-gray-900">{{ $formattedName }}</span>
                @if ($review->is_verified)
                    <span class="inline-flex items-center gap-0.5 text-xs text-green-600">
                        <svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.77 4 4 0 0 1 0 6.76 4 4 0 0 1-4.78 4.77 4 4 0 0 1-6.74 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
                        Verified
                    </span>
                @endif
                <span class="text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    @if ($review->review_text)
        <p class="mt-2 text-sm text-gray-500">{{ $review->review_text }}</p>
    @endif

    @if ($review->response_text)
        <div class="mt-3 rounded-md bg-gray-50 p-3">
            <div class="flex items-center gap-1.5 text-xs font-medium text-gray-900">
                {{-- MessageSquare icon --}}
                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Business response
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ $review->response_text }}</p>
        </div>
    @endif
</div>
