<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveManagedBusiness
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $handle = $request->route('handle');

        $business = Business::query()
            ->where('handle', $handle)
            ->where('is_active', true)
            ->where('onboarding_completed', true)
            ->with('subscriptionTier:id,slug,name,stripe_price_id,trial_days')
            ->first();

        abort_if(! $business, 404);
        abort_if(! $business->canAccess($request->user()), 403);

        $request->attributes->set('currentBusiness', $business);

        return $next($request);
    }
}
