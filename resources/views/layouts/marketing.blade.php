<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Find trusted pet groomers, dog walkers, and cat sitters near you. Compare reviews, check availability, and book instantly on heyBertie.">

        <title>{{ $title ?? 'heyBertie — Find Trusted Pet Services Near You' }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css'])

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="bg-white font-sans text-gray-900 antialiased">
        {{-- Header --}}
        <header class="sticky top-0 z-50 border-b-2 border-gray-200 bg-white" x-data="{ mobileMenuOpen: false }">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="text-2xl font-bold tracking-tight text-gray-900">
                    heyBertie
                </a>

                {{-- Desktop Nav --}}
                <nav class="hidden items-center gap-8 md:flex">
                    <a href="#" class="text-sm font-medium text-gray-600 hover:text-gray-900">For Groomers</a>
                    <a href="#" class="text-sm font-medium text-gray-600 hover:text-gray-900">Pricing</a>
                    <a href="#" class="text-sm font-medium text-gray-600 hover:text-gray-900">Help</a>
                </nav>

                {{-- Desktop Auth --}}
                <div class="hidden items-center gap-4 md:flex">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-gray-900 px-6 py-2.5 text-sm font-medium text-white hover:bg-gray-800">
                        Sign Up
                    </a>
                </div>

                {{-- Mobile Hamburger --}}
                <button
                    class="md:hidden"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    aria-label="Toggle menu"
                >
                    <svg x-show="!mobileMenuOpen" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Mobile Menu --}}
            <div x-show="mobileMenuOpen" x-cloak x-transition class="border-t border-gray-200 bg-white md:hidden">
                <div class="space-y-1 px-4 py-4">
                    <a href="#" class="block rounded-lg px-3 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">For Groomers</a>
                    <a href="#" class="block rounded-lg px-3 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">Pricing</a>
                    <a href="#" class="block rounded-lg px-3 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">Help</a>
                </div>
                <div class="border-t border-gray-200 px-4 py-4">
                    <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">Login</a>
                    <a href="{{ route('register') }}" class="mt-2 block rounded-lg bg-gray-900 px-3 py-2.5 text-center text-base font-medium text-white hover:bg-gray-800">Sign Up</a>
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <main>
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="border-t-2 border-gray-200 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    {{-- Product --}}
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-900">Product</h3>
                        <ul class="mt-4 space-y-3">
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Search</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Dog Grooming</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Dog Walking</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Cat Sitting</a></li>
                        </ul>
                    </div>

                    {{-- Resources --}}
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-900">Resources</h3>
                        <ul class="mt-4 space-y-3">
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Blog</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Help Centre</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">FAQs</a></li>
                        </ul>
                    </div>

                    {{-- Company --}}
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-900">Company</h3>
                        <ul class="mt-4 space-y-3">
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">About</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Careers</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Contact</a></li>
                        </ul>
                    </div>

                    {{-- Legal --}}
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-900">Legal</h3>
                        <ul class="mt-4 space-y-3">
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Privacy Policy</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Terms of Service</a></li>
                            <li><a href="#" class="text-sm text-gray-600 hover:text-gray-900">Cookie Policy</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 border-t border-gray-300 pt-8">
                    <p class="text-center text-sm text-gray-500">&copy; {{ date('Y') }} heyBertie. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
