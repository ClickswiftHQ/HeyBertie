@php
    $statusColors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-green-100 text-green-800',
        'completed' => 'bg-gray-100 text-gray-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'no_show' => 'bg-orange-100 text-orange-800',
    ];
    $badgeClass = $statusColors[$booking->status] ?? 'bg-gray-100 text-gray-800';
@endphp

<a href="{{ URL::signedRoute('customer.bookings.show', ['ref' => $booking->booking_reference]) }}" class="block rounded-lg border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm">
    <div class="flex items-start justify-between">
        <div>
            <p class="font-medium text-gray-900">{{ $booking->business->name }}</p>
            <p class="mt-0.5 text-sm text-gray-500">{{ $booking->location->name }}</p>
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
            {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
        </span>
    </div>

    <div class="mt-3 flex items-center gap-4 text-sm text-gray-600">
        <div class="flex items-center gap-1.5">
            <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
            {{ $booking->appointment_datetime->format('D j M Y, g:i A') }}
        </div>
    </div>

    @if ($booking->items->isNotEmpty())
        <p class="mt-2 text-sm text-gray-500">{{ $booking->items->pluck('service_name')->join(', ') }}</p>
    @endif
</a>
