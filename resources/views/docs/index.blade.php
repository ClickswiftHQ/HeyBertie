@extends('layouts.marketing')

@section('title', 'Docs — heyBertie')

@section('content')
    {{-- Hero --}}
    <section class="bg-gray-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Docs</h1>
            <p class="mt-4 max-w-2xl text-lg text-gray-600">Internal documentation, SOPs, and admin resources.</p>
        </div>

        {{-- Articles grouped by category --}}
        <div class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8">
            @php
                $allDocs = \Statamic\Facades\Entry::query()
                    ->where('collection', 'docs')
                    ->whereStatus('published')
                    ->orderBy('order', 'asc')
                    ->get();

                $categoryGroups = [];
                $uncategorised = [];

                foreach ($allDocs as $entry) {
                    $categorySlugs = $entry->get('categories') ?? [];
                    $categorySlug = is_array($categorySlugs) ? ($categorySlugs[0] ?? null) : $categorySlugs;

                    if ($categorySlug) {
                        $categoryGroups[$categorySlug][] = $entry;
                    } else {
                        $uncategorised[] = $entry;
                    }
                }
            @endphp

            @if ($allDocs->isEmpty())
                <div class="py-12 text-center">
                    <p class="text-lg text-gray-500">No docs yet. Create articles from the control panel.</p>
                </div>
            @else
                @foreach ($categoryGroups as $catSlug => $articles)
                    @php
                        $categoryTerm = \Statamic\Facades\Term::find('categories::' . $catSlug);
                        $categoryTitle = $categoryTerm ? $categoryTerm->title() : ucfirst($catSlug);
                    @endphp

                    <div class="mb-10">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $categoryTitle }}</h2>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($articles as $entry)
                                <a href="{{ $entry->url() }}" class="group block rounded-lg border border-gray-200 bg-white p-6 transition-colors hover:border-gray-300 hover:bg-gray-50">
                                    <h3 class="font-semibold text-gray-900 group-hover:text-gray-600">
                                        {{ $entry->get('title') }}
                                    </h3>

                                    @if ($entry->get('excerpt'))
                                        <p class="mt-2 line-clamp-2 text-sm text-gray-600">{{ $entry->get('excerpt') }}</p>
                                    @endif

                                    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-900">
                                        Read
                                        <svg class="size-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if (! empty($uncategorised))
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold text-gray-900">General</h2>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($uncategorised as $entry)
                                <a href="{{ $entry->url() }}" class="group block rounded-lg border border-gray-200 bg-white p-6 transition-colors hover:border-gray-300 hover:bg-gray-50">
                                    <h3 class="font-semibold text-gray-900 group-hover:text-gray-600">
                                        {{ $entry->get('title') }}
                                    </h3>

                                    @if ($entry->get('excerpt'))
                                        <p class="mt-2 line-clamp-2 text-sm text-gray-600">{{ $entry->get('excerpt') }}</p>
                                    @endif

                                    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-900">
                                        Read
                                        <svg class="size-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>
@endsection
