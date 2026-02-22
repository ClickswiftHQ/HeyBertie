<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    /**
     * Return autocomplete suggestions for location search.
     */
    public function __invoke(Request $request, GeocodingService $geocodingService): JsonResponse
    {
        $input = $request->string('q')->trim()->value();

        if ($input === '') {
            return response()->json([]);
        }

        return response()->json($geocodingService->suggest($input));
    }
}
