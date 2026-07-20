<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.admin.token', '');

        if ($configuredToken === '') {
            return new JsonResponse([
                'ok' => false,
                'message' => 'ADMIN_API_TOKEN is not configured.',
            ], 500);
        }

        $providedToken = (string) $request->header('X-Admin-Token', '');

        if (! hash_equals($configuredToken, $providedToken)) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}

