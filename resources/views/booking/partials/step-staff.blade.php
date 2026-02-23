<div>
    <h2 class="text-lg font-semibold text-gray-900">Choose a Staff Member</h2>
    <p class="mt-1 text-sm text-gray-500">Pick who you'd like for your appointment, or let us assign someone.</p>

    <div class="mt-6 space-y-3">
        {{-- Anyone available option --}}
        <div
            @click="selectStaff(null)"
            :class="{
                'border-gray-900 ring-1 ring-gray-900': selectedStaffId === null,
                'border-gray-200 hover:border-gray-300': selectedStaffId !== null,
            }"
            class="cursor-pointer rounded-lg border bg-white p-4 transition"
        >
            <div class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-gray-100">
                    <svg class="size-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-1.997M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-medium text-gray-900">Anyone available</h3>
                    <p class="text-sm text-gray-500">We'll assign the first available team member</p>
                </div>
                <div class="shrink-0">
                    <div
                        :class="{
                            'border-gray-900 bg-gray-900': selectedStaffId === null,
                            'border-gray-300': selectedStaffId !== null,
                        }"
                        class="flex size-6 items-center justify-center rounded-full border-2 transition"
                    >
                        <svg x-show="selectedStaffId === null" class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Staff member cards --}}
        <template x-for="member in allStaff" :key="member.id">
            <div
                @click="selectStaff(member.id)"
                :class="{
                    'border-gray-900 ring-1 ring-gray-900': selectedStaffId === member.id,
                    'border-gray-200 hover:border-gray-300': selectedStaffId !== member.id,
                }"
                class="cursor-pointer rounded-lg border bg-white p-4 transition"
            >
                <div class="flex items-center gap-4">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-gray-100">
                        <template x-if="member.photo_url">
                            <img :src="member.photo_url" :alt="member.display_name" class="size-12 rounded-full object-cover">
                        </template>
                        <template x-if="!member.photo_url">
                            <span class="text-sm font-semibold text-gray-600" x-text="member.display_name.charAt(0).toUpperCase()"></span>
                        </template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-medium text-gray-900" x-text="member.display_name"></h3>
                        <p class="text-sm capitalize text-gray-500" x-text="member.role"></p>
                        <p x-show="member.bio" class="mt-0.5 text-sm text-gray-400" x-text="member.bio?.substring(0, 80) + (member.bio?.length > 80 ? '...' : '')"></p>
                    </div>
                    <div class="shrink-0">
                        <div
                            :class="{
                                'border-gray-900 bg-gray-900': selectedStaffId === member.id,
                                'border-gray-300': selectedStaffId !== member.id,
                            }"
                            class="flex size-6 items-center justify-center rounded-full border-2 transition"
                        >
                            <svg x-show="selectedStaffId === member.id" class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="mt-8 flex gap-3">
        <button @click="prevStep()" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Back</button>
        <button @click="nextStep()" class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800">Continue</button>
    </div>
</div>
