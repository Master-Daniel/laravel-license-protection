<?php

namespace LicenseProtection\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SetLicenseKeyCommand extends Command
{
    protected $signature = 'license:set {key : The license key to set}';
    protected $description = 'Set the license key for the application (automatically binds to current domain and IP)';

    public function handle(): int
    {
        $licenseKey = $this->argument('key');

        if (empty($licenseKey)) {
            $this->error('License key cannot be empty!');
            return Command::FAILURE;
        }

        try {
            // Ensure licenses table exists
            if (!DB::getSchemaBuilder()->hasTable('licenses')) {
                $this->error('Licenses table does not exist. Please run migrations first.');
                return Command::FAILURE;
            }

            // Get current domain and IP
            $domain = $this->getCurrentDomain();
            $serverIp = $this->getCurrentServerIp();

            $this->info("Detected Domain: {$domain}");
            $this->info("Detected Server IP: {$serverIp}");
            $this->newLine();
            $this->warn('⚠️  This license will be permanently bound to:');
            $this->line("   Domain: {$domain}");
            $this->line("   Server IP: {$serverIp}");
            $this->newLine();

            if (!$this->confirm('Do you want to continue?', true)) {
                $this->info('License key not set.');
                return Command::SUCCESS;
            }

            // Clear license validation cache (force revalidation with new license)
            Cache::forget('license_validation');
            Cache::forget('license_validation_validated_at');

            // Deactivate all existing licenses
            DB::table('licenses')->update(['is_active' => false]);

            // Calculate expiration date (365 days from now)
            $expiresAt = now()->addDays(365);

            // Prepare license data
            $licenseData = [
                'license_key' => encrypt($licenseKey),
                'domain' => $domain,
                'server_ip' => $serverIp,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'last_validated_at' => null,
                'validation_count' => 0,
            ];

            // Only add expires_at if column exists (for backward compatibility)
            if (DB::getSchemaBuilder()->hasColumn('licenses', 'expires_at')) {
                $licenseData['expires_at'] = $expiresAt;
            }

            // Insert new license with domain and IP binding
            DB::table('licenses')->insert($licenseData);

            $this->newLine();
            $this->info('✓ License key set successfully!');
            $this->info('✓ License is permanently bound to this domain and IP.');
            
            // Only show expiration if column exists
            if (DB::getSchemaBuilder()->hasColumn('licenses', 'expires_at')) {
                $this->info('✓ License expires on: ' . $expiresAt->format('Y-m-d H:i:s') . ' (' . $expiresAt->diffForHumans() . ')');
            } else {
                $this->warn('⚠️  Note: Run "php artisan migrate" to enable 365-day expiration feature.');
            }
            
            $this->info('✓ Validation cache cleared - license will be validated automatically.');
            $this->newLine();
            $this->info('Run "php artisan license:validate" to verify the license.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to set license key: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get current domain
     */
    protected function getCurrentDomain(): string
    {
        $url = config('app.url');
        
        if ($url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host) {
                return preg_replace('/^www\./', '', $host);
            }
        }

        // Fallback to SERVER_NAME
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        return preg_replace('/^www\./', '', $host);
    }

    /**
     * Get current server IP address
     */
    protected function getCurrentServerIp(): string
    {
        // Try multiple methods to get server IP
        $ip = null;

        // Method 1: From $_SERVER
        if (isset($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        }
        // Method 2: From gethostbyname
        elseif (function_exists('gethostbyname')) {
            $hostname = gethostname();
            if ($hostname) {
                $ip = gethostbyname($hostname);
            }
        }

        // Fallback to localhost
        if (empty($ip) || $ip === ($hostname ?? '')) {
            $ip = '127.0.0.1';
        }

        return $ip;
    }
}
