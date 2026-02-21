<div
    x-data="{ open: false, copied: false }"
    x-on:share-open.window="open = true"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-black/50"
    ></div>

    {{-- Dialog --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-lg"
    >
        <h3 class="text-lg font-semibold text-gray-900">Share {{ $business->name }}</h3>

        <div class="mt-4 space-y-3">
            <div class="flex gap-3">
                <a
                    href="https://wa.me/?text={{ urlencode($business->name . ' on heyBertie ' . $canonicalUrl) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex-1 rounded-lg border border-gray-200 p-3 text-center text-sm text-gray-700 transition-colors hover:bg-gray-50"
                >
                    WhatsApp
                </a>
                <a
                    href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonicalUrl) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex-1 rounded-lg border border-gray-200 p-3 text-center text-sm text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Facebook
                </a>
                <a
                    href="https://twitter.com/intent/tweet?text={{ urlencode($business->name . ' on heyBertie') }}&url={{ urlencode($canonicalUrl) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex-1 rounded-lg border border-gray-200 p-3 text-center text-sm text-gray-700 transition-colors hover:bg-gray-50"
                >
                    X
                </a>
            </div>
            <button
                @click="navigator.clipboard.writeText('{{ $canonicalUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 p-3 text-sm text-gray-700 transition-colors hover:bg-gray-50"
            >
                <template x-if="copied">
                    <span class="inline-flex items-center gap-2">
                        {{-- Check icon --}}
                        <svg class="size-4 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Copied!
                    </span>
                </template>
                <template x-if="!copied">
                    <span class="inline-flex items-center gap-2">
                        {{-- Copy icon --}}
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        Copy link
                    </span>
                </template>
            </button>
        </div>

        <button @click="open = false" class="absolute right-4 top-4 text-gray-400 hover:text-gray-600">
            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            <span class="sr-only">Close</span>
        </button>
    </div>
</div>
