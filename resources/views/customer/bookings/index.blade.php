@extends('layouts.marketing')

@section('title', 'My Bookings — heyBertie')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <h1 class="text-2xl font-semibold text-gray-900">My Bookings</h1>

        {{-- Upcoming --}}
        <section class="mt-8">
            <h2 class="text-lg font-medium text-gray-900">Upcoming</h2>

            @if ($upcomingBookings->isEmpty())
                <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-6 text-center">
                    <p class="text-sm text-gray-500">No upcoming bookings.</p>
                </div>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($upcomingBookings as $booking)
                        @include('customer.bookings.partials.booking-card', ['booking' => $booking])
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Past --}}
        @if ($pastBookings->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-lg font-medium text-gray-900">Past</h2>

                <div class="mt-4 space-y-4">
                    @foreach ($pastBookings as $booking)
                        @include('customer.bookings.partials.booking-card', ['booking' => $booking])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
