<?php

namespace LicenseProtection\Helpers;

use Illuminate\Support\Facades\Log;
use LicenseProtection\Services\LicenseValidator;

class LicenseHelper
{
    /**
     * Check if license is valid
     */
    public static function check(): bool
    {
        try {
            $validator = app(LicenseValidator::class);
            return $validator->isValid();
        } catch (\Exception $e) {
            Log::error('License check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assert license is valid or abort
     */
    public static function assert(): void
    {
        if (!self::check()) {
            if (request()->expectsJson() || request()->is('api/*')) {
                abort(403, json_encode([
                    'success' => false,
                    'message' => 'License validation failed. Please contact support.',
                    'error' => 'INVALID_LICENSE',
                    'code' => 403
                ]), ['Content-Type' => 'application/json']);
            } else {
                abort(403, 'License validation failed. Please contact support.');
            }
        }
    }
}

