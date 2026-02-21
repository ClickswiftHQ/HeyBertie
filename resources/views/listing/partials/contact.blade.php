@if ($business->phone || $business->email || $business->website)
    <section>
        <h2 class="text-xl font-semibold text-gray-900">Contact {{ $business->name }}</h2>

        <div class="mt-4 space-y-3">
            @if ($business->phone)
                <a href="tel:{{ $business->phone }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50">
                    {{-- Phone icon --}}
                    <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span class="text-sm text-gray-900">{{ $business->phone }}</span>
                </a>
            @endif

            @if ($business->email)
                <a href="mailto:{{ $business->email }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50">
                    {{-- Mail icon --}}
                    <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <span class="text-sm text-gray-900">{{ $business->email }}</span>
                </a>
            @endif

            @if ($business->website)
                <a href="{{ $business->website }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50">
                    {{-- Globe icon --}}
                    <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                    <span class="flex-1 text-sm text-gray-900">{{ preg_replace('#^https?://#', '', $business->website) }}</span>
                    {{-- ExternalLink icon --}}
                    <svg class="size-3.5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                </a>
            @endif
        </div>
    </section>
@endif
