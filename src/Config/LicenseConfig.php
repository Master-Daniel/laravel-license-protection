<?php

namespace LicenseProtection\Config;

/**
 * Embedded License Configuration
 * 
 * This configuration is embedded in the package to prevent spoofing.
 * Update these values when deploying your license validation server.
 */
class LicenseConfig
{
    /**
     * License validation server URL
     * Update this to your actual license server URL
     */
    protected static $validationServer = 'https://license-server.elitebot.com.ng/api';

    /**
     * Application ID for this package
     * This identifies the package to the validation server
     */
    protected static $appId = 'elite-codec-laravel-license-protection-v1';

    /**
     * Secret key for API authentication
     * This should match the secret key on your validation server
     * IMPORTANT: Change this to a strong random string before distribution
     */
    protected static $secretKey = '985d58cef86847462a6dba6fa6977efa7d8240ffdc42e348aabf8e08fd45d06e';

    /**
     * Get validation server URL
     */
    public static function getValidationServer(): string
    {
        return self::$validationServer;
    }

    /**
     * Get application ID
     */
    public static function getAppId(): string
    {
        return self::$appId;
    }

    /**
     * Get secret key
     */
    public static function getSecretKey(): string
    {
        return self::$secretKey;
    }

    /**
     * Cache TTL for validation results (in seconds)
     */
    public static function getCacheTtl(): int
    {
        return 3600; // 1 hour
    }
}

