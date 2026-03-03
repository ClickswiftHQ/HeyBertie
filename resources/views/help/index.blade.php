@extends('layouts.marketing')

@section('title', 'Help Centre — heyBertie')
@section('meta_description', 'Find answers to common questions about using heyBertie for pet grooming bookings, payments, and account management.')

@section('content')
    {{-- Hero --}}
    <section class="bg-gray-50 py-16 sm:py-20" x-data="helpSearch()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Help Centre</h1>
            <p class="mt-4 max-w-2xl text-lg text-gray-600">Find answers and step-by-step guides for using heyBertie.</p>

            {{-- Search --}}
            <div class="mt-8 max-w-xl">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        type="text"
                        x-model="query"
                        placeholder="Search help articles..."
                        class="w-full rounded-lg border border-gray-300 py-3 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-500 focus:border-gray-500 focus:ring-1 focus:ring-gray-500 focus:outline-none"
                    >
                </div>
            </div>
        </div>

        {{-- Articles --}}
        <div class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8">
            @php
                $allArticles = \Statamic\Facades\Entry::query()
                    ->where('collection', 'help')
                    ->whereStatus('published')
                    ->orderBy('order', 'asc')
                    ->get();

                $audienceGroups = [];

                foreach ($allArticles as $entry) {
                    $audienceSlugs = $entry->get('audience') ?? [];
                    $audienceSlug = is_array($audienceSlugs) ? ($audienceSlugs[0] ?? 'uncategorised') : $audienceSlugs;
                    $categorySlugs = $entry->get('categories') ?? [];
                    $categorySlug = is_array($categorySlugs) ? ($categorySlugs[0] ?? 'general') : $categorySlugs;

                    $audienceGroups[$audienceSlug][$categorySlug][] = $entry;
                }

                $audienceOrder = ['for-groomers', 'for-pet-owners'];
            @endphp

            @if ($allArticles->isEmpty())
                <div class="py-12 text-center">
                    <p class="text-lg text-gray-500">No help articles yet. Check back soon!</p>
                </div>
            @else
                {{-- No results message --}}
                <div x-show="query.length > 0 && visibleCount === 0" x-cloak class="py-12 text-center">
                    <p class="text-lg text-gray-500">No articles match your search.</p>
                </div>

                @foreach ($audienceOrder as $audSlug)
                    @if (isset($audienceGroups[$audSlug]))
                        @php
                            $audienceTerm = \Statamic\Facades\Term::find('audience::' . $audSlug);
                            $audienceTitle = $audienceTerm ? $audienceTerm->title() : ucfirst($audSlug);
                        @endphp

                        <div class="audience-group mb-12" x-show="isAudienceVisible('{{ $audSlug }}')" x-cloak:remove>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $audienceTitle }}</h2>

                            @foreach ($audienceGroups[$audSlug] as $catSlug => $articles)
                                @php
                                    $categoryTerm = \Statamic\Facades\Term::find('categories::' . $catSlug);
                                    $categoryTitle = $categoryTerm ? $categoryTerm->title() : ucfirst($catSlug);
                                @endphp

                                <div class="category-group mt-8" x-show="isCategoryVisible('{{ $audSlug }}', '{{ $catSlug }}')" x-cloak:remove>
                                    <h3 class="text-lg font-semibold text-gray-700">{{ $categoryTitle }}</h3>

                                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($articles as $entry)
                                            <article
                                                class="help-article group"
                                                data-audience="{{ $audSlug }}"
                                                data-category="{{ $catSlug }}"
                                                data-title="{{ strtolower($entry->get('title')) }}"
                                                data-excerpt="{{ strtolower($entry->get('excerpt') ?? '') }}"
                                                x-show="matchesSearch($el)"
                                                x-cloak:remove
                                            >
                                                <a href="{{ $entry->url() }}" class="block rounded-lg border border-gray-200 bg-white p-6 transition-colors hover:border-gray-300 hover:bg-gray-50">
                                                    <h4 class="font-semibold text-gray-900 group-hover:text-gray-600">
                                                        {{ $entry->get('title') }}
                                                    </h4>

                                                    @if ($entry->get('excerpt'))
                                                        <p class="mt-2 line-clamp-2 text-sm text-gray-600">{{ $entry->get('excerpt') }}</p>
                                                    @endif

                                                    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gray-900">
                                                        Read article
                                                        <svg class="size-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                                        </svg>
                                                    </span>
                                                </a>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </section>

    <script>
        function helpSearch() {
            return {
                query: '',
                visibleCount: 0,

                matchesSearch(el) {
                    if (this.query.length === 0) return true;
                    const q = this.query.toLowerCase();
                    const title = el.dataset.title || '';
                    const excerpt = el.dataset.excerpt || '';
                    return title.includes(q) || excerpt.includes(q);
                },

                isCategoryVisible(audience, category) {
                    if (this.query.length === 0) return true;
                    const articles = document.querySelectorAll(`.help-article[data-audience="${audience}"][data-category="${category}"]`);
                    return Array.from(articles).some(el => this.matchesSearch(el));
                },

                isAudienceVisible(audience) {
                    if (this.query.length === 0) return true;
                    const articles = document.querySelectorAll(`.help-article[data-audience="${audience}"]`);
                    return Array.from(articles).some(el => this.matchesSearch(el));
                },

                init() {
                    this.$watch('query', () => {
                        this.$nextTick(() => {
                            const all = document.querySelectorAll('.help-article');
                            this.visibleCount = Array.from(all).filter(el => this.matchesSearch(el)).length;
                        });
                    });
                }
            };
        }
    </script>
@endsection
