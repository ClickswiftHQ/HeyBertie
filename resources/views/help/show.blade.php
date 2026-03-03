@php
    $resolvedTitle = (string) $title;
    $resolvedSeoTitle = $seo_title?->value() ?: $resolvedTitle;
    $resolvedExcerpt = (string) ($excerpt ?? '');
    $resolvedSeoDesc = $seo_description?->value() ?: $resolvedExcerpt;
    $resolvedContent = (string) ($content ?? '');
    $wordCount = str_word_count(strip_tags($resolvedContent));
    $readingTime = max(1, (int) ceil($wordCount / 200));

    $audienceTerms = collect($audience ?? []);
    $audienceTerm = $audienceTerms->first();
    $categoryTerms = collect($categories ?? []);
    $categoryTerm = $categoryTerms->first();

    // Sidebar navigation: all help articles grouped by audience + category
    $allArticles = \Statamic\Facades\Entry::query()
        ->where('collection', 'help')
        ->whereStatus('published')
        ->orderBy('order', 'asc')
        ->get();

    $sidebarGroups = [];
    foreach ($allArticles as $entry) {
        $audSlugs = $entry->get('audience') ?? [];
        $audSlug = is_array($audSlugs) ? ($audSlugs[0] ?? 'uncategorised') : $audSlugs;
        $catSlugs = $entry->get('categories') ?? [];
        $catSlug = is_array($catSlugs) ? ($catSlugs[0] ?? 'general') : $catSlugs;
        $sidebarGroups[$audSlug][$catSlug][] = $entry;
    }
    $sidebarOrder = ['for-groomers', 'for-pet-owners'];

    // Related articles: same category, exclude current
    $currentSlug = $page?->slug() ?? '';
    $currentCatSlug = $categoryTerm ? $categoryTerm->slug() : null;
    $relatedArticles = collect();
    if ($currentCatSlug) {
        $relatedArticles = $allArticles
            ->filter(function ($entry) use ($currentSlug, $currentCatSlug) {
                if ($entry->slug() === $currentSlug) {
                    return false;
                }
                $catSlugs = $entry->get('categories') ?? [];
                $catSlug = is_array($catSlugs) ? ($catSlugs[0] ?? '') : $catSlugs;
                return $catSlug === $currentCatSlug;
            })
            ->take(3);
    }
@endphp

@extends('layouts.marketing')

@section('title', $resolvedSeoTitle . ' — heyBertie Help Centre')
@section('meta_description', $resolvedSeoDesc)

@push('head')
    <meta property="og:title" content="{{ $resolvedSeoTitle }}">
    <meta property="og:description" content="{{ $resolvedSeoDesc }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($og_image?->value())
        @php
            $ogAsset = \Statamic\Facades\Asset::find($og_image->value());
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
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- Breadcrumbs --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <a href="/help" class="hover:text-gray-900">Help Centre</a>
                @if ($audienceTerm)
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    <span>{{ $audienceTerm->title() }}</span>
                @endif
                @if ($categoryTerm)
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    <span>{{ $categoryTerm->title() }}</span>
                @endif
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
                <span class="text-gray-900">{{ $resolvedTitle }}</span>
            </nav>

            <div class="mt-8 lg:grid lg:grid-cols-[280px_1fr] lg:gap-12">
                {{-- Sidebar Navigation --}}
                <aside class="hidden lg:block">
                    <div class="sticky top-24">
                        <a href="/help" class="text-sm font-semibold text-gray-900 hover:text-gray-600">Help Centre</a>

                        <nav class="mt-4 space-y-6">
                            @foreach ($sidebarOrder as $audSlug)
                                @if (isset($sidebarGroups[$audSlug]))
                                    @php
                                        $audTerm = \Statamic\Facades\Term::find('audience::' . $audSlug);
                                        $audTitle = $audTerm ? $audTerm->title() : ucfirst($audSlug);
                                    @endphp

                                    <div>
                                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $audTitle }}</h3>

                                        @foreach ($sidebarGroups[$audSlug] as $catSlug => $articles)
                                            @php
                                                $catTerm = \Statamic\Facades\Term::find('categories::' . $catSlug);
                                                $catTitle = $catTerm ? $catTerm->title() : ucfirst($catSlug);
                                            @endphp

                                            <div class="mt-3">
                                                <h4 class="text-sm font-medium text-gray-700">{{ $catTitle }}</h4>
                                                <ul class="mt-1 space-y-1">
                                                    @foreach ($articles as $entry)
                                                        <li>
                                                            <a
                                                                href="{{ $entry->url() }}"
                                                                class="block rounded px-2 py-1 text-sm {{ $entry->slug() === $currentSlug ? 'bg-gray-100 font-medium text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                                                            >
                                                                {{ $entry->get('title') }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </nav>
                    </div>
                </aside>

                {{-- Main Content --}}
                <div>
                    {{-- Header --}}
                    <header>
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            @if ($audienceTerm)
                                <span class="inline-flex items-center rounded-full bg-gray-900 px-2.5 py-0.5 text-xs font-medium text-white">
                                    {{ $audienceTerm->title() }}
                                </span>
                            @endif
                            @if ($categoryTerm)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                    {{ $categoryTerm->title() }}
                                </span>
                            @endif
                        </div>

                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $title }}</h1>
                        <p class="mt-3 text-sm text-gray-500">{{ $readingTime }} min read</p>
                    </header>

                    {{-- Content --}}
                    <div class="prose prose-gray mt-10 max-w-none prose-headings:font-semibold prose-a:text-gray-900 prose-a:underline hover:prose-a:text-gray-600">
                        {!! $content !!}
                    </div>

                    {{-- Was this helpful? --}}
                    <div class="mt-12 border-t border-gray-200 pt-8" x-data="{ feedback: null }">
                        <div x-show="feedback === null">
                            <p class="text-sm font-medium text-gray-900">Was this article helpful?</p>
                            <div class="mt-3 flex gap-3">
                                <button
                                    @click="feedback = 'yes'"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    <svg class="size-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V3a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                                    </svg>
                                    Yes
                                </button>
                                <button
                                    @click="feedback = 'no'"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    <svg class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715A12.137 12.137 0 0 1 2.25 12c0-2.848.992-5.464 2.649-7.521C5.287 3.997 5.886 3.75 6.504 3.75h4.016c.483 0 .964.078 1.423.23l3.114 1.04a4.501 4.501 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.75 2.25 2.25 0 0 0 9.75 22a.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384m-10.253 1.5H9.7m8.075-9.75c.01.05.027.1.05.148.593 1.2.925 2.55.925 3.977 0 1.487-.36 2.89-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398-.306.774-1.086 1.227-1.918 1.227h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 0 0 .303-.54" />
                                    </svg>
                                    No
                                </button>
                            </div>
                        </div>

                        <div x-show="feedback !== null" x-cloak>
                            <p class="text-sm text-gray-600">
                                <span x-show="feedback === 'yes'">Thanks for letting us know! Glad it helped.</span>
                                <span x-show="feedback === 'no'">Thanks for your feedback. We'll work on improving this article.</span>
                            </p>
                        </div>
                    </div>

                    {{-- Tags --}}
                    @php
                        $tagTerms = collect($tags ?? []);
                    @endphp
                    @if ($tagTerms->isNotEmpty())
                        <div class="mt-8 border-t border-gray-200 pt-6">
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

                    {{-- Related Articles --}}
                    @if ($relatedArticles->isNotEmpty())
                        <div class="mt-12 border-t border-gray-200 pt-8">
                            <h2 class="text-lg font-semibold text-gray-900">Related Articles</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($relatedArticles as $related)
                                    <a href="{{ $related->url() }}" class="group block rounded-lg border border-gray-200 p-4 transition-colors hover:border-gray-300 hover:bg-gray-50">
                                        <h3 class="font-medium text-gray-900 group-hover:text-gray-600">{{ $related->get('title') }}</h3>
                                        @if ($related->get('excerpt'))
                                            <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ $related->get('excerpt') }}</p>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </article>
@endsection
