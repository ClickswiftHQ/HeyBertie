@php
    $lowestPrice = $services->whereNotNull('price')->min('price');
@endphp

<div
    x-data="{ visible: false }"
    x-on:scroll.window="visible = window.scrollY > 400"
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full"
    x-transition:enter-end="translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="translate-y-0"
    x-transition:leave-end="translate-y-full"
    x-cloak
    class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white p-3 lg:hidden"
>
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-gray-900">{{ $business->name }}</p>
            @if ($lowestPrice !== null)
                <p class="text-xs text-gray-500">From £{{ number_format($lowestPrice, 0) }}</p>
            @endif
        </div>
        @if ($canBook)
            <a href="#" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Book Now</a>
        @elseif ($business->phone)
            <a href="tel:{{ $business->phone }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Call</a>
        @else
            <span class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-400">Contact</span>
        @endif
    </div>
</div>
