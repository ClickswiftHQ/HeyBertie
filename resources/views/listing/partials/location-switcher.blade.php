@if ($locations->count() > 1)
    <div class="border-b border-gray-200">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <nav class="-mb-px flex gap-4 overflow-x-auto" aria-label="Location tabs">
                @foreach ($locations as $loc)
                    @php
                        $isActive = $loc->id === $location->id;
                    @endphp
                    <a
                        href="{{ route('business.location', [$business->handle, $loc->slug]) }}"
                        class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors {{ $isActive ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-900' }}"
                        @if ($isActive) aria-current="page" @endif
                    >
                        {{ $loc->name }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
@endif
