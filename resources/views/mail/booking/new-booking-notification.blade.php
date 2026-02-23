<x-mail::message>
# New Booking Received

A new booking has been made.

**Booking Reference:** {{ $booking->booking_reference }}

## Customer Details

**Name:** {{ $booking->customer->name }}<br>
**Email:** {{ $booking->customer->email }}<br>
@if ($booking->customer->phone)
**Phone:** {{ $booking->customer->phone }}
@endif

## Appointment

**Date:** {{ $booking->appointment_datetime->format('l j F Y') }}<br>
**Time:** {{ $booking->appointment_datetime->format('g:i A') }}<br>
**Duration:** {{ $booking->duration_minutes }} minutes<br>
**Location:** {{ $booking->location->name }}

@if ($booking->staffMember)
**Staff Member:** {{ $booking->staffMember->name }}
@endif

@if ($booking->pet_name)
## Pet

**Name:** {{ $booking->pet_name }}<br>
@if ($booking->pet_breed)
**Breed:** {{ $booking->pet_breed }}<br>
@endif
@if ($booking->pet_size)
**Size:** {{ ucfirst($booking->pet_size) }}
@endif
@endif

@if ($booking->items->isNotEmpty())
## Services

<x-mail::table>
| Service | Duration | Price |
|:--------|:---------|------:|
@foreach ($booking->items as $item)
| {{ $item->service_name }} | {{ $item->duration_minutes }} min | &pound;{{ number_format((float) $item->price, 2) }} |
@endforeach
| **Total** | **{{ $booking->duration_minutes }} min** | **&pound;{{ number_format((float) $booking->price, 2) }}** |
</x-mail::table>
@endif

@if ($booking->customer_notes)
## Customer Notes

{{ $booking->customer_notes }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
