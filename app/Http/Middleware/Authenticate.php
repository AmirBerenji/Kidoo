<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Handle unauthenticated requests.
     */
    protected function redirectTo($request)
    {
        // For API requests, return JSON instead of redirecting
        if (! $request->expectsJson()) {
            abort(response()->json(['message' => 'Unauthenticated.'], 401));
        }
    }
}
