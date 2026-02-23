<div>
    <h2 class="text-lg font-semibold text-gray-900">Your Details</h2>
    <p class="mt-1 text-sm text-gray-500">Tell us about you and your pet so we can prepare for your visit.</p>

    <div x-show="submitError" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4">
        <p class="text-sm text-red-800" x-text="submitError"></p>
    </div>

    <div class="mt-6 space-y-4">
        {{-- Name --}}
        <div>
            <label for="booking-name" class="block text-sm font-medium text-gray-700">Full name <span class="text-red-500">*</span></label>
            <input
                id="booking-name"
                type="text"
                x-model="form.name"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500 focus:outline-none"
                required
            >
        </div>

        {{-- Email --}}
        <div>
            <label for="booking-email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
            <input
                id="booking-email"
                type="email"
                x-model="form.email"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500 focus:outline-none"
                required
            >
        </div>

        {{-- Phone --}}
        <div>
            <label for="booking-phone" class="block text-sm font-medium text-gray-700">Phone <span class="text-red-500">*</span></label>
            <input
                id="booking-phone"
                type="tel"
                x-model="form.phone"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500 focus:outline-none"
                required
            >
        </div>

        <hr class="border-gray-200">

        {{-- Pet Name --}}
        <div>
            <label for="booking-pet-name" class="block text-sm font-medium text-gray-700">Pet name <span class="text-red-500">*</span></label>
            <input
                id="booking-pet-name"
                type="text"
                x-model="form.pet_name"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500 focus:outline-none"
                placeholder="e.g. Bella"
                required
            >
        </div>

        {{-- Pet Breed (autocomplete) --}}
        <div class="relative" @click.outside="breedDropdownOpen = false">
            <label for="booking-pet-breed" class="block text-sm font-medium text-gray-700">Breed <span class="text-gray-400">(optional)</span></label>
            <input
                id="booking-pet-breed"
                type="text"
                x-model="breedQuery"
                @input="filterBreeds(); form.pet_breed = breedQuery"
                @keydown="handleBreedKeydown($event)"
                @focus="filterBreeds()"
                autocomplete="off"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500 focus:outline-none"
                placeholder="e.g. Cockapoo"
            >
            <ul
                x-show="breedDropdownOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
            >
                <template x-for="(breed, index) in breedSuggestions" :key="breed.name">
                    <li
                        @click="selectBreed(breed)"
                        @mouseenter="breedHighlightIndex = index"
                        :class="{ 'bg-gray-100': breedHighlightIndex === index }"
                        class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-50"
                    >
                        <span x-text="breed.name" class="font-medium text-gray-900"></span>
                        <span x-text="breed.species" class="ml-1 text-gray-400"></span>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Pet Size --}}
        <div>
            <label for="booking-pet-size" class="block text-sm font-medium text-gray-700">Size <span class="text-gray-400">(optional)</span></label>
            <select
                id="booking-pet-size"
                x-model="form.pet_size"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500 focus:outline-none"
            >
                <option value="">Select size</option>
                <option value="small">Small</option>
                <option value="medium">Medium</option>
                <option value="large">Large</option>
            </select>
        </div>

        {{-- Notes --}}
        <div>
            <label for="booking-notes" class="block text-sm font-medium text-gray-700">Notes for the groomer <span class="text-gray-400">(optional)</span></label>
            <textarea
                id="booking-notes"
                x-model="form.notes"
                rows="3"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500 focus:outline-none"
                placeholder="Any special requirements, behavioural notes, or allergies..."
            ></textarea>
        </div>
    </div>

    <div class="mt-8 flex gap-3">
        <button @click="prevStep()" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Back</button>
        <button
            @click="submitBooking()"
            :disabled="!canContinue"
            class="rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <span x-show="!submitting">Confirm Booking</span>
            <span x-show="submitting" class="flex items-center gap-2">
                <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Booking...
            </span>
        </button>
    </div>
</div>
