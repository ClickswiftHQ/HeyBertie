<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use App\Support\PostcodeFormatter;
use Illuminate\Http\JsonResponse;

class PostcodeLookupController extends Controller
{
    public function __invoke(string $postcode, GeocodingService $geocodingService): JsonResponse
    {
        if (! PostcodeFormatter::isValid($postcode)) {
            return response()->json([]);
        }

        $addresses = $geocodingService->lookupPostcode($postcode);

        return response()->json($addresses ?? []);
    }
}
