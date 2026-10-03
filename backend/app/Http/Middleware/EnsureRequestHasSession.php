<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequestHasSession
{
    /**
     * Reject requests that Sanctum did not treat as coming from the SPA.
     *
     * Sanctum only starts a session for requests from a stateful domain, so
     * session-based endpoints such as login would otherwise fail with a 500.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return response()->json([
                'message' => 'This endpoint only accepts requests from the frontend application.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
