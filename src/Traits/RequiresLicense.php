<?php

namespace LicenseProtection\Traits;

use LicenseProtection\Helpers\LicenseHelper;

trait RequiresLicense
{
    /**
     * Boot the trait - add license check middleware
     */
    public function __construct()
    {
        parent::__construct();
        
        // Validate license for all methods in this controller
        $this->middleware(function ($request, $next) {
            LicenseHelper::assert();
            return $next($request);
        });
    }
}

