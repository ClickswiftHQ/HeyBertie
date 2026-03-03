@php
    $resolvedTitle = (string) $title;
    $resolvedSeoTitle = $seo_title?->value() ?: $resolvedTitle;
    $resolvedExcerpt = (string) ($excerpt ?? '');
    $resolvedSeoDesc = $seo_description?->value() ?: $resolvedExcerpt;
@endphp

@extends('layouts.marketing')

@section('title', $resolvedSeoTitle . ' — heyBertie Blog')
@section('meta_description', $resolvedSeoDesc)

@push('head')
    <meta property="og:title" content="{{ $resolvedSeoTitle }}">
    <meta property="og:description" content="{{ $resolvedSeoDesc }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($og_image?->value() ?? $featured_image?->value())
        @php
            $ogAssetPath = $og_image?->value() ?? $featured_image?->value();
            $ogAsset = $ogAssetPath ? \Statamic\Facades\Asset::find($ogAssetPath) : null;
        @endphp
        @if ($ogAsset)
            <meta property="og:image" content="{{ url($ogAsset->url()) }}">
        @endif
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $resolvedSeoTitle }}">
    <meta name="twitter:description" content="{{ $resolvedSeoDesc }}">
@endpush

@section('content')
    <article class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            {{-- Back link --}}
            <a href="/blog" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Blog
            </a>

            {{-- Header --}}
            <header class="mt-8">
                @php
                    $categoryTerms = collect($categories ?? []);
                @endphp
                @if ($categoryTerms->isNotEmpty())
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach ($categoryTerms as $term)
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                                {{ $term->title() }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $title }}</h1>

                <div class="mt-4 flex items-center gap-3 text-sm text-gray-500">
                    @if ($date?->value())
                        <time datetime="{{ $date->format('Y-m-d') }}">
                            {{ $date->format('M j, Y') }}
                        </time>
                    @endif
                    @if ($author?->value())
                        <span>&middot;</span>
                        <span>{{ $author }}</span>
                    @endif
                </div>
            </header>

            {{-- Featured Image --}}
            @if ($featured_image?->value())
                @php
                    $image = \Statamic\Facades\Asset::find($featured_image->value());
                @endphp
                @if ($image)
                    <div class="mt-8 overflow-hidden rounded-lg">
                        <img
                            src="{{ $image->url() }}"
                            alt="{{ $title }}"
                            class="w-full object-cover"
                        >
                    </div>
                @endif
            @endif

            {{-- Content --}}
            <div class="prose prose-gray mt-10 max-w-none prose-headings:font-semibold prose-a:text-gray-900 prose-a:underline hover:prose-a:text-gray-600">
                {!! $content !!}
            </div>

            {{-- Tags --}}
            @php
                $tagTerms = collect($tags ?? []);
            @endphp
            @if ($tagTerms->isNotEmpty())
                <div class="mt-10 border-t border-gray-200 pt-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">Tags:</span>
                        @foreach ($tagTerms as $term)
                            <span class="inline-flex items-center rounded-full bg-gray-50 px-3 py-1 text-sm text-gray-600 ring-1 ring-gray-200 ring-inset">
                                {{ $term->title() }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </article>
@endsection
