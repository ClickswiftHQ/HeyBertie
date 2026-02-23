@extends('layouts.marketing')

@section('title', 'Booking ' . $booking->booking_reference . ' — heyBertie')

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6" x-data="{ showCancelModal: false }">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Status Badge & Reference --}}
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

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Booking Details</h1>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $badgeClass }}">
                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
            </span>
        </div>

        {{-- Reference --}}
        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-center">
            <p class="text-sm text-gray-500">Booking Reference</p>
            <p class="mt-1 text-2xl font-bold tracking-wider text-gray-900">{{ $booking->booking_reference }}</p>
        </div>

        {{-- Booking Details --}}
        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="font-semibold text-gray-900">{{ $booking->business->name }}</h2>
            <p class="text-sm text-gray-500">{{ $booking->location->name }} &middot; {{ $booking->location->city }}</p>

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
                            @if ($booking->pet_size)
                                &mdash; {{ ucfirst($booking->pet_size) }}
                            @endif
                        </span>
                    </div>
                @endif

                {{-- Staff --}}
                @if ($booking->staffMember)
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <span class="text-gray-900">{{ $booking->staffMember->name }}</span>
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

            {{-- Location --}}
            <div class="mt-4 border-t border-gray-200 pt-4 text-sm text-gray-600">
                <p class="font-medium text-gray-900">Location</p>
                <p class="mt-1">{{ $booking->location->address_line_1 }}@if ($booking->location->address_line_2), {{ $booking->location->address_line_2 }}@endif</p>
                <p>{{ $booking->location->city }}, {{ $booking->location->postcode }}</p>
            </div>
        </div>

        {{-- Actions --}}
        @if ($rescheduleUrl || $cancelUrl)
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                @if ($rescheduleUrl)
                    <a href="{{ $rescheduleUrl }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Reschedule
                    </a>
                @endif
                @if ($cancelUrl)
                    <button @click="showCancelModal = true" class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-6 py-3 text-sm font-medium text-red-600 hover:bg-red-50">
                        Cancel Booking
                    </button>
                @endif
            </div>
        @endif

        {{-- Back link --}}
        @auth
            <div class="mt-8 text-center">
                <a href="{{ route('customer.bookings.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                    &larr; Back to My Bookings
                </a>
            </div>
        @endauth

        {{-- Cancel Modal --}}
        @if ($cancelUrl)
            <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showCancelModal = false">
                <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.stop>
                    <h3 class="text-lg font-semibold text-gray-900">Cancel Booking</h3>
                    <p class="mt-2 text-sm text-gray-500">Are you sure you want to cancel this booking? This action cannot be undone.</p>

                    <form method="POST" action="{{ $cancelUrl }}">
                        @csrf
                        <div class="mt-4">
                            <label for="reason" class="block text-sm font-medium text-gray-700">Reason (optional)</label>
                            <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" placeholder="Let us know why you're cancelling..."></textarea>
                        </div>

                        <div class="mt-6 flex gap-3">
                            <button type="button" @click="showCancelModal = false" class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Keep Booking
                            </button>
                            <button type="submit" class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700">
                                Cancel Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
