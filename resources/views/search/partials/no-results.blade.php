<div class="mx-auto max-w-lg py-16 text-center">
    <svg class="mx-auto size-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
    </svg>

    <h2 class="mt-6 text-xl font-semibold text-gray-900">
        No {{ str($serviceName)->lower() }}s found near {{ $location }}
    </h2>

    <p class="mt-3 text-gray-600">Try:</p>
    <ul class="mt-2 space-y-1 text-sm text-gray-600">
        <li>Increasing your search distance</li>
        <li>Searching a nearby city</li>
        <li>Removing filters</li>
    </ul>

    <div class="mt-8">
        <p class="text-sm font-medium text-gray-700">Popular areas:</p>
        <div class="mt-3 flex flex-wrap justify-center gap-2">
            @foreach (['london', 'manchester', 'birmingham', 'leeds', 'bristol'] as $city)
                <a
                    href="{{ route('search.landing', 'dog-grooming-in-'.$city) }}"
                    class="rounded-lg border-2 border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-900 hover:text-gray-900"
                >
                    {{ ucfirst($city) }}
                </a>
            @endforeach
        </div>
    </div>
</div>
