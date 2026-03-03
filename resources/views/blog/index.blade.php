@extends('layouts.marketing')

@section('title', 'Blog — heyBertie')
@section('meta_description', 'Tips, guides, and insights for pet grooming professionals. Grow your business with heyBertie.')

@section('content')
    {{-- Hero --}}
    <section class="bg-gray-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Blog</h1>
            <p class="mt-4 max-w-2xl text-lg text-gray-600">Tips, insights, and stories for pet grooming professionals and pet owners alike.</p>
        </div>
    </section>

    {{-- Posts --}}
    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @php
                $entries = \Statamic\Facades\Entry::query()
                    ->where('collection', 'blog')
                    ->whereStatus('published')
                    ->orderBy('date', 'desc')
                    ->paginate(12);
            @endphp

            @if ($entries->isEmpty())
                <div class="py-12 text-center">
                    <p class="text-lg text-gray-500">No posts yet. Check back soon!</p>
                </div>
            @else
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($entries as $entry)
                        <article class="group">
                            <a href="{{ $entry->url() }}" class="block">
                                {{-- Image --}}
                                <div class="aspect-[16/9] overflow-hidden rounded-lg bg-gray-100">
                                    @if ($entry->get('featured_image'))
                                        <img
                                            src="{{ \Statamic\Facades\Asset::find($entry->get('featured_image'))?->url() }}"
                                            alt="{{ $entry->get('title') }}"
                                            class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        >
                                    @else
                                        <div class="flex size-full items-center justify-center">
                                            <svg class="size-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="mt-4">
                                    @if ($entry->get('categories'))
                                        <div class="mb-2 flex flex-wrap gap-2">
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

                                    <h2 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600">
                                        {{ $entry->get('title') }}
                                    </h2>

                                    @if ($entry->get('excerpt'))
                                        <p class="mt-2 line-clamp-2 text-sm text-gray-600">{{ $entry->get('excerpt') }}</p>
                                    @endif

                                    <div class="mt-3 flex items-center gap-3 text-sm text-gray-500">
                                        <time datetime="{{ $entry->date()->format('Y-m-d') }}">
                                            {{ $entry->date()->format('M j, Y') }}
                                        </time>
                                        @if ($entry->get('author'))
                                            <span>&middot;</span>
                                            <span>{{ $entry->get('author') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if ($entries->hasPages())
                    <div class="mt-12">
                        {{ $entries->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>
@endsection
