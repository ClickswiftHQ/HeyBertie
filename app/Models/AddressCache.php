<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddressCache extends Model
{
    protected $table = 'address_cache';

    protected $fillable = [
        'postcode',
        'line_1',
        'line_2',
        'line_3',
        'post_town',
        'county',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
