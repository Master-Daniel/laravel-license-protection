<?php

namespace LicenseProtection;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use LicenseProtection\Services\LicenseValidator;
use LicenseProtection\Http\Middleware\LicenseMiddleware;
use LicenseProtection\Console\Commands\ValidateLicenseCommand;
use LicenseProtection\Console\Commands\SetLicenseKeyCommand;

class LicenseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/license.php',
            'license'
        );

        // Register LicenseValidator as singleton
        $this->app->singleton(LicenseValidator::class, function ($app) {
            return new LicenseValidator();
        });

        // Critical: Check license on service provider boot
        $this->app->booted(function () {
            $this->performCriticalLicenseCheck();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/license.php' => config_path('license.php'),
        ], 'license-config');

        // Publish migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register middleware
        $this->app['router']->middlewareGroup('license', [
            LicenseMiddleware::class,
        ]);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ValidateLicenseCommand::class,
                SetLicenseKeyCommand::class,
            ]);
        }

        // Critical: Perform license check before application fully boots
        $this->performEarlyLicenseCheck();
    }

    /**
     * Perform early license check - before routes are loaded
     */
    protected function performEarlyLicenseCheck(): void
    {
        // Skip in console for migrations/commands
        if ($this->app->runningInConsole()) {
            $command = $this->app->runningUnitTests() ? null : $_SERVER['argv'][1] ?? null;
            $allowedCommands = ['migrate', 'migrate:fresh', 'migrate:rollback', 'license:validate', 'license:set'];
            
            if ($command && !in_array($command, $allowedCommands)) {
                $this->validateLicense();
            }
            return;
        }

        // For web requests, validate immediately
        $this->validateLicense();
    }

    /**
     * Perform critical license check after boot
     */
    protected function performCriticalLicenseCheck(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        // Additional validation point
        $validator = $this->app->make(LicenseValidator::class);
        
        if (!$validator->isValid()) {
            $this->blockApplication();
        }
    }

    /**
     * Validate license
     */
    protected function validateLicense(): void
    {
        $validator = $this->app->make(LicenseValidator::class);
        
        if (!$validator->isValid()) {
            $this->blockApplication();
        }
    }

    /**
     * Block application if license is invalid
     */
    protected function blockApplication(): void
    {
        // Log the attempt
        Log::warning('License validation failed - Application blocked');

        // Return error response
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

