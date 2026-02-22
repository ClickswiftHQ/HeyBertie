@extends('layouts.marketing')

@section('title', $metaTitle)

@if ($metaDescription)
    @section('meta_description', $metaDescription)
@endif

@push('head')
    @if ($isLandingPage && $canonicalUrl)
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @endif

    @if (! $isLandingPage)
        <meta name="robots" content="noindex, follow">
    @endif

    @if ($schemaMarkup)
        <script type="application/ld+json">{!! $schemaMarkup !!}</script>
    @endif
@endpush

@section('content')
    {{-- Search Bar --}}
    @include('search.partials.search-bar')

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($geocodingFailed ?? false)
            @include('search.partials.location-error')
        @elseif ($results && $results->total() > 0)
            {{-- Results Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold text-gray-900">
                    @if ($isLandingPage)
                        {{ $serviceName }} in {{ $location }}
                    @else
                        {{ $totalResults }} {{ str($serviceName)->lower() }}{{ $totalResults !== 1 ? 's' : '' }} near {{ $location }}
                    @endif
                </h1>

                {{-- Sort Dropdown --}}
                <form method="GET" action="{{ $isLandingPage ? url()->current() : route('search') }}">
                    <input type="hidden" name="location" value="{{ $location }}">
                    <input type="hidden" name="service" value="{{ $service }}">
                    @if (! empty($filters['type']))
                        <input type="hidden" name="type" value="{{ $filters['type'] }}">
                    @endif
                    @if (! empty($filters['rating']))
                        <input type="hidden" name="rating" value="{{ $filters['rating'] }}">
                    @endif
                    @if (! empty($filters['distance']))
                        <input type="hidden" name="distance" value="{{ $filters['distance'] }}">
                    @endif
                    <select
                        name="sort"
                        onchange="this.form.submit()"
                        class="rounded-lg border-2 border-gray-300 px-4 py-2 text-sm text-gray-900 focus:border-gray-900 focus:outline-none"
                    >
                        <option value="distance" {{ $sort === 'distance' ? 'selected' : '' }}>Distance</option>
                        <option value="rating" {{ $sort === 'rating' ? 'selected' : '' }}>Rating</option>
                        <option value="price_low" {{ $sort === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_high" {{ $sort === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                    </select>
                </form>
            </div>

            {{-- Main Content: Filters + Results --}}
            <div class="flex flex-col gap-8 lg:flex-row">
                {{-- Mobile Filter Toggle --}}
                <div class="lg:hidden" x-data="{ filtersOpen: false }">
                    <button
                        @click="filtersOpen = !filtersOpen"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:border-gray-900"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                        </svg>
                        Filters
                    </button>

                    <div x-show="filtersOpen" x-cloak x-transition class="mt-4">
                        @include('search.partials.filters')
                    </div>
                </div>

                {{-- Desktop Filter Sidebar --}}
                <aside class="hidden w-64 shrink-0 lg:block">
                    @include('search.partials.filters')
                </aside>

                {{-- Results Grid --}}
                <div class="flex-1">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($results as $locationResult)
                            @include('search.partials.result-card', ['locationResult' => $locationResult])
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($results->hasPages())
                        <div class="mt-8">
                            {{ $results->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            @include('search.partials.no-results')
        @endif
    </div>
@endsection
