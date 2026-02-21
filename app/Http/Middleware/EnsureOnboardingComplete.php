<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $hasDraft = Business::query()
            ->where('owner_user_id', $request->user()->id)
            ->where('onboarding_completed', false)
            ->exists();

        if ($hasDraft) {
            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
