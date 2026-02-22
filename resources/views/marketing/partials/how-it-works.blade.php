<section class="px-4 py-12 sm:px-6 md:py-20 lg:px-8">
    <div class="mx-auto max-w-5xl">
        <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">{{ $title }}</h2>
        <div class="mt-12 grid grid-cols-1 gap-10 md:grid-cols-{{ count($steps) }}">
            @foreach ($steps as $index => $step)
                <div class="text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full border-2 border-gray-900 text-2xl font-bold text-gray-900">
                        {{ $index + 1 }}
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-900">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-gray-600">{{ $step['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
