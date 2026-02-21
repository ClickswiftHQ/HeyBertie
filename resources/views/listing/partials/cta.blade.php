@php
    $lowestPrice = $services->whereNotNull('price')->min('price');
@endphp

<div class="rounded-lg border border-gray-200 bg-white">
    <div class="p-5">
        @if ($lowestPrice !== null)
            <p class="text-sm text-gray-500">
                From <span class="text-lg font-semibold text-gray-900">£{{ number_format($lowestPrice, 0) }}</span>
            </p>
        @endif

        @if ($rating['count'] > 0)
            <div class="mt-2 flex items-center gap-1 text-sm">
                {{-- Star icon --}}
                <svg class="size-4 fill-amber-400 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <span class="font-medium text-gray-900">{{ number_format($rating['average'], 1) }}</span>
                <span class="text-gray-500">({{ $rating['count'] }} {{ Str::plural('review', $rating['count']) }})</span>
            </div>
        @endif

        <div class="mt-4">
            @if ($canBook)
                <a href="#" class="block rounded-lg bg-gray-900 px-6 py-2.5 text-center text-sm font-medium text-white hover:bg-gray-800">Book Now</a>
            @elseif ($business->phone)
                <a href="tel:{{ $business->phone }}" class="block rounded-lg border-2 border-gray-300 px-6 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">Call to Book</a>
            @else
                <span class="block rounded-lg border-2 border-gray-300 px-6 py-2.5 text-center text-sm font-medium text-gray-400">Contact to Book</span>
            @endif
        </div>
    </div>
</div>
