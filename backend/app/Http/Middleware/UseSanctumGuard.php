<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the user through Sanctum (session cookie or API token) on every
 * API route, so public endpoints such as the course page still know who
 * is signed in, and policies receive that user. Routes that require login
 * add auth:sanctum on top.
 */
class UseSanctumGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
