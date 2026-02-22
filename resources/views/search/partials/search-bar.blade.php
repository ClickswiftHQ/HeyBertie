<section class="border-b-2 border-gray-200 bg-gray-50 px-4 py-6 sm:px-6 lg:px-8">
    <form
        action="{{ route('search') }}"
        method="GET"
        class="mx-auto max-w-4xl"
    >
        <div class="flex flex-col gap-3 md:flex-row">
            <select
                name="service"
                class="w-full rounded-lg border-2 border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-gray-900 focus:outline-none md:w-auto"
            >
                <option value="dog-grooming" {{ ($service ?? 'dog-grooming') === 'dog-grooming' ? 'selected' : '' }}>Dog Grooming</option>
                <option value="dog-walking" {{ ($service ?? '') === 'dog-walking' ? 'selected' : '' }}>Dog Walking</option>
                <option value="cat-sitting" {{ ($service ?? '') === 'cat-sitting' ? 'selected' : '' }}>Cat Sitting</option>
            </select>

            @include('search.partials.location-autocomplete', ['value' => $location ?? '', 'placeholder' => 'e.g. London, SW1A'])

            <button
                type="submit"
                class="w-full rounded-lg bg-gray-900 px-8 py-3 text-sm font-medium text-white hover:bg-gray-800 md:w-auto"
            >
                Search
            </button>
        </div>
    </form>
</section>
