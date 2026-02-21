@if ($location->opening_hours)
    @php
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $todayIndex = now()->dayOfWeekIso - 1;
        $todayKey = $days[$todayIndex];
        $todayHours = $location->opening_hours[$todayKey] ?? null;
        $isOpen = false;

        if ($todayHours) {
            $currentTime = now()->format('H:i');
            $isOpen = $currentTime >= $todayHours['open'] && $currentTime <= $todayHours['close'];
        }
    @endphp

    <section>
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Opening Hours</h2>
            @if ($isOpen)
                <span class="rounded-full bg-gray-900 px-2.5 py-0.5 text-xs font-medium text-white">Open now</span>
            @else
                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">Closed</span>
            @endif
        </div>

        <div class="mt-4 space-y-2">
            @foreach ($days as $index => $day)
                @php
                    $hours = $location->opening_hours[$day] ?? null;
                    $isToday = $index === $todayIndex;
                @endphp
                <div class="flex items-center justify-between rounded-md px-3 py-2 text-sm {{ $isToday ? 'bg-gray-100 font-medium' : '' }}">
                    <span class="capitalize text-gray-900">{{ $day }}</span>
                    @if ($hours)
                        <span class="text-gray-900">
                            {{ Carbon\Carbon::createFromFormat('H:i', $hours['open'])->format('g:i A') }}
                            –
                            {{ Carbon\Carbon::createFromFormat('H:i', $hours['close'])->format('g:i A') }}
                        </span>
                    @else
                        <span class="text-gray-500">Closed</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
