@php
    $business = $locationResult->business;
    $avgRating = $business->getAverageRating();
    $reviewCount = $business->getReviewCount();
    $topServices = $locationResult->services->take(2);
    $distance = round($locationResult->distance, 1);
    $locationTypeLabel = match ($locationResult->location_type) {
        'salon' => 'Salon',
        'mobile' => 'Mobile',
        'home_based' => 'Home-based',
        default => ucfirst($locationResult->location_type),
    };
@endphp

<a
    href="{{ route('business.location', [$business->handle, $locationResult->slug]) }}"
    class="group rounded-xl border-2 border-gray-200 bg-white p-5 transition hover:border-gray-900"
>
    <div class="flex items-start gap-4">
        {{-- Logo --}}
        @if ($business->logo_url)
            <img
                src="{{ $business->logo_url }}"
                alt="{{ $business->name }}"
                class="size-14 shrink-0 rounded-lg border border-gray-200 object-cover"
            >
        @else
            <span class="flex size-14 shrink-0 items-center justify-center rounded-lg border-2 border-gray-200 bg-gray-100 text-lg font-bold text-gray-500">
                {{ str($business->name)->substr(0, 1)->upper() }}
            </span>
        @endif

        <div class="min-w-0 flex-1">
            {{-- Business Name + Verified Badge --}}
            <div class="flex items-center gap-2">
                <h3 class="truncate text-base font-semibold text-gray-900 group-hover:text-gray-700">
                    {{ $business->name }}
                </h3>
                @if ($business->verification_status === 'verified')
                    <svg class="size-5 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>

            {{-- Rating --}}
            <div class="mt-1 flex items-center gap-1.5 text-sm">
                @if ($avgRating)
                    <span class="font-medium text-gray-900">{{ number_format($avgRating, 1) }}</span>
                    <div class="flex text-yellow-400">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= round($avgRating))
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="size-4 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        @endfor
                    </div>
                    <span class="text-gray-500">({{ $reviewCount }})</span>
                @else
                    <span class="text-gray-500">No reviews yet</span>
                @endif
            </div>

            {{-- Location Type + Town + Distance --}}
            <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600">
                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                    {{ $locationTypeLabel }}
                </span>
                <span>{{ $locationResult->town }}{{ $locationResult->town !== $locationResult->city ? ', '.$locationResult->city : '' }}</span>
                <span>&middot;</span>
                <span>{{ $distance }} km</span>
            </div>
        </div>
    </div>

    {{-- Services --}}
    @if ($topServices->isNotEmpty())
        <div class="mt-4 space-y-1.5 border-t border-gray-100 pt-4">
            @foreach ($topServices as $svc)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700">{{ $svc->name }}</span>
                    <span class="font-medium text-gray-900">{{ $svc->getFormattedPrice() }}</span>
                </div>
            @endforeach
        </div>
    @endif
</a>
