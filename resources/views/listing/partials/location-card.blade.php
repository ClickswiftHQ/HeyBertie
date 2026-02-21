@php
    $locationLabel = match ($loc->location_type) {
        'mobile' => 'Mobile',
        'home' => 'Home-based',
        default => 'Salon',
    };
@endphp

<a
    href="{{ route('business.location', [$business->handle, $loc->slug]) }}"
    class="group block rounded-xl border border-gray-200 p-5 transition-colors hover:border-gray-300 hover:bg-gray-50"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate text-base font-semibold text-gray-900 group-hover:underline">{{ $loc->name }}</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if ($loc->address_line_1)
                    {{ $loc->address_line_1 }},
                @endif
                {{ $loc->city }}
                @if ($loc->postcode)
                    {{ $loc->postcode }}
                @endif
            </p>
        </div>
        <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{{ $locationLabel }}</span>
    </div>

    <div class="mt-3 flex items-center justify-between">
        <span class="text-sm font-medium text-gray-900 group-hover:underline">View location &rarr;</span>
    </div>
</a>
