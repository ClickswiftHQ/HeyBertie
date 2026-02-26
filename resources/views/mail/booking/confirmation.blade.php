<x-mail::message>
# {{ $booking->status === 'confirmed' ? 'Booking Confirmed' : 'Booking Received' }}

Hi {{ $booking->customer->name }},

@if ($booking->status === 'confirmed')
Your booking with **{{ $booking->business->name }}** has been confirmed.
@else
Your booking with **{{ $booking->business->name }}** has been received and is awaiting confirmation from the business.
@endif

**Booking Reference:** {{ $booking->booking_reference }}

**Date:** {{ $booking->appointment_datetime->format('l j F Y') }}<br>
**Time:** {{ $booking->appointment_datetime->format('g:i A') }}<br>
**Duration:** {{ $booking->duration_minutes }} minutes

@if ($booking->pet_name)
**Pet:** {{ $booking->pet_name }}@if ($booking->pet_breed) ({{ $booking->pet_breed }})@endif @if ($booking->pet_size) — {{ ucfirst($booking->pet_size) }}@endif

@endif
@if ($booking->items->isNotEmpty())
<x-mail::table>
| Service | Price |
|:--------|------:|
@foreach ($booking->items as $item)
| {{ $item->service_name }} | &pound;{{ number_format((float) $item->price, 2) }} |
@endforeach
| **Total** | **&pound;{{ number_format((float) $booking->price, 2) }}** |
</x-mail::table>
@endif

**Location:** {{ $booking->location->name }}<br>
{{ $booking->location->address_line_1 }}@if ($booking->location->address_line_2), {{ $booking->location->address_line_2 }}@endif<br>
{{ $booking->location->city }}, {{ $booking->location->postcode }}

@if ($booking->location->phone ?? $booking->business->phone)
**Contact:** {{ $booking->location->phone ?? $booking->business->phone }}
@endif

<x-mail::button :url="$manageUrl">
Manage Your Booking
</x-mail::button>

Thanks,<br>
{{ $booking->business->name }}
</x-mail::message>
