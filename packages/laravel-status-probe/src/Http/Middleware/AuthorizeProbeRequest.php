<?php

namespace Mbs047\LaravelStatusProbe\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeProbeRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('status-probe.auth.mode') !== 'bearer') {
            return $next($request);
        }

        $expectedToken = config('status-probe.auth.token');
        $providedToken = $request->bearerToken();

        if (! filled($expectedToken)) {
            return new JsonResponse([
                'message' => 'The status probe token is not configured.',
            ], 503);
        }

        if (! filled($providedToken) || ! hash_equals((string) $expectedToken, (string) $providedToken)) {
            return new JsonResponse([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
