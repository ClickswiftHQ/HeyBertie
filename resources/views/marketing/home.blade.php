@extends('layouts.marketing')

@section('content')
    {{-- Hero + Search --}}
    <section class="bg-gray-50 px-4 py-12 sm:px-6 md:py-20 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl md:text-6xl">
                Find trusted pet services near you
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600">
                Compare local groomers, walkers, and sitters. Read real reviews, check availability, and book instantly.
            </p>

            {{-- Search Form --}}
            <form
                action="/search"
                method="GET"
                class="mx-auto mt-10 max-w-3xl"
                x-data="{ location: '', submitted: false }"
                @submit="submitted = true; if (!location.trim()) { $event.preventDefault(); $refs.locationInput.focus(); }"
            >
                <div class="flex flex-col gap-3 md:flex-row">
                    <select
                        name="service"
                        class="w-full rounded-lg border-2 border-gray-300 px-4 py-3 text-gray-900 focus:border-gray-900 focus:outline-none md:w-auto"
                    >
                        <option value="dog-grooming">Dog Grooming</option>
                        <option value="dog-walking">Dog Walking</option>
                        <option value="cat-sitting">Cat Sitting</option>
                    </select>

                    <div class="relative flex-1">
                        <input
                            type="text"
                            name="location"
                            placeholder="e.g. London, SW1A"
                            x-model="location"
                            x-ref="locationInput"
                            class="w-full rounded-lg border-2 px-4 py-3 text-gray-900 focus:border-gray-900 focus:outline-none"
                            :class="submitted && !location.trim() ? 'border-red-500' : 'border-gray-300'"
                        >
                        <p
                            x-show="submitted && !location.trim()"
                            x-cloak
                            class="mt-1 text-left text-sm text-red-600"
                        >
                            Please enter a location.
                        </p>
                    </div>

                    <input
                        type="date"
                        name="date"
                        class="w-full rounded-lg border-2 border-gray-300 px-4 py-3 text-gray-900 focus:border-gray-900 focus:outline-none md:w-auto"
                    >

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-gray-900 px-8 py-3 font-medium text-white hover:bg-gray-800 md:w-auto"
                    >
                        Search
                    </button>
                </div>
            </form>

            {{-- Popular City Quick Links --}}
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2 text-sm text-gray-500">
                <span>Popular:</span>
                <a href="/search?location=London" class="font-medium text-gray-700 underline hover:text-gray-900">London</a>
                <a href="/search?location=Manchester" class="font-medium text-gray-700 underline hover:text-gray-900">Manchester</a>
                <a href="/search?location=Birmingham" class="font-medium text-gray-700 underline hover:text-gray-900">Birmingham</a>
                <a href="/search?location=Leeds" class="font-medium text-gray-700 underline hover:text-gray-900">Leeds</a>
                <a href="/search?location=Bristol" class="font-medium text-gray-700 underline hover:text-gray-900">Bristol</a>
            </div>
        </div>
    </section>

    {{-- Trust Bar --}}
    <section class="border-y-2 border-gray-200 bg-white px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-4xl grid-cols-1 gap-8 text-center sm:grid-cols-3">
            <div>
                <p class="text-3xl font-bold text-gray-900">2,500+</p>
                <p class="mt-1 text-sm text-gray-600">Verified Professionals</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900">50,000+</p>
                <p class="mt-1 text-sm text-gray-600">Happy Pets Served</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900">4.9 &#9733;</p>
                <p class="mt-1 text-sm text-gray-600">Average Rating</p>
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="px-4 py-12 sm:px-6 md:py-20 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">How it works</h2>
            <div class="mt-12 grid grid-cols-1 gap-10 md:grid-cols-3">
                <div class="text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full border-2 border-gray-900 text-2xl font-bold text-gray-900">
                        1
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-900">Search</h3>
                    <p class="mt-2 text-gray-600">Enter your location and the service you need. Browse local professionals with real reviews.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full border-2 border-gray-900 text-2xl font-bold text-gray-900">
                        2
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-900">Compare</h3>
                    <p class="mt-2 text-gray-600">View profiles, read reviews, and compare prices. Find the perfect match for your pet.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full border-2 border-gray-900 text-2xl font-bold text-gray-900">
                        3
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-900">Book</h3>
                    <p class="mt-2 text-gray-600">Book instantly online. Pay securely through heyBertie with our satisfaction guarantee.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Popular Services --}}
    <section class="bg-gray-50 px-4 py-12 sm:px-6 md:py-20 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">Popular services</h2>
            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <a href="/search?service=dog-grooming" class="rounded-lg border-2 border-gray-300 bg-white p-8 text-center transition hover:border-gray-900">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-gray-100 text-3xl">
                        &#9988;
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-900">Dog Grooming</h3>
                    <p class="mt-2 text-sm text-gray-600">Professional grooming from bath and brush to full breed-standard cuts.</p>
                </a>
                <a href="/search?service=dog-walking" class="rounded-lg border-2 border-gray-300 bg-white p-8 text-center transition hover:border-gray-900">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-gray-100 text-3xl">
                        &#128054;
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-900">Dog Walking</h3>
                    <p class="mt-2 text-sm text-gray-600">Trusted local walkers for daily strolls, adventure hikes, and everything in between.</p>
                </a>
                <a href="/search?service=cat-sitting" class="rounded-lg border-2 border-gray-300 bg-white p-8 text-center transition hover:border-gray-900">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-gray-100 text-3xl">
                        &#128049;
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-900">Cat Sitting</h3>
                    <p class="mt-2 text-sm text-gray-600">In-home cat sitters who'll keep your feline happy while you're away.</p>
                </a>
            </div>
        </div>
    </section>

    {{-- Popular Cities --}}
    <section class="px-4 py-12 sm:px-6 md:py-20 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">Popular cities</h2>
            <div class="mt-12 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach (['London', 'Manchester', 'Birmingham', 'Leeds', 'Bristol', 'Liverpool', 'Edinburgh', 'Glasgow', 'Sheffield', 'Newcastle', 'Cardiff', 'Nottingham'] as $city)
                    <a href="/search?location={{ urlencode($city) }}" class="rounded-lg border-2 border-gray-300 px-4 py-3 text-center text-sm font-medium text-gray-700 transition hover:border-gray-900 hover:text-gray-900">
                        {{ $city }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features Grid --}}
    <section class="bg-gray-50 px-4 py-12 sm:px-6 md:py-20 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <h2 class="text-center text-3xl font-bold text-gray-900 md:text-4xl">Why choose heyBertie</h2>
            <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-gray-200 text-xl">
                        &#9889;
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Instant Booking</h3>
                    <p class="mt-2 text-sm text-gray-600">Book confirmed appointments in seconds, no back-and-forth needed.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-gray-200 text-xl">
                        &#10003;
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Verified Pros</h3>
                    <p class="mt-2 text-sm text-gray-600">Every professional is ID-checked, insured, and reviewed by real customers.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-gray-200 text-xl">
                        &#128274;
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Secure Payments</h3>
                    <p class="mt-2 text-sm text-gray-600">Pay safely through heyBertie. Your money is protected until the job is done.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-gray-200 text-xl">
                        &#11088;
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Real Reviews</h3>
                    <p class="mt-2 text-sm text-gray-600">Honest feedback from verified customers helps you choose with confidence.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Professional CTA --}}
    <section class="px-4 py-12 sm:px-6 md:py-20 lg:px-8">
        <div class="mx-auto max-w-3xl rounded-lg border-2 border-gray-900 bg-white p-8 text-center md:p-12">
            <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">Are you a dog groomer?</h2>
            <p class="mx-auto mt-4 max-w-xl text-gray-600">
                Join thousands of pet professionals growing their business on heyBertie. Get discovered by local pet owners and manage your bookings in one place.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ route('register') }}" class="w-full rounded-lg bg-gray-900 px-8 py-4 font-medium text-white hover:bg-gray-800 sm:w-auto">
                    Join as a Professional
                </a>
                <a href="#" class="w-full rounded-lg border-2 border-gray-900 px-8 py-4 font-medium text-gray-900 hover:bg-gray-50 sm:w-auto">
                    Learn More
                </a>
            </div>
        </div>
    </section>
@endsection
