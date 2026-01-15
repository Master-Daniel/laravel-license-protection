<?php

return [
    /*
    |--------------------------------------------------------------------------
    | License Protection Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file is optional. All critical settings are embedded
    | in the package code to prevent spoofing. You only need to publish this
    | config if you want to customize cache settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long to cache license validation results (in seconds).
    | Default is 1 hour (3600 seconds).
    |
    */
    'cache_ttl' => env('LICENSE_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | License Binding
    |--------------------------------------------------------------------------
    |
    | Licenses are permanently bound to:
    | - Domain: The domain where the application is installed
    | - Server IP: The IP address of the server
    |
    | This ensures one license = one installation.
    |
    */
];
