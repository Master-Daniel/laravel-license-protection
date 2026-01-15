<?php

namespace LicenseProtection\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use LicenseProtection\Services\LicenseValidator;

class LicenseMiddleware
{
    protected $validator;

    public function __construct(LicenseValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation for health check endpoints
        if ($request->is('up') || $request->is('health')) {
            return $next($request);
        }

        // Validate license
        if (!$this->validator->isValid()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'License validation failed. Please contact support.',
                    'error' => 'INVALID_LICENSE',
                    'code' => 403
                ], 403);
            }

            abort(403, 'License validation failed. Please contact support.');
        }

        return $next($request);
    }
}

