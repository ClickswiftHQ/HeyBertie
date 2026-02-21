@extends('layouts.marketing')

@section('title', $business->name . ' — Dog Grooming')

@php
    $metaDescription = $business->description
        ? Str::limit($business->description, 120, '')
        : $business->name . ' — professional dog grooming across ' . $locations->count() . ' locations.';
@endphp

@section('meta_description', $metaDescription)

@push('head')
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:title" content="@yield('title')">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="business.business">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($business->logo_url)
        <meta property="og:image" content="{{ $business->logo_url }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title')">
    <meta name="twitter:description" content="{{ $metaDescription }}">

    <script type="application/ld+json">{!! $schemaMarkup !!}</script>
@endpush

@section('content')
    {{-- Header --}}
    <div class="relative">
        <div class="h-48 w-full bg-gradient-to-r from-gray-200 to-gray-100 sm:h-64">
            @if ($business->cover_image_url)
                <img src="{{ $business->cover_image_url }}" alt="{{ $business->name }} cover" class="size-full object-cover">
            @endif
        </div>

        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="-mt-12 flex flex-col gap-4 sm:-mt-16 sm:flex-row sm:items-end sm:gap-6">
                <div class="size-24 shrink-0 overflow-hidden rounded-xl border-4 border-white bg-gray-100 sm:size-32">
                    @if ($business->logo_url)
                        <img src="{{ $business->logo_url }}" alt="{{ $business->name }}" class="size-full object-cover">
                    @else
                        <div class="flex size-full items-center justify-center text-3xl font-bold text-gray-500 sm:text-4xl">
                            {{ strtoupper(substr($business->name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <div class="flex-1 pb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $business->name }}</h1>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                            <span>{{ $business->handle }}</span>
                            @if ($business->verification_status === 'verified')
                                <span class="inline-flex items-center gap-1 text-green-600">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.77 4 4 0 0 1 0 6.76 4 4 0 0 1-4.78 4.77 4 4 0 0 1-6.74 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
                                    Verified
                                </span>
                            @endif
                            @if ($rating['count'] > 0)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="size-4 fill-amber-400 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    {{ number_format($rating['average'], 1) }} ({{ $rating['count'] }})
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                                {{ $locations->count() }} locations
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        {{-- About --}}
        @if ($business->description)
            @php
                $description = $business->description;
                $isTruncated = Str::length($description) > 300;
            @endphp
            <section>
                <h2 class="text-xl font-semibold text-gray-900">About {{ $business->name }}</h2>
                <div class="mt-3" x-data="{ expanded: false }">
                    <p class="whitespace-pre-line text-gray-600">
                        <template x-if="expanded">
                            <span>{{ $description }}</span>
                        </template>
                        <template x-if="!expanded">
                            <span>{{ Str::limit($description, 300) }}</span>
                        </template>
                    </p>
                    @if ($isTruncated)
                        <button
                            @click="expanded = !expanded"
                            class="mt-1 text-sm font-medium text-gray-900 hover:underline"
                            x-text="expanded ? 'Show less' : 'Read more'"
                        ></button>
                    @endif
                </div>
            </section>

            <hr class="my-8 border-gray-200">
        @endif

        {{-- Locations --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900">Our Locations</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($locations as $loc)
                    @include('listing.partials.location-card', ['loc' => $loc])
                @endforeach
            </div>
        </section>

        <hr class="my-8 border-gray-200">

        {{-- Contact --}}
        @include('listing.partials.contact')
    </div>
@endsection
