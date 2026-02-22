<section class="bg-gray-50 px-4 py-12 sm:px-6 md:py-20 lg:px-8">
    <div class="mx-auto max-w-5xl">
        <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">{{ $title ?? 'What professionals say' }}</h2>
        <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-{{ count($testimonials) }}">
            @foreach ($testimonials as $testimonial)
                <div class="rounded-lg border-2 border-gray-200 bg-white p-8">
                    <p class="text-gray-600">&ldquo;{{ $testimonial['quote'] }}&rdquo;</p>
                    <div class="mt-6">
                        <p class="font-semibold text-gray-900">{{ $testimonial['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $testimonial['role'] }}, {{ $testimonial['business'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
