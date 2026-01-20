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
        // Automatically publish config if it doesn't exist (silent installation)
        $configPath = config_path('license.php');
        if (!file_exists($configPath)) {
            $this->publishes([
                __DIR__.'/../config/license.php' => $configPath,
            ], 'license-config');
            
            // Copy config file automatically
            if (!file_exists($configPath)) {
                $configDir = dirname($configPath);
                if (!is_dir($configDir)) {
                    mkdir($configDir, 0755, true);
                }
                copy(__DIR__.'/../config/license.php', $configPath);
            }
        }

        // Publish migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register middleware
        $this->app['router']->middlewareGroup('license', [
            LicenseMiddleware::class,
        ]);

        // Register commands FIRST (before license check)
        if ($this->app->runningInConsole()) {
            $this->commands([
                ValidateLicenseCommand::class,
                SetLicenseKeyCommand::class,
            ]);
        }

        // Critical: Perform license check before application fully boots
        // But skip during package discovery and for allowed commands
        if (!$this->isPackageDiscovery() && !$this->isAllowedCommand()) {
            $this->performEarlyLicenseCheck();
        }
    }

    /**
     * Check if we're in package discovery mode
     */
    protected function isPackageDiscovery(): bool
    {
        return $this->app->runningInConsole() && 
               (php_sapi_name() === 'cli' && isset($_SERVER['argv']) && 
                in_array('package:discover', $_SERVER['argv'] ?? []));
    }

    /**
     * Check if current command is allowed to run without license
     */
    protected function isAllowedCommand(): bool
    {
        // If application encryption key is not set yet, skip ALL license checks for console
        // This allows running key:generate and initial setup commands safely.
        if (empty(config('app.key'))) {
            return true;
        }

        if (!$this->app->runningInConsole()) {
            return false;
        }

        $command = $_SERVER['argv'][1] ?? null;
        if (!$command) {
            return true; // No command specified, allow (e.g., just 'php artisan')
        }

        $allowedCommands = [
            'key:generate',
            'migrate', 
            'migrate:fresh', 
            'migrate:rollback',
            'migrate:status',
            'migrate:install',
            'license:validate', 
            'license:set',
            'list',
            'help',
            'about',
            'package:discover',
            'vendor:publish',
            'tinker',
            'config:clear',
            'config:cache',
        ];
        
        foreach ($allowedCommands as $allowed) {
            if ($command === $allowed || strpos($command, $allowed . ':') === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Perform early license check - before routes are loaded
     */
    protected function performEarlyLicenseCheck(): void
    {
        // Skip in console for migrations/commands
        if ($this->app->runningInConsole()) {
            // If APP_KEY is not set yet, skip license validation entirely for console
            if (empty(config('app.key'))) {
                return;
            }

            $command = $this->app->runningUnitTests() ? null : $_SERVER['argv'][1] ?? null;
            
            // Allow these commands to run without license validation
            $allowedCommands = [
                'key:generate',
                'migrate', 
                'migrate:fresh', 
                'migrate:rollback', 
                'migrate:status',
                'migrate:install',
                'license:validate', 
                'license:set',
                'list', // Allow artisan list
                'help', // Allow artisan help
                'about', // Allow artisan about
                'vendor:publish', // Allow vendor publish
                'tinker', // Allow tinker
                'config:clear', // Allow config clear
                'config:cache', // Allow config cache
            ];
            
            // Check if command starts with any allowed command (handles subcommands)
            $isAllowed = false;
            if ($command) {
                foreach ($allowedCommands as $allowed) {
                    if ($command === $allowed || strpos($command, $allowed . ':') === 0) {
                        $isAllowed = true;
                        break;
                    }
                }
            }
            
            // Only validate if command is not allowed
            if (!$isAllowed && $command) {
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

