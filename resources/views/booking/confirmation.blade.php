@extends('layouts.marketing')

@section('title', 'Booking Confirmed — ' . $business->name)

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
        {{-- Success Icon --}}
        <div class="text-center">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100">
                <svg class="size-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="mt-4 text-2xl font-semibold text-gray-900">Booking Confirmed</h1>
            <p class="mt-2 text-gray-500">Your appointment has been booked. We'll be in touch to confirm.</p>
        </div>

        {{-- Reference --}}
        <div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-4 text-center">
            <p class="text-sm text-gray-500">Booking Reference</p>
            <p class="mt-1 text-2xl font-bold tracking-wider text-gray-900">{{ $booking->booking_reference }}</p>
        </div>

        {{-- Booking Details --}}
        <div class="mt-8 rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="font-semibold text-gray-900">{{ $business->name }}</h2>
            <p class="text-sm text-gray-500">{{ $location->name }} &middot; {{ $location->city }}</p>

            <div class="mt-4 space-y-3 border-t border-gray-200 pt-4">
                {{-- Date & Time --}}
                <div class="flex items-center gap-2 text-sm">
                    <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span class="text-gray-900">{{ $booking->appointment_datetime->format('l j F Y') }} at {{ $booking->appointment_datetime->format('g:i A') }}</span>
                </div>

                {{-- Duration --}}
                <div class="flex items-center gap-2 text-sm">
                    <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    @php
                        $mins = $booking->duration_minutes;
                        $durationText = $mins < 60 ? $mins . ' min' : intdiv($mins, 60) . 'h' . ($mins % 60 > 0 ? ' ' . ($mins % 60) . 'm' : '');
                    @endphp
                    <span class="text-gray-900">{{ $durationText }}</span>
                </div>

                {{-- Pet --}}
                @if ($booking->pet_name)
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                        </svg>
                        <span class="text-gray-900">
                            {{ $booking->pet_name }}
                            @if ($booking->pet_breed)
                                ({{ $booking->pet_breed }})
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            {{-- Services --}}
            @if ($booking->items->isNotEmpty())
                <div class="mt-4 space-y-2 border-t border-gray-200 pt-4">
                    @foreach ($booking->items as $item)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-900">{{ $item->service_name }}</span>
                            <span class="font-medium text-gray-900">&pound;{{ number_format((float) $item->price, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Total --}}
            <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                <span class="font-medium text-gray-900">Total</span>
                <span class="text-lg font-semibold text-gray-900">&pound;{{ number_format((float) $booking->price, 2) }}</span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-8 flex flex-col items-center gap-3">
            <a href="{{ $manageUrl }}" class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Manage Booking
            </a>
            <a href="{{ route('business.location', [$business->handle, $location->slug]) }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                &larr; Back to {{ $business->name }}
            </a>
        </div>
    </div>
@endsection
