@php
    $resolvedTitle = (string) $title;
    $resolvedContent = (string) ($content ?? '');
    $wordCount = str_word_count(strip_tags($resolvedContent));
    $readingTime = max(1, (int) ceil($wordCount / 200));

    $categoryTerms = collect($categories ?? []);
    $categoryTerm = $categoryTerms->first();

    // Sidebar navigation: all docs grouped by category
    $allDocs = \Statamic\Facades\Entry::query()
        ->where('collection', 'docs')
        ->whereStatus('published')
        ->orderBy('order', 'asc')
        ->get();

    $sidebarGroups = [];
    $sidebarUncategorised = [];

    foreach ($allDocs as $entry) {
        $catSlugs = $entry->get('categories') ?? [];
        $catSlug = is_array($catSlugs) ? ($catSlugs[0] ?? null) : $catSlugs;

        if ($catSlug) {
            $sidebarGroups[$catSlug][] = $entry;
        } else {
            $sidebarUncategorised[] = $entry;
        }
    }

    // Related articles: same category, exclude current
    $currentSlug = $page?->slug() ?? '';
    $currentCatSlug = $categoryTerm ? $categoryTerm->slug() : null;
    $relatedArticles = collect();
    if ($currentCatSlug) {
        $relatedArticles = $allDocs
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

@section('title', $resolvedTitle . ' — Docs')

@section('content')
    <article class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- Breadcrumbs --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <a href="/docs" class="hover:text-gray-900">Docs</a>
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
                        <a href="/docs" class="text-sm font-semibold text-gray-900 hover:text-gray-600">Docs</a>

                        <nav class="mt-4 space-y-6">
                            @foreach ($sidebarGroups as $catSlug => $articles)
                                @php
                                    $catTerm = \Statamic\Facades\Term::find('categories::' . $catSlug);
                                    $catTitle = $catTerm ? $catTerm->title() : ucfirst($catSlug);
                                @endphp

                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $catTitle }}</h3>
                                    <ul class="mt-2 space-y-1">
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

                            @if (! empty($sidebarUncategorised))
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">General</h3>
                                    <ul class="mt-2 space-y-1">
                                        @foreach ($sidebarUncategorised as $entry)
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
                            @endif
                        </nav>
                    </div>
                </aside>

                {{-- Main Content --}}
                <div>
                    {{-- Header --}}
                    <header>
                        @if ($categoryTerm)
                            <div class="mb-4">
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                    {{ $categoryTerm->title() }}
                                </span>
                            </div>
                        @endif

                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $title }}</h1>
                        <p class="mt-3 text-sm text-gray-500">{{ $readingTime }} min read</p>
                    </header>

                    {{-- Content --}}
                    <div class="prose prose-gray mt-10 max-w-none prose-headings:font-semibold prose-a:text-gray-900 prose-a:underline hover:prose-a:text-gray-600">
                        {!! $content !!}
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
                            <h2 class="text-lg font-semibold text-gray-900">Related Docs</h2>
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
