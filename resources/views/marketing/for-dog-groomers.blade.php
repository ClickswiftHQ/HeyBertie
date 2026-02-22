@extends('layouts.marketing')

@section('title', $metaTitle)
@section('meta_description', $metaDescription)

@push('head')
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endpush

@section('content')
    {{-- Hero --}}
    @include('marketing.partials.hero', [
        'headline' => 'Grow your dog grooming business with heyBertie',
        'subheadline' => 'Get discovered by local pet owners, manage bookings online, and run your business smarter — all from one platform.',
        'primaryCtaText' => 'Join heyBertie Free',
        'primaryCtaUrl' => route('join'),
        'secondaryCtaText' => 'See how it works',
        'secondaryCtaUrl' => '#how-it-works',
    ])

    {{-- Trust Bar --}}
    <section class="border-y-2 border-gray-200 bg-white px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-4xl grid-cols-1 gap-8 text-center sm:grid-cols-3">
            <div>
                <p class="text-3xl font-bold text-gray-900">2,500+</p>
                <p class="mt-1 text-sm text-gray-600">Groomers Listed</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900">50,000+</p>
                <p class="mt-1 text-sm text-gray-600">Bookings Managed</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900">4.9 &#9733;</p>
                <p class="mt-1 text-sm text-gray-600">Average Professional Rating</p>
            </div>
        </div>
    </section>

    {{-- Marketplace Benefits --}}
    @include('marketing.partials.feature-grid', [
        'sectionClass' => 'bg-gray-50 px-4 py-12 sm:px-6 md:py-20 lg:px-8',
        'title' => 'Get found by pet owners near you',
        'subtitle' => 'heyBertie puts your business in front of thousands of pet owners actively searching for groomers in your area.',
        'columns' => 4,
        'features' => [
            ['icon' => '&#128269;', 'title' => 'Get Discovered Locally', 'description' => 'Appear in search results when pet owners look for groomers in your area.'],
            ['icon' => '&#10003;', 'title' => 'Verified Profile', 'description' => 'Stand out with a verified badge that builds trust with new clients.'],
            ['icon' => '&#11088;', 'title' => 'Real Reviews', 'description' => 'Collect and showcase genuine reviews from your happy customers.'],
            ['icon' => '&#9889;', 'title' => 'Instant Bookings', 'description' => 'Let clients book appointments directly — no phone tag required.'],
        ],
    ])

    {{-- Software Features --}}
    @include('marketing.partials.feature-grid', [
        'title' => 'Powerful tools to run your salon',
        'subtitle' => 'Everything you need to manage and grow your grooming business, built into one simple dashboard.',
        'columns' => 4,
        'features' => [
            ['icon' => '&#128197;', 'title' => 'Online Booking System', 'description' => 'Clients book 24/7 through your profile. You control your availability and services.'],
            ['icon' => '&#128101;', 'title' => 'Client Management', 'description' => 'Keep track of pet details, grooming history, and client preferences in one place.'],
            ['icon' => '&#128276;', 'title' => 'Automated Reminders', 'description' => 'Reduce no-shows with automatic appointment reminders sent to your clients.'],
            ['icon' => '&#128200;', 'title' => 'Business Analytics', 'description' => 'Track bookings, revenue, and growth with easy-to-read reports and insights.'],
        ],
    ])

    {{-- How It Works --}}
    <div id="how-it-works">
        @include('marketing.partials.how-it-works', [
            'title' => 'Get started in 3 easy steps',
            'steps' => [
                ['title' => 'Create Your Profile', 'description' => 'Sign up for free and build your professional profile with photos, services, and pricing.'],
                ['title' => 'Get Discovered', 'description' => 'Pet owners in your area find you through heyBertie search and start booking appointments.'],
                ['title' => 'Manage & Grow', 'description' => 'Use your dashboard to manage bookings, track clients, and grow your business.'],
            ],
        ])
    </div>

    {{-- Testimonials --}}
    @include('marketing.partials.testimonials', [
        'title' => 'Trusted by groomers across the UK',
        'testimonials' => [
            [
                'quote' => 'Since joining heyBertie, my bookings have increased by 40%. The online booking system alone saves me hours every week.',
                'name' => 'Sarah Mitchell',
                'role' => 'Owner',
                'business' => 'Paws & Claws Grooming',
            ],
            [
                'quote' => 'I love how easy it is to manage my clients and their pets. The automated reminders have practically eliminated no-shows.',
                'name' => 'James Cooper',
                'role' => 'Head Groomer',
                'business' => 'The Dog House',
            ],
            [
                'quote' => 'heyBertie helped me go from a solo groomer to running a full salon. The analytics showed me exactly where to invest.',
                'name' => 'Emma Richards',
                'role' => 'Founder',
                'business' => 'Bark & Beauty',
            ],
        ],
    ])

    {{-- Pricing Teaser --}}
    <section class="bg-gray-50 px-4 py-12 sm:px-6 md:py-20 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">Simple, transparent pricing</h2>
            <p class="mx-auto mt-4 max-w-2xl text-center text-gray-600">Start free and upgrade as your business grows. No hidden fees, cancel anytime.</p>
            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                {{-- Free Tier --}}
                <div class="rounded-lg border-2 border-gray-200 bg-white p-8 text-center">
                    <h3 class="text-lg font-semibold text-gray-900">Free</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">&pound;0</p>
                    <p class="mt-1 text-sm text-gray-500">per month</p>
                    <ul class="mt-6 space-y-3 text-left text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Business profile listing</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Appear in search results</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Collect reviews</span>
                        </li>
                    </ul>
                </div>

                {{-- Solo Tier --}}
                <div class="rounded-lg border-2 border-gray-900 bg-white p-8 text-center">
                    <h3 class="text-lg font-semibold text-gray-900">Solo</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">&pound;19</p>
                    <p class="mt-1 text-sm text-gray-500">per month</p>
                    <ul class="mt-6 space-y-3 text-left text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Everything in Free</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Online booking system</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Automated reminders</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Client management</span>
                        </li>
                    </ul>
                </div>

                {{-- Salon Tier --}}
                <div class="rounded-lg border-2 border-gray-200 bg-white p-8 text-center">
                    <h3 class="text-lg font-semibold text-gray-900">Salon</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">&pound;49</p>
                    <p class="mt-1 text-sm text-gray-500">per month</p>
                    <ul class="mt-6 space-y-3 text-left text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Everything in Solo</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Multi-location support</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Business analytics</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 text-gray-900">&#10003;</span>
                            <span>Priority support</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 text-center">
                <a href="#" class="text-sm font-medium text-gray-900 underline hover:text-gray-700">View full pricing details</a>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    @include('marketing.partials.faq', [
        'title' => 'Frequently asked questions',
        'faqs' => [
            [
                'question' => 'Is heyBertie free to join?',
                'answer' => 'Yes! You can create a profile and get listed on heyBertie completely free. Upgrade to a paid plan when you\'re ready for booking management, automated reminders, and more.',
            ],
            [
                'question' => 'How do pet owners find my business?',
                'answer' => 'Pet owners search heyBertie by service type and location. Your profile appears in relevant search results, complete with your services, photos, reviews, and availability.',
            ],
            [
                'question' => 'Can I manage multiple locations?',
                'answer' => 'Yes, our Salon plan supports multiple locations under one account. Each location gets its own profile page, services, and booking calendar.',
            ],
            [
                'question' => 'How does the online booking system work?',
                'answer' => 'You set your available hours, services, and pricing. Clients book directly through your profile — you get notified instantly and can manage everything from your dashboard.',
            ],
            [
                'question' => 'What if I already have a website?',
                'answer' => 'heyBertie works alongside your existing website. Think of it as an additional channel to reach new clients who are actively searching for grooming services in your area.',
            ],
            [
                'question' => 'Can I cancel at any time?',
                'answer' => 'Absolutely. There are no long-term contracts. You can upgrade, downgrade, or cancel your plan at any time from your dashboard.',
            ],
        ],
    ])

    {{-- CTA Banner --}}
    @include('marketing.partials.cta-banner', [
        'headline' => 'Ready to grow your grooming business?',
        'description' => 'Join thousands of dog groomers already using heyBertie to get discovered, manage bookings, and grow their business.',
        'ctaText' => 'Get Started Free',
        'ctaUrl' => route('join'),
    ])
@endsection
