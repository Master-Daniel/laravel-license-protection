<?php

namespace LicenseProtection\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SetLicenseKeyCommand extends Command
{
    protected $signature = 'license:set {key : The license key to set} {--ip= : Manually specify the server IP address}';
    protected $description = 'Set the license key for the application (automatically binds to current domain and IP)';

    public function handle(): int
    {
        $licenseKey = $this->argument('key');

        if (empty($licenseKey)) {
            $this->error('License key cannot be empty!');
            return Command::FAILURE;
        }

        try {
            // Ensure application encryption key is set before trying to encrypt the license key
            if (empty(config('app.key'))) {
                $this->error('No application encryption key has been specified.');
                $this->error('Please run "php artisan key:generate" first, then run this command again.');
                return Command::FAILURE;
            }

            // Ensure licenses table exists
            if (!DB::getSchemaBuilder()->hasTable('licenses')) {
                $this->error('Licenses table does not exist. Please run migrations first.');
                return Command::FAILURE;
            }

            // Get current domain and IP
            $domain = $this->getCurrentDomain();
            
            // Allow manual IP override via --ip option
            $serverIp = $this->option('ip');
            if (!$serverIp) {
                $serverIp = $this->getCurrentServerIp();
            } else {
                // Validate manually provided IP
                if (!filter_var($serverIp, FILTER_VALIDATE_IP)) {
                    $this->error('Invalid IP address provided. Please provide a valid IP address.');
                    return Command::FAILURE;
                }
            }

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

        // Method 1: Try to get IP from domain name (most reliable for production)
        $domain = $this->getCurrentDomain();
        if ($domain && $domain !== 'localhost') {
            $resolvedIp = gethostbyname($domain);
            if ($resolvedIp && $resolvedIp !== $domain && filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $resolvedIp;
            }
        }

        // Method 2: Try shell command to get primary IP (Linux/Unix)
        if (empty($ip) && function_exists('shell_exec')) {
            // Try hostname -I (Linux)
            $shellIp = trim(shell_exec('hostname -I 2>/dev/null'));
            if ($shellIp && filter_var($shellIp, FILTER_VALIDATE_IP)) {
                // Get first IP if multiple
                $ips = explode(' ', $shellIp);
                foreach ($ips as $candidateIp) {
                    if (filter_var($candidateIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        $ip = $candidateIp;
                        break;
                    }
                }
                // If no public IP found, use first private IP
                if (empty($ip) && !empty($ips[0])) {
                    $ip = trim($ips[0]);
                }
            }
        }

        // Method 3: Try ip command (Linux)
        if (empty($ip) && function_exists('shell_exec')) {
            $ipCommand = trim(shell_exec("ip -4 addr show | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | grep -v '127.0.0.1' | head -1 2>/dev/null"));
            if ($ipCommand && filter_var($ipCommand, FILTER_VALIDATE_IP)) {
                $ip = $ipCommand;
            }
        }

        // Method 4: From $_SERVER (may be localhost in CLI)
        if (empty($ip) && isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            $ip = $_SERVER['SERVER_ADDR'];
        }

        // Method 5: From gethostbyname with hostname
        if (empty($ip) && function_exists('gethostbyname')) {
            $hostname = gethostname();
            if ($hostname) {
                $hostnameIp = gethostbyname($hostname);
                if ($hostnameIp && $hostnameIp !== $hostname && $hostnameIp !== '127.0.0.1') {
                    $ip = $hostnameIp;
                }
            }
        }

        // Method 6: Try external service as last resort (only if domain is not localhost)
        if (empty($ip) && $domain && $domain !== 'localhost') {
            try {
                $externalIp = @file_get_contents('https://api.ipify.org?format=text');
                if ($externalIp && filter_var(trim($externalIp), FILTER_VALIDATE_IP)) {
                    $ip = trim($externalIp);
                }
            } catch (\Exception $e) {
                // Ignore external service failures
            }
        }

        // Final fallback to localhost (but warn user)
        if (empty($ip) || $ip === '127.0.0.1') {
            $this->warn('⚠️  Could not detect server IP automatically. Using 127.0.0.1.');
            $this->warn('   You may want to manually specify the server IP.');
            $ip = '127.0.0.1';
        }

        return $ip;
    }
}
