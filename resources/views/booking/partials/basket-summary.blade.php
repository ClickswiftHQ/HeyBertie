<div class="sticky top-6 rounded-lg border border-gray-200 bg-white">
    <div class="p-5">
        <h3 class="font-semibold text-gray-900">{{ $business->name }}</h3>
        <p class="text-sm text-gray-500">{{ $location->name }}</p>

        {{-- Selected services --}}
        <div class="mt-4 space-y-3" x-show="selectedServices.length > 0">
            <template x-for="service in selectedServices" :key="service.id">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium text-gray-900" x-text="service.name"></p>
                        <p class="text-xs text-gray-500" x-text="formatDuration(service.duration_minutes)"></p>
                    </div>
                    <p class="shrink-0 text-sm font-medium text-gray-900" x-text="service.formatted_price"></p>
                </div>
            </template>
        </div>

        <div x-show="selectedServices.length === 0" class="mt-4">
            <p class="text-sm text-gray-400">No services selected yet.</p>
        </div>

        {{-- Totals --}}
        <div x-show="selectedServices.length > 0" class="mt-4 border-t border-gray-200 pt-4">
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>
                    <span x-text="selectedServices.length"></span>
                    <span x-text="selectedServices.length === 1 ? 'service' : 'services'"></span>
                    &middot; <span x-text="formatDuration(totalDuration)"></span>
                </span>
            </div>
            <div class="mt-1 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-900">Total</span>
                <span class="text-lg font-semibold text-gray-900" x-text="'£' + totalPrice.toFixed(2)"></span>
            </div>
        </div>

        {{-- Date/time and staff (shown when selected) --}}
        <div x-show="selectedDate" class="mt-4 border-t border-gray-200 pt-4 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span>
                    <span x-text="formatDate(selectedDate).day"></span>
                    <span x-text="formatDate(selectedDate).date"></span>
                    <span x-text="formatDate(selectedDate).month"></span>
                    <template x-if="selectedTime">
                        <span> &middot; <span x-text="formatTime12(selectedTime)"></span></span>
                    </template>
                </span>
            </div>
        </div>

        <div x-show="staffSelectionEnabled && allStaff.length > 0" class="mt-2 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span x-text="selectedStaffName"></span>
            </div>
        </div>
    </div>
</div>
