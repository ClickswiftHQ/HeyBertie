<div class="space-y-1.5">
    @foreach ([5, 4, 3, 2, 1] as $stars)
        @php
            $count = $rating['breakdown'][$stars] ?? 0;
            $pct = $rating['count'] > 0 ? ($count / $rating['count']) * 100 : 0;
        @endphp
        <div class="flex items-center gap-2 text-sm">
            <div class="flex w-8 items-center gap-0.5">
                <span class="text-gray-900">{{ $stars }}</span>
                <svg class="size-3 fill-amber-400 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-amber-400 transition-all duration-500" style="width: {{ $pct }}%"></div>
            </div>
            <span class="w-8 text-right text-gray-500">{{ $count }}</span>
        </div>
    @endforeach
</div>
