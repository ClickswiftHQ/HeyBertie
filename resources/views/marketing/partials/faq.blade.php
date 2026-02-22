<section class="px-4 py-12 sm:px-6 md:py-20 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">{{ $title ?? 'Frequently asked questions' }}</h2>
        <div class="mt-12 divide-y divide-gray-200 border-t border-gray-200" x-data="{ open: null }">
            @foreach ($faqs as $index => $faq)
                <div>
                    <button
                        class="flex w-full items-center justify-between py-5 text-left"
                        @click="open = open === {{ $index }} ? null : {{ $index }}"
                    >
                        <span class="text-base font-medium text-gray-900">{{ $faq['question'] }}</span>
                        <svg
                            class="ml-4 size-5 shrink-0 text-gray-500 transition-transform duration-200"
                            :class="open === {{ $index }} ? 'rotate-180' : ''"
                            fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div
                        x-show="open === {{ $index }}"
                        x-cloak
                        x-collapse
                        class="pb-5"
                    >
                        <p class="text-gray-600">{{ $faq['answer'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
