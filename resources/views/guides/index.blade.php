@extends('layouts.marketing')

@section('title', 'Guides — heyBertie')
@section('meta_description', 'Step-by-step guides to help you start and grow your pet grooming business with heyBertie.')

@section('content')
    {{-- Hero --}}
    <section class="bg-gray-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Guides</h1>
            <p class="mt-4 max-w-2xl text-lg text-gray-600">Step-by-step resources to help you start, run, and grow your pet grooming business.</p>
        </div>
    </section>

    {{-- Guides --}}
    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @php
                $allGuides = \Statamic\Facades\Entry::query()
                    ->where('collection', 'guides')
                    ->whereStatus('published')
                    ->orderBy('order', 'asc')
                    ->get();

                $featured = $allGuides->filter(fn ($entry) => $entry->get('featured'));
                $regular = $allGuides->reject(fn ($entry) => $entry->get('featured'));
            @endphp

            @if ($allGuides->isEmpty())
                <div class="py-12 text-center">
                    <p class="text-lg text-gray-500">No guides yet. Check back soon!</p>
                </div>
            @else
                {{-- Featured Guides --}}
                @if ($featured->isNotEmpty())
                    <div class="mb-12">
                        <h2 class="text-lg font-semibold text-gray-900">Featured</h2>
                        <div class="mt-6 grid gap-8 sm:grid-cols-2">
                            @foreach ($featured as $entry)
                                <article class="group rounded-lg border-2 border-gray-900 p-6 transition-colors hover:bg-gray-50">
                                    <a href="{{ $entry->url() }}" class="block">
                                        @if ($entry->get('categories'))
                                            <div class="mb-3 flex flex-wrap gap-2">
                                                @foreach ($entry->get('categories') as $categorySlug)
                                                    @php
                                                        $category = \Statamic\Facades\Term::find('categories::' . $categorySlug);
                                                    @endphp
                                                    @if ($category)
                                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                                            {{ $category->title() }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-gray-600">
                                            {{ $entry->get('title') }}
                                        </h3>

                                        @if ($entry->get('excerpt'))
                                            <p class="mt-2 text-sm text-gray-600">{{ $entry->get('excerpt') }}</p>
                                        @endif

                                        <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-900">
                                            Read guide
                                            <svg class="size-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                            </svg>
                                        </span>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Regular Guides --}}
                @if ($regular->isNotEmpty())
                    @if ($featured->isNotEmpty())
                        <h2 class="text-lg font-semibold text-gray-900">All Guides</h2>
                    @endif
                    <div class="mt-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($regular as $entry)
                            <article class="group">
                                <a href="{{ $entry->url() }}" class="block rounded-lg border border-gray-200 p-6 transition-colors hover:border-gray-300 hover:bg-gray-50">
                                    @if ($entry->get('categories'))
                                        <div class="mb-3 flex flex-wrap gap-2">
                                            @foreach ($entry->get('categories') as $categorySlug)
                                                @php
                                                    $category = \Statamic\Facades\Term::find('categories::' . $categorySlug);
                                                @endphp
                                                @if ($category)
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                                        {{ $category->title() }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    <h3 class="font-semibold text-gray-900 group-hover:text-gray-600">
                                        {{ $entry->get('title') }}
                                    </h3>

                                    @if ($entry->get('excerpt'))
                                        <p class="mt-2 line-clamp-2 text-sm text-gray-600">{{ $entry->get('excerpt') }}</p>
                                    @endif

                                    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-900">
                                        Read guide
                                        <svg class="size-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </section>
@endsection
