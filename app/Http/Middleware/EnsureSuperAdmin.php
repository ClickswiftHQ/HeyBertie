<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Statamic\Exceptions\ForbiddenHttpException;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->super) {
            throw new ForbiddenHttpException;
        }

        return $next($request);
    }
}
