<div>
    <h2 class="text-lg font-semibold text-gray-900">Select Services</h2>
    <p class="mt-1 text-sm text-gray-500">Choose the services you'd like to book.</p>

    <div class="mt-6 space-y-3">
        <template x-for="service in allServices" :key="service.id">
            <div
                @click="toggleService(service)"
                :class="{
                    'border-gray-900 ring-1 ring-gray-900': isSelected(service.id),
                    'border-gray-200 hover:border-gray-300': !isSelected(service.id),
                }"
                class="cursor-pointer rounded-lg border bg-white transition"
            >
                <div class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-medium text-gray-900" x-text="service.name"></h3>
                            <span
                                x-show="service.is_featured"
                                class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700"
                            >Featured</span>
                        </div>
                        <p x-show="service.description" class="mt-1 text-sm text-gray-500" x-text="service.description"></p>
                        <div class="mt-2 flex items-center gap-3 text-sm text-gray-500">
                            <span x-text="formatDuration(service.duration_minutes)"></span>
                            <span class="font-medium text-gray-900" x-text="service.formatted_price"></span>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <div
                            :class="{
                                'border-gray-900 bg-gray-900': isSelected(service.id),
                                'border-gray-300': !isSelected(service.id),
                            }"
                            class="flex size-6 items-center justify-center rounded-full border-2 transition"
                        >
                            <svg x-show="isSelected(service.id)" class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="mt-8">
        <button
            @click="nextStep()"
            :disabled="!canContinue"
            class="w-full rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
        >Continue</button>
    </div>
</div>
