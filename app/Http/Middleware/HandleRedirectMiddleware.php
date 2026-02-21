<?php

namespace App\Http\Middleware;

use App\Models\HandleChange;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $handle = $request->route('handle');

        if (! $handle) {
            return $next($request);
        }

        $handleChange = HandleChange::query()
            ->forHandle($handle)
            ->latest('changed_at')
            ->first();

        if (! $handleChange) {
            return $next($request);
        }

        $locationSlug = $request->route('locationSlug');
        $redirectUrl = $locationSlug
            ? route('business.location', [$handleChange->new_handle, $locationSlug])
            : route('business.show', $handleChange->new_handle);

        return redirect($redirectUrl, 301);
    }
}
