@extends('layouts.marketing')

@section('title', 'Reschedule Booking — heyBertie')

@section('content')
    <div
        x-data="rescheduleFlow({
            locationId: {{ $booking->location_id }},
            duration: {{ $booking->duration_minutes }},
            staffId: {{ $booking->staff_member_id ?? 'null' }},
            availableDatesUrl: '{{ route('api.booking.available-dates', $booking->location_id) }}',
            timeSlotsUrl: '{{ route('api.booking.time-slots', $booking->location_id) }}',
            processUrl: '{{ $processUrl }}',
            csrfToken: '{{ csrf_token() }}',
        })"
        class="mx-auto max-w-2xl px-4 py-12 sm:px-6"
    >
        {{-- Header --}}
        <h1 class="text-2xl font-semibold text-gray-900">Reschedule Booking</h1>

        {{-- Booking Summary --}}
        <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-medium text-gray-900">{{ $booking->business->name }}</p>
                    <p class="text-sm text-gray-500">{{ $booking->location->name }}</p>
                </div>
                <span class="text-sm font-medium text-gray-500">{{ $booking->booking_reference }}</span>
            </div>
            <div class="mt-3 text-sm text-gray-600">
                <p><span class="font-medium">Current:</span> {{ $booking->appointment_datetime->format('l j F Y') }} at {{ $booking->appointment_datetime->format('g:i A') }}</p>
                @if ($booking->items->isNotEmpty())
                    <p class="mt-1"><span class="font-medium">Services:</span> {{ $booking->items->pluck('service_name')->join(', ') }}</p>
                @endif
            </div>
        </div>

        {{-- Flash Error --}}
        @if (session('error'))
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        {{-- New Date/Time Selection --}}
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-900">Choose a New Date & Time</h2>
            <p class="mt-1 text-sm text-gray-500">Select when you'd like to reschedule your appointment.</p>

            {{-- Date Slider --}}
            <div class="mt-6">
                <div x-show="loadingDates" class="flex items-center justify-center py-8">
                    <svg class="size-6 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <div x-show="!loadingDates" class="overflow-x-auto pb-2">
                    <div class="flex gap-2" style="min-width: max-content;">
                        <template x-for="dateInfo in availableDates" :key="dateInfo.date">
                            <button
                                @click="dateInfo.available && selectDate(dateInfo.date)"
                                :disabled="!dateInfo.available"
                                :class="{
                                    'border-gray-900 bg-gray-900 text-white': selectedDate === dateInfo.date,
                                    'border-gray-200 bg-white text-gray-900 hover:border-gray-300': selectedDate !== dateInfo.date && dateInfo.available,
                                    'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed': !dateInfo.available,
                                }"
                                class="flex w-16 shrink-0 flex-col items-center rounded-lg border px-3 py-2 text-center transition"
                            >
                                <span class="text-xs font-medium" x-text="formatDate(dateInfo.date).day"></span>
                                <span class="text-lg font-semibold" x-text="formatDate(dateInfo.date).date"></span>
                                <span class="text-xs" x-text="formatDate(dateInfo.date).month"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Time Slots --}}
            <div class="mt-6" x-show="selectedDate">
                <div x-show="loadingSlots" class="flex items-center justify-center py-8">
                    <svg class="size-6 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <div x-show="!loadingSlots && timeSlots.length === 0" class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center">
                    <p class="text-sm text-gray-500">No availability on this date. Please try another day.</p>
                </div>

                <div x-show="!loadingSlots && timeSlots.length > 0" class="space-y-6">
                    {{-- Morning --}}
                    <div x-show="slotsByPeriod.morning.length > 0">
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Morning</h3>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                            <template x-for="slot in slotsByPeriod.morning" :key="slot.time">
                                <button
                                    @click="selectTime(slot.time)"
                                    :class="{
                                        'border-gray-900 bg-gray-900 text-white': selectedTime === slot.time,
                                        'border-gray-200 bg-white text-gray-900 hover:border-gray-300': selectedTime !== slot.time,
                                    }"
                                    class="rounded-lg border px-3 py-2 text-center text-sm font-medium transition"
                                    x-text="formatTime12(slot.time)"
                                ></button>
                            </template>
                        </div>
                    </div>

                    {{-- Afternoon --}}
                    <div x-show="slotsByPeriod.afternoon.length > 0">
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Afternoon</h3>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                            <template x-for="slot in slotsByPeriod.afternoon" :key="slot.time">
                                <button
                                    @click="selectTime(slot.time)"
                                    :class="{
                                        'border-gray-900 bg-gray-900 text-white': selectedTime === slot.time,
                                        'border-gray-200 bg-white text-gray-900 hover:border-gray-300': selectedTime !== slot.time,
                                    }"
                                    class="rounded-lg border px-3 py-2 text-center text-sm font-medium transition"
                                    x-text="formatTime12(slot.time)"
                                ></button>
                            </template>
                        </div>
                    </div>

                    {{-- Evening --}}
                    <div x-show="slotsByPeriod.evening.length > 0">
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Evening</h3>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                            <template x-for="slot in slotsByPeriod.evening" :key="slot.time">
                                <button
                                    @click="selectTime(slot.time)"
                                    :class="{
                                        'border-gray-900 bg-gray-900 text-white': selectedTime === slot.time,
                                        'border-gray-200 bg-white text-gray-900 hover:border-gray-300': selectedTime !== slot.time,
                                    }"
                                    class="rounded-lg border px-3 py-2 text-center text-sm font-medium transition"
                                    x-text="formatTime12(slot.time)"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit Error --}}
        <div x-show="submitError" x-cloak class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-medium text-red-800" x-text="submitError"></p>
        </div>

        {{-- Actions --}}
        <div class="mt-8 flex gap-3">
            <a href="{{ URL::signedRoute('customer.bookings.show', ['ref' => $booking->booking_reference]) }}" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button
                @click="submitReschedule()"
                :disabled="!selectedDate || !selectedTime || submitting"
                class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span x-show="!submitting">Confirm Reschedule</span>
                <span x-show="submitting">Rescheduling...</span>
            </button>
        </div>
    </div>

    <script>
        function rescheduleFlow(config) {
            return {
                selectedDate: null,
                selectedTime: null,
                availableDates: [],
                timeSlots: [],
                loadingDates: false,
                loadingSlots: false,
                submitting: false,
                submitError: null,

                init() {
                    this.fetchAvailableDates();
                },

                async fetchAvailableDates() {
                    this.loadingDates = true;
                    try {
                        const params = new URLSearchParams({ duration: config.duration });
                        if (config.staffId) params.set('staff', config.staffId);
                        const res = await fetch(`${config.availableDatesUrl}?${params}`);
                        const data = await res.json();
                        this.availableDates = data.dates;

                        const firstAvailable = this.availableDates.find(d => d.available);
                        if (firstAvailable) {
                            this.selectDate(firstAvailable.date);
                        }
                    } catch (e) {
                        console.error('Failed to fetch available dates', e);
                    } finally {
                        this.loadingDates = false;
                    }
                },

                async selectDate(date) {
                    this.selectedDate = date;
                    this.selectedTime = null;
                    this.loadingSlots = true;
                    try {
                        const params = new URLSearchParams({ date: date, duration: config.duration });
                        if (config.staffId) params.set('staff', config.staffId);
                        const res = await fetch(`${config.timeSlotsUrl}?${params}`);
                        const data = await res.json();
                        this.timeSlots = data.slots;
                    } catch (e) {
                        console.error('Failed to fetch time slots', e);
                    } finally {
                        this.loadingSlots = false;
                    }
                },

                selectTime(time) {
                    this.selectedTime = time;
                },

                async submitReschedule() {
                    this.submitting = true;
                    this.submitError = null;

                    try {
                        const res = await fetch(config.processUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                                'Accept': 'text/html',
                            },
                            body: JSON.stringify({
                                appointment_datetime: this.selectedDate + ' ' + this.selectedTime + ':00',
                            }),
                            redirect: 'follow',
                        });

                        if (res.redirected) {
                            window.location.href = res.url;
                            return;
                        }

                        if (!res.ok) {
                            const text = await res.text();
                            this.submitError = 'This time slot is not available. Please choose another.';
                        } else {
                            window.location.href = res.url;
                        }
                    } catch (e) {
                        this.submitError = 'Something went wrong. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                },

                formatDate(dateString) {
                    const date = new Date(dateString + 'T12:00:00');
                    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    return {
                        day: days[date.getDay()],
                        date: date.getDate(),
                        month: months[date.getMonth()],
                    };
                },

                formatTime12(time) {
                    const [h, m] = time.split(':');
                    const hour = parseInt(h);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    return `${displayHour}:${m} ${ampm}`;
                },

                get slotsByPeriod() {
                    const groups = { morning: [], afternoon: [], evening: [] };
                    this.timeSlots.forEach(slot => {
                        if (groups[slot.period]) {
                            groups[slot.period].push(slot);
                        }
                    });
                    return groups;
                },
            };
        }
    </script>
@endsection
