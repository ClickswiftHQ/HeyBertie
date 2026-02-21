@php
    $addressParts = array_filter([
        $location->address_line_1,
        $location->address_line_2,
        $location->city,
        $location->county,
        $location->postcode,
    ]);

    $directionsUrl = ($location->latitude && $location->longitude)
        ? 'https://www.google.com/maps/dir/?api=1&destination=' . $location->latitude . ',' . $location->longitude
        : 'https://www.google.com/maps/search/?api=1&query=' . urlencode(implode(', ', $addressParts));
@endphp

<section>
    <h2 class="text-xl font-semibold text-gray-900">Location</h2>

    <div class="mt-4 rounded-lg border border-gray-200 p-4">
        <div class="flex items-start gap-3">
            {{-- MapPin icon --}}
            <svg class="mt-0.5 size-5 shrink-0 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
            <div>
                <p class="text-sm text-gray-900">{{ $location->address_line_1 }}</p>
                @if ($location->address_line_2)
                    <p class="text-sm text-gray-900">{{ $location->address_line_2 }}</p>
                @endif
                <p class="text-sm text-gray-900">
                    {{ $location->city }}@if ($location->county), {{ $location->county }}@endif
                    {{ $location->postcode }}
                </p>
            </div>
        </div>

        <a
            href="{{ $directionsUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
            {{-- Navigation icon --}}
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
            Get Directions
            {{-- ExternalLink icon --}}
            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
        </a>

        @if ($location->is_mobile && $location->service_radius_km)
            <div class="mt-3 rounded-md bg-gray-50 p-3 text-sm text-gray-500">
                <p class="font-medium text-gray-900">Mobile Service Area</p>
                <p class="mt-1">
                    We come to you! Service radius: {{ $location->service_radius_km }} km from {{ $location->city }}.
                </p>
            </div>
        @endif
    </div>
</section>
