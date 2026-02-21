@extends('layouts.marketing')

@section('title', $business->name . ' - ' . ($location->location_type === 'mobile' ? 'Mobile Dog Grooming' : 'Dog Grooming') . ' in ' . $location->city)

@php
    $locationType = $location->location_type === 'mobile' ? 'Mobile Dog Grooming' : 'Dog Grooming';
    $lowestPrice = $services->whereNotNull('price')->min('price');
    $priceText = $lowestPrice !== null ? ' From £' . number_format($lowestPrice, 0) . '.' : '';
    $ratingText = $rating['count'] > 0 ? ' ' . $rating['average'] . ' (' . $rating['count'] . ' reviews).' : '';
    $metaDescription = $business->description
        ? Str::limit($business->description, 120, '')
        : 'Professional ' . strtolower($locationType) . ' in ' . $location->city . '.' . $priceText . $ratingText;
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
    @include('listing.partials.header')

    @include('listing.partials.location-switcher')

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
            <div class="space-y-8 lg:col-span-2">
                @include('listing.partials.about')
                <hr class="border-gray-200">
                @include('listing.partials.services')
                <hr class="border-gray-200">
                @include('listing.partials.reviews')
                <hr class="border-gray-200">
                @include('listing.partials.location')
                <hr class="border-gray-200">
                @include('listing.partials.contact')
            </div>

            <div class="mt-8 lg:mt-0">
                <div class="sticky top-6 space-y-6">
                    @include('listing.partials.cta')
                    @include('listing.partials.availability')
                </div>
            </div>
        </div>
    </div>

    @include('listing.partials.sticky-booking-bar')
    @include('listing.partials.share-dialog')
@endsection
