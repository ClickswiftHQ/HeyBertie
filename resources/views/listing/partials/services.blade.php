<section>
    <h2 class="text-xl font-semibold text-gray-900">Services</h2>

    @forelse ($services as $service)
        @php
            $duration = $service->duration_minutes;
            if ($duration < 60) {
                $durationText = $duration . ' min';
            } else {
                $hrs = intdiv($duration, 60);
                $mins = $duration % 60;
                $durationText = $mins > 0 ? "{$hrs} hr {$mins} min" : "{$hrs} hr";
            }

            if ($service->price_type === 'call' || $service->price === null) {
                $priceText = 'Price on request';
            } else {
                $formatted = '£' . number_format((float) $service->price, 2);
                $priceText = $service->price_type === 'from' ? "From {$formatted}" : $formatted;
            }
        @endphp

        <div class="mt-3 rounded-lg border border-gray-200 bg-white">
            <div class="flex items-center justify-between gap-4 p-4">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="font-medium text-gray-900">{{ $service->name }}</h3>
                        @if ($service->is_featured)
                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">Featured</span>
                        @endif
                    </div>
                    @if ($service->description)
                        <p class="mt-1 text-sm text-gray-500">{{ $service->description }}</p>
                    @endif
                    <div class="mt-2 flex items-center gap-3 text-sm text-gray-500">
                        <span>{{ $durationText }}</span>
                        <span class="font-medium text-gray-900">{{ $priceText }}</span>
                    </div>
                </div>
                <div class="shrink-0">
                    @if ($canBook)
                        <a href="#" class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Book</a>
                    @else
                        <span class="inline-flex items-center px-3 py-1.5 text-sm text-gray-400">Contact to book</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <p class="mt-3 text-gray-500">No services listed yet.</p>
    @endforelse
</section>
