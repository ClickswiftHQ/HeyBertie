<section class="bg-gray-50 px-4 py-12 sm:px-6 md:py-20 lg:px-8">
    <div class="mx-auto max-w-4xl text-center">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl md:text-6xl">
            {{ $headline }}
        </h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600">
            {{ $subheadline }}
        </p>
        <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <a href="{{ $primaryCtaUrl }}" class="w-full rounded-lg bg-gray-900 px-8 py-4 font-medium text-white hover:bg-gray-800 sm:w-auto">
                {{ $primaryCtaText }}
            </a>
            @if (isset($secondaryCtaText, $secondaryCtaUrl))
                <a href="{{ $secondaryCtaUrl }}" class="w-full rounded-lg border-2 border-gray-900 px-8 py-4 font-medium text-gray-900 hover:bg-gray-50 sm:w-auto">
                    {{ $secondaryCtaText }}
                </a>
            @endif
        </div>
    </div>
</section>
