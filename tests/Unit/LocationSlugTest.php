<?php

use App\Models\Location;

test('generateSlug creates town-city slug when different', function () {
    expect(Location::generateSlug('Fulham', 'London'))->toBe('fulham-london');
});

test('generateSlug deduplicates when town equals city', function () {
    expect(Location::generateSlug('London', 'London'))->toBe('london');
});

test('generateSlug handles multi-word towns', function () {
    expect(Location::generateSlug('St Albans', 'London'))->toBe('st-albans-london');
});

test('generateSlug trims whitespace', function () {
    expect(Location::generateSlug(' Fulham ', ' London '))->toBe('fulham-london');
});

test('generateSlug deduplicates case-insensitively', function () {
    expect(Location::generateSlug('london', 'LONDON'))->toBe('london');
});
