<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnmatchedSearch extends Model
{
    protected $fillable = [
        'query',
        'search_count',
    ];

    protected function casts(): array
    {
        return [
            'search_count' => 'integer',
        ];
    }
}
