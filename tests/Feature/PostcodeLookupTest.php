<?php

use App\Services\GeocodingService;

test('valid postcode returns address list', function () {
    $this->mock(GeocodingService::class)
        ->shouldReceive('lookupPostcode')
        ->with('SW1A1AA')
        ->andReturn([
            [
                'line_1' => 'Buckingham Palace',
                'line_2' => '',
                'line_3' => '',
                'post_town' => 'LONDON',
                'county' => '',
                'postcode' => 'SW1A 1AA',
                'latitude' => 51.5014,
                'longitude' => -0.1419,
            ],
        ]);

    $this->get('/api/postcode-lookup/SW1A1AA')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['line_1' => 'Buckingham Palace']);
});

test('invalid postcode returns empty array', function () {
    $this->get('/api/postcode-lookup/12345')
        ->assertSuccessful()
        ->assertJson([]);
});

test('valid postcode with no results returns empty array', function () {
    $this->mock(GeocodingService::class)
        ->shouldReceive('lookupPostcode')
        ->with('ZZ99ZZ')
        ->andReturn(null);

    $this->get('/api/postcode-lookup/ZZ99ZZ')
        ->assertSuccessful()
        ->assertJson([]);
});
