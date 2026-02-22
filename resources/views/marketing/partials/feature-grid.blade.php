@php
    $columns = $columns ?? 3;
    $gridClass = match ($columns) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<section class="{{ $sectionClass ?? 'px-4 py-12 sm:px-6 md:py-20 lg:px-8' }}">
    <div class="mx-auto max-w-5xl">
        <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">{{ $title }}</h2>
        @if (isset($subtitle))
            <p class="mx-auto mt-4 max-w-2xl text-center text-gray-600">{{ $subtitle }}</p>
        @endif
        <div class="mt-12 grid grid-cols-1 gap-8 {{ $gridClass }}">
            @foreach ($features as $feature)
                <div class="text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-gray-200 text-xl">
                        {!! $feature['icon'] !!}
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ $feature['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
