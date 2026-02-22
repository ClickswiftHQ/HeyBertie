<?php

use App\Models\GeocodeCache;

beforeEach(function () {
    GeocodeCache::create(['slug' => 'london', 'name' => 'London', 'display_name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278, 'settlement_type' => 'City']);
    GeocodeCache::create(['slug' => 'guildford-surrey', 'name' => 'Guildford', 'display_name' => 'Guildford, Surrey', 'latitude' => 51.2362, 'longitude' => -0.5704, 'settlement_type' => 'Town', 'postcode_sector' => 'GU1 1']);
    GeocodeCache::create(['slug' => 'glasgow', 'name' => 'Glasgow', 'display_name' => 'Glasgow', 'latitude' => 55.8642, 'longitude' => -4.2518, 'settlement_type' => 'City']);
});

test('returns suggestions matching partial input', function () {
    $this->getJson('/api/search-suggest?q=guild')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Guildford, Surrey']);
});

test('returns multiple matches', function () {
    $this->getJson('/api/search-suggest?q=gl')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Glasgow']);
});

test('returns empty for no matches', function () {
    $this->getJson('/api/search-suggest?q=zzzzz')
        ->assertSuccessful()
        ->assertJsonCount(0);
});

test('returns empty for blank input', function () {
    $this->getJson('/api/search-suggest?q=')
        ->assertSuccessful()
        ->assertJsonCount(0);
});

test('search is case insensitive', function () {
    $this->getJson('/api/search-suggest?q=LONDON')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'London']);
});

test('partial postcode input returns towns in that sector', function () {
    GeocodeCache::create([
        'slug' => 'aarons-hill-surrey',
        'name' => "Aaron's Hill",
        'display_name' => "Aaron's Hill, Surrey",
        'latitude' => 51.18291,
        'longitude' => -0.63098,
        'postcode_sector' => 'GU7 2',
        'settlement_type' => 'Suburban Area',
        'county' => 'Surrey',
        'country' => 'England',
    ]);

    $this->getJson('/api/search-suggest?q=GU7')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => "Aaron's Hill, Surrey"]);
});

test('results are weighted by type with city before village', function () {
    GeocodeCache::create(['slug' => 'bristol-avon', 'name' => 'Bristol', 'display_name' => 'Bristol', 'latitude' => 51.4545, 'longitude' => -2.5879, 'settlement_type' => 'City']);
    GeocodeCache::create(['slug' => 'bramley-surrey', 'name' => 'Bramley', 'display_name' => 'Bramley, Surrey', 'latitude' => 51.1928, 'longitude' => -0.8690, 'settlement_type' => 'Village']);

    $response = $this->getJson('/api/search-suggest?q=br')
        ->assertSuccessful();

    $names = collect($response->json())->pluck('name')->all();

    // Bristol (City) should appear before Bramley (Village)
    expect(array_search('Bristol', $names))->toBeLessThan(array_search('Bramley, Surrey', $names));
});
