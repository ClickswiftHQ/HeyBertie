@extends('layouts.marketing')

@section('title', 'Book an Appointment — ' . $business->name)

@section('content')
    <div
        x-data="bookingFlow({
            services: {{ Js::from($services->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'duration_minutes' => $s->duration_minutes,
                'price' => (float) $s->price,
                'price_type' => $s->price_type,
                'formatted_price' => $s->getFormattedPrice(),
                'is_featured' => $s->is_featured,
            ])) }},
            staff: {{ Js::from($staff->map(fn ($s) => [
                'id' => $s->id,
                'display_name' => $s->display_name,
                'bio' => $s->bio,
                'photo_url' => $s->photo_url,
                'role' => $s->role,
            ])) }},
            staffSelectionEnabled: {{ Js::from($staffSelectionEnabled) }},
            preselectedServiceIds: {{ Js::from($preselectedServiceIds) }},
            locationId: {{ $location->id }},
            breeds: {{ Js::from($breeds) }},
            storeUrl: '{{ route('booking.store', [$business->handle, $location->slug]) }}',
            availableDatesUrl: '{{ route('api.booking.available-dates', $location->id) }}',
            timeSlotsUrl: '{{ route('api.booking.time-slots', $location->id) }}',
            csrfToken: '{{ csrf_token() }}',
        })"
        class="mx-auto max-w-6xl px-4 py-8 sm:px-6"
    >
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-3">
                @if ($business->logo_url)
                    <img src="{{ $business->logo_url }}" alt="{{ $business->name }}" class="size-10 rounded-lg object-cover">
                @endif
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ $business->name }}</h1>
                    <p class="text-sm text-gray-500">{{ $location->name }} &middot; {{ $location->city }}</p>
                </div>
            </div>
        </div>

        {{-- Step Indicator --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm">
                <template x-for="(stepInfo, index) in visibleSteps" :key="index">
                    <div class="flex items-center gap-2">
                        <span x-show="index > 0" class="text-gray-300">/</span>
                        <button
                            @click="goToStep(stepInfo.key)"
                            :class="{
                                'font-medium text-gray-900': currentStep === stepInfo.key,
                                'text-gray-400 hover:text-gray-600': currentStep !== stepInfo.key && canGoToStep(stepInfo.key),
                                'text-gray-300 cursor-default': !canGoToStep(stepInfo.key),
                            }"
                            :disabled="!canGoToStep(stepInfo.key)"
                            x-text="stepInfo.label"
                        ></button>
                    </div>
                </template>
            </div>
        </div>

        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
            {{-- Main Panel --}}
            <div class="lg:col-span-2">
                {{-- Step 1: Services --}}
                <div x-show="currentStep === 'services'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    @include('booking.partials.step-services')
                </div>

                {{-- Step 2: Staff --}}
                <div x-show="currentStep === 'staff'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    @include('booking.partials.step-staff')
                </div>

                {{-- Step 3: Date & Time --}}
                <div x-show="currentStep === 'datetime'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    @include('booking.partials.step-datetime')
                </div>

                {{-- Step 4: Details --}}
                <div x-show="currentStep === 'details'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    @include('booking.partials.step-details')
                </div>
            </div>

            {{-- Basket Summary (Desktop) --}}
            <div class="hidden lg:block">
                @include('booking.partials.basket-summary')
            </div>
        </div>

        {{-- Basket Summary (Mobile Bottom Sheet) --}}
        <div class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white p-3 lg:hidden" x-show="selectedServices.length > 0">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                        <span x-text="selectedServices.length"></span> <span x-text="selectedServices.length === 1 ? 'service' : 'services'"></span>
                        &middot; <span x-text="formatDuration(totalDuration)"></span>
                    </p>
                    <p class="text-xs font-medium text-gray-900" x-text="'£' + totalPrice.toFixed(2)"></p>
                </div>
                <button
                    @click="nextStep()"
                    :disabled="!canContinue"
                    class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                    x-text="currentStep === 'details' ? 'Confirm Booking' : 'Continue'"
                ></button>
            </div>
        </div>
    </div>

    <script>
        function bookingFlow(config) {
            return {
                // Config
                allServices: config.services,
                allStaff: config.staff,
                allBreeds: config.breeds,
                staffSelectionEnabled: config.staffSelectionEnabled,
                locationId: config.locationId,
                storeUrl: config.storeUrl,
                availableDatesUrl: config.availableDatesUrl,
                timeSlotsUrl: config.timeSlotsUrl,
                csrfToken: config.csrfToken,

                // State
                currentStep: 'services',
                selectedServices: [],
                selectedStaffId: null,
                selectedDate: null,
                selectedTime: null,
                availableDates: [],
                timeSlots: [],
                loadingDates: false,
                loadingSlots: false,
                submitting: false,
                submitError: null,

                // Form fields
                form: {
                    name: '{{ auth()->user()?->name ?? '' }}',
                    email: '{{ auth()->user()?->email ?? '' }}',
                    phone: '',
                    pet_name: '',
                    pet_breed: '',
                    pet_size: '',
                    notes: '',
                },

                // Breed autocomplete
                breedQuery: '',
                breedSuggestions: [],
                breedDropdownOpen: false,
                breedHighlightIndex: -1,

                // Computed
                get visibleSteps() {
                    let steps = [{ key: 'services', label: 'Services' }];
                    if (this.staffSelectionEnabled && this.allStaff.length > 0) {
                        steps.push({ key: 'staff', label: 'Staff' });
                    }
                    steps.push({ key: 'datetime', label: 'Date & Time' });
                    steps.push({ key: 'details', label: 'Your Details' });
                    return steps;
                },

                get totalDuration() {
                    return this.selectedServices.reduce((sum, s) => sum + s.duration_minutes, 0);
                },

                get totalPrice() {
                    return this.selectedServices.reduce((sum, s) => sum + (s.price || 0), 0);
                },

                get canContinue() {
                    if (this.currentStep === 'services') return this.selectedServices.length > 0;
                    if (this.currentStep === 'staff') return true;
                    if (this.currentStep === 'datetime') return this.selectedDate && this.selectedTime;
                    if (this.currentStep === 'details') return this.form.name && this.form.email && this.form.phone && this.form.pet_name && !this.submitting;
                    return false;
                },

                // Init
                init() {
                    if (config.preselectedServiceIds.length > 0) {
                        this.selectedServices = this.allServices.filter(s => config.preselectedServiceIds.includes(s.id));
                    }
                },

                // Methods
                toggleService(service) {
                    const index = this.selectedServices.findIndex(s => s.id === service.id);
                    if (index >= 0) {
                        this.selectedServices.splice(index, 1);
                    } else {
                        this.selectedServices.push(service);
                    }
                },

                isSelected(serviceId) {
                    return this.selectedServices.some(s => s.id === serviceId);
                },

                canGoToStep(step) {
                    const stepOrder = this.visibleSteps.map(s => s.key);
                    const targetIndex = stepOrder.indexOf(step);
                    const currentIndex = stepOrder.indexOf(this.currentStep);
                    return targetIndex <= currentIndex;
                },

                goToStep(step) {
                    if (this.canGoToStep(step)) {
                        this.currentStep = step;
                    }
                },

                nextStep() {
                    const stepOrder = this.visibleSteps.map(s => s.key);
                    const currentIndex = stepOrder.indexOf(this.currentStep);

                    if (this.currentStep === 'details') {
                        this.submitBooking();
                        return;
                    }

                    if (currentIndex < stepOrder.length - 1) {
                        const nextStepKey = stepOrder[currentIndex + 1];
                        this.currentStep = nextStepKey;

                        if (nextStepKey === 'datetime') {
                            this.fetchAvailableDates();
                        }
                    }
                },

                prevStep() {
                    const stepOrder = this.visibleSteps.map(s => s.key);
                    const currentIndex = stepOrder.indexOf(this.currentStep);
                    if (currentIndex > 0) {
                        this.currentStep = stepOrder[currentIndex - 1];
                    }
                },

                selectStaff(staffId) {
                    this.selectedStaffId = staffId;
                    this.selectedDate = null;
                    this.selectedTime = null;
                    this.timeSlots = [];
                },

                async fetchAvailableDates() {
                    this.loadingDates = true;
                    try {
                        const params = new URLSearchParams({
                            duration: this.totalDuration,
                        });
                        if (this.selectedStaffId) {
                            params.set('staff', this.selectedStaffId);
                        }
                        const res = await fetch(`${this.availableDatesUrl}?${params}`);
                        const data = await res.json();
                        this.availableDates = data.dates;

                        // Auto-select first available date
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
                        const params = new URLSearchParams({
                            date: date,
                            duration: this.totalDuration,
                        });
                        if (this.selectedStaffId) {
                            params.set('staff', this.selectedStaffId);
                        }
                        const res = await fetch(`${this.timeSlotsUrl}?${params}`);
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

                async submitBooking() {
                    this.submitting = true;
                    this.submitError = null;

                    try {
                        const res = await fetch(this.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                service_ids: this.selectedServices.map(s => s.id),
                                staff_member_id: this.selectedStaffId,
                                appointment_datetime: this.selectedDate + ' ' + this.selectedTime + ':00',
                                name: this.form.name,
                                email: this.form.email,
                                phone: this.form.phone,
                                pet_name: this.form.pet_name,
                                pet_breed: this.form.pet_breed || null,
                                pet_size: this.form.pet_size || null,
                                notes: this.form.notes || null,
                            }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            this.submitError = data.message || 'Something went wrong. Please try again.';
                        }
                    } catch (e) {
                        this.submitError = 'Something went wrong. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                },

                filterBreeds() {
                    const q = this.breedQuery.toLowerCase().trim();
                    if (q.length === 0) {
                        this.breedSuggestions = [];
                        this.breedDropdownOpen = false;
                        return;
                    }
                    this.breedSuggestions = this.allBreeds.filter(b => b.name.toLowerCase().includes(q)).slice(0, 8);
                    this.breedDropdownOpen = this.breedSuggestions.length > 0;
                    this.breedHighlightIndex = -1;
                },

                selectBreed(breed) {
                    this.form.pet_breed = breed.name;
                    this.breedQuery = breed.name;
                    this.breedDropdownOpen = false;
                    this.breedSuggestions = [];
                },

                handleBreedKeydown(e) {
                    if (!this.breedDropdownOpen) return;
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        this.breedHighlightIndex = Math.min(this.breedHighlightIndex + 1, this.breedSuggestions.length - 1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        this.breedHighlightIndex = Math.max(this.breedHighlightIndex - 1, 0);
                    } else if (e.key === 'Enter' && this.breedHighlightIndex >= 0) {
                        e.preventDefault();
                        this.selectBreed(this.breedSuggestions[this.breedHighlightIndex]);
                    } else if (e.key === 'Escape') {
                        this.breedDropdownOpen = false;
                    }
                },

                formatDuration(minutes) {
                    if (minutes < 60) return minutes + ' min';
                    const hrs = Math.floor(minutes / 60);
                    const mins = minutes % 60;
                    return mins > 0 ? `${hrs}h ${mins}m` : `${hrs}h`;
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

                get selectedStaffName() {
                    if (!this.selectedStaffId) return 'Any available';
                    const staff = this.allStaff.find(s => s.id === this.selectedStaffId);
                    return staff ? staff.display_name : 'Any available';
                },
            };
        }
    </script>
@endsection
