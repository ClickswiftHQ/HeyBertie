<div>
    <h2 class="text-lg font-semibold text-gray-900">Choose Date & Time</h2>
    <p class="mt-1 text-sm text-gray-500">Select when you'd like your appointment.</p>

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

    <div class="mt-8 flex gap-3">
        <button @click="prevStep()" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Back</button>
        <button
            @click="nextStep()"
            :disabled="!canContinue"
            class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
        >Continue</button>
    </div>
</div>
