<section class="px-4 py-12 sm:px-6 md:py-20 lg:px-8">
    <div class="mx-auto max-w-3xl rounded-lg border-2 border-gray-900 bg-white p-8 text-center md:p-12">
        <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">{{ $headline }}</h2>
        <p class="mx-auto mt-4 max-w-xl text-gray-600">{{ $description }}</p>
        <div class="mt-8">
            <a href="{{ $ctaUrl }}" class="inline-block rounded-lg bg-gray-900 px-8 py-4 font-medium text-white hover:bg-gray-800">
                {{ $ctaText }}
            </a>
        </div>
    </div>
</section>
