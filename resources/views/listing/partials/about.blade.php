@php
    $description = $business->description ?? '';
    $isTruncated = Str::length($description) > 300;
    $locationLabel = match ($location->location_type) {
        'mobile' => 'Mobile grooming',
        'home' => 'Home-based grooming',
        default => 'Salon-based grooming',
    };
    $openDays = null;
    if ($location->opening_hours) {
        $openDays = collect($location->opening_hours)
            ->filter()
            ->keys()
            ->map(fn ($day) => ucfirst(substr($day, 0, 3)))
            ->join(', ');
    }
@endphp

<section>
    <h2 class="text-xl font-semibold text-gray-900">About {{ $business->name }}</h2>

    @if ($description)
        <div class="mt-3" x-data="{ expanded: false }">
            <p class="whitespace-pre-line text-gray-600">
                <template x-if="expanded">
                    <span>{{ $description }}</span>
                </template>
                <template x-if="!expanded">
                    <span>{{ Str::limit($description, 300) }}</span>
                </template>
            </p>
            @if ($isTruncated)
                <button
                    @click="expanded = !expanded"
                    class="mt-1 text-sm font-medium text-gray-900 hover:underline"
                    x-text="expanded ? 'Show less' : 'Read more'"
                ></button>
            @endif
        </div>
    @endif

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
            {{-- Scissors icon --}}
            <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/></svg>
            <span class="text-sm text-gray-900">{{ $locationLabel }}</span>
        </div>
        <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
            {{-- MapPin icon --}}
            <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="text-sm text-gray-900">{{ $location->city }}</span>
        </div>
        @if ($openDays)
            <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                {{-- CalendarDays icon --}}
                <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                <span class="text-sm text-gray-900">Open {{ $openDays }}</span>
            </div>
        @endif
    </div>
</section>
