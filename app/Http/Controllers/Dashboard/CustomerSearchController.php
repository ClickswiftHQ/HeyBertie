<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = $business->customers()
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->orderByDesc('last_visit')
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($customers);
    }
}
