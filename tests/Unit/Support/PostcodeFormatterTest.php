<?php

use App\Support\PostcodeFormatter;

test('formats lowercase postcode without spaces', function () {
    expect(PostcodeFormatter::format('gu166hd'))->toBe('GU16 6HD');
});

test('formats lowercase postcode with space', function () {
    expect(PostcodeFormatter::format('sw1a 1aa'))->toBe('SW1A 1AA');
});

test('preserves already formatted postcode', function () {
    expect(PostcodeFormatter::format('M1 1AE'))->toBe('M1 1AE');
});

test('trims whitespace and normalises', function () {
    expect(PostcodeFormatter::format('  sk1  1eb  '))->toBe('SK1 1EB');
});

test('formats postcode with multiple spaces', function () {
    expect(PostcodeFormatter::format('EC1A  1BB'))->toBe('EC1A 1BB');
});

test('validates correct UK postcodes', function (string $postcode) {
    expect(PostcodeFormatter::isValid($postcode))->toBeTrue();
})->with([
    'SW1A 1AA',
    'GU16 6HD',
    'M1 1AE',
    'sk11eb',
    'EC1A 1BB',
    'W1A 0AX',
]);

test('rejects invalid postcodes', function (string $postcode) {
    expect(PostcodeFormatter::isValid($postcode))->toBeFalse();
})->with([
    '12345',
    'ABCDEF',
    '',
    'London',
    'ABC 123',
]);
