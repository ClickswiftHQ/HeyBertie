@php
    $locationLabel = match ($location->location_type) {
        'mobile' => 'Mobile',
        'home' => 'Home-based',
        default => 'Salon',
    };
@endphp

<div class="relative">
    <div class="h-48 w-full bg-gradient-to-r from-gray-200 to-gray-100 sm:h-64">
        @if ($business->cover_image_url)
            <img src="{{ $business->cover_image_url }}" alt="{{ $business->name }} cover" class="size-full object-cover">
        @endif
    </div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="-mt-12 flex flex-col gap-4 sm:-mt-16 sm:flex-row sm:items-end sm:gap-6">
            <div class="size-24 shrink-0 overflow-hidden rounded-xl border-4 border-white bg-gray-100 sm:size-32">
                @if ($business->logo_url)
                    <img src="{{ $business->logo_url }}" alt="{{ $business->name }}" class="size-full object-cover">
                @else
                    <div class="flex size-full items-center justify-center text-3xl font-bold text-gray-500 sm:text-4xl">
                        {{ strtoupper(substr($business->name, 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="flex-1 pb-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $business->name }}</h1>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                            <span>{{ $business->handle }}</span>
                            @if ($business->verification_status === 'verified')
                                <span class="inline-flex items-center gap-1 text-green-600">
                                    {{-- BadgeCheck icon --}}
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.77 4 4 0 0 1 0 6.76 4 4 0 0 1-4.78 4.77 4 4 0 0 1-6.74 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
                                    Verified
                                </span>
                            @endif
                            @if ($rating['count'] > 0)
                                <span class="inline-flex items-center gap-1">
                                    {{-- Star icon --}}
                                    <svg class="size-4 fill-amber-400 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    {{ number_format($rating['average'], 1) }} ({{ $rating['count'] }})
                                </span>
                            @endif
                        </div>
                        <div class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{{ $locationLabel }}</span>
                            <span class="inline-flex items-center gap-1">
                                {{-- MapPin icon --}}
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                                {{ $location->city }}
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-2" x-data>
                        @if ($canBook)
                            <a href="#" class="inline-flex items-center rounded-lg bg-gray-900 px-6 py-2.5 text-sm font-medium text-white hover:bg-gray-800">Book Now</a>
                        @else
                            <a href="#" class="inline-flex items-center rounded-lg border-2 border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Contact to Book</a>
                        @endif
                        @if ($business->phone)
                            <a href="tel:{{ $business->phone }}" class="inline-flex items-center rounded-lg border-2 border-gray-300 px-3 py-2.5 text-gray-700 hover:bg-gray-50">
                                {{-- Phone icon --}}
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span class="sr-only">Call</span>
                            </a>
                        @endif
                        <button
                            @click="$dispatch('share-open')"
                            class="inline-flex items-center rounded-lg border-2 border-gray-300 px-3 py-2.5 text-gray-700 hover:bg-gray-50"
                        >
                            {{-- Share2 icon --}}
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
                            <span class="sr-only">Share</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
