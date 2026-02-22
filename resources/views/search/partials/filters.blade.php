<form
    method="GET"
    action="{{ $isLandingPage ? url()->current() : route('search') }}"
    x-data
>
    {{-- Preserve core query params --}}
    @if (! $isLandingPage)
        <input type="hidden" name="location" value="{{ $location }}">
    @endif
    <input type="hidden" name="service" value="{{ $service }}">
    @if ($sort !== 'distance')
        <input type="hidden" name="sort" value="{{ $sort }}">
    @endif

    <div class="space-y-6">
        {{-- Location Type --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-900">Location Type</h3>
            <div class="mt-3 space-y-2">
                @foreach (['salon' => 'Salon', 'mobile' => 'Mobile', 'home_based' => 'Home-based'] as $value => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="type"
                            value="{{ $value }}"
                            {{ ($filters['type'] ?? '') === $value ? 'checked' : '' }}
                            @change="$el.form.submit()"
                            class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900"
                        >
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Min Rating --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-900">Minimum Rating</h3>
            <div class="mt-3 space-y-2">
                @foreach ([4 => '4+ stars', 3 => '3+ stars'] as $value => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="rating"
                            value="{{ $value }}"
                            {{ (int) ($filters['rating'] ?? 0) === $value ? 'checked' : '' }}
                            @change="$el.form.submit()"
                            class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900"
                        >
                        {{ $label }}
                    </label>
                @endforeach
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="radio"
                        name="rating"
                        value=""
                        {{ empty($filters['rating']) ? 'checked' : '' }}
                        @change="$el.form.submit()"
                        class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900"
                    >
                    Any
                </label>
            </div>
        </div>

        {{-- Distance --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-900">Distance</h3>
            <div class="mt-3 space-y-2">
                @foreach ([5 => '5 km', 10 => '10 km', 25 => '25 km', 50 => '50 km'] as $value => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="distance"
                            value="{{ $value }}"
                            {{ (int) ($filters['distance'] ?? 25) === $value ? 'checked' : '' }}
                            @change="$el.form.submit()"
                            class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900"
                        >
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Clear All --}}
        <div>
            <a
                href="{{ $isLandingPage ? url()->current() : route('search', ['location' => $location, 'service' => $service]) }}"
                class="text-sm font-medium text-gray-600 underline hover:text-gray-900"
            >
                Clear All
            </a>
        </div>
    </div>
</form>
