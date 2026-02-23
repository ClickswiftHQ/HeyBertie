<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BreedSuggestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->value();

        if ($query === '') {
            return response()->json([]);
        }

        $breeds = Breed::query()
            ->where('name', 'like', "%{$query}%")
            ->with('species:id,name')
            ->orderBy('sort_order')
            ->limit(10)
            ->get(['id', 'name', 'species_id']);

        return response()->json(
            $breeds->map(fn (Breed $breed) => [
                'name' => $breed->name,
                'species' => $breed->species->name,
            ])->values()
        );
    }
}
