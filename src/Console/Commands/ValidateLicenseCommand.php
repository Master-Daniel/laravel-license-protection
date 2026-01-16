<?php

namespace LicenseProtection\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LicenseProtection\Services\LicenseValidator;

class ValidateLicenseCommand extends Command
{
    protected $signature = 'license:validate';
    protected $description = 'Validate the current license key';

    public function handle(LicenseValidator $validator): int
    {
        $this->info('Validating license...');
        $this->newLine();

        // Check if licenses table exists
        if (!DB::getSchemaBuilder()->hasTable('licenses')) {
            $this->error('✗ Licenses table does not exist!');
            $this->warn('Please run: php artisan migrate');
            return Command::FAILURE;
        }

        // Check if license exists
        $license = DB::table('licenses')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$license) {
            $this->error('✗ No active license found!');
            $this->warn('Please run: php artisan license:set YOUR_LICENSE_KEY');
            return Command::FAILURE;
        }

        // Get license data to show expiration info
        $licenseData = $this->getLicenseData();

        // Check expiration first
        if ($licenseData && !empty($licenseData['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($licenseData['expires_at']);
            if (now()->greaterThan($expiresAt)) {
                $this->error('✗ License expired on: ' . $expiresAt->format('Y-m-d H:i:s'));
                $this->warn('Please renew your license.');
                return Command::FAILURE;
            }
        }

        // Show license info
        $this->info('License Details:');
        $this->line('  Domain: ' . ($license->domain ?? 'N/A'));
        $this->line('  Server IP: ' . ($license->server_ip ?? 'N/A'));
        if ($licenseData && !empty($licenseData['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($licenseData['expires_at']);
            $daysRemaining = now()->diffInDays($expiresAt, false);
            $this->line('  Expires: ' . $expiresAt->format('Y-m-d H:i:s') . ' (' . $daysRemaining . ' days remaining)');
        }
        $this->newLine();

        if ($validator->forceRevalidation()) {
            $this->info('✓ License is valid!');
            
            if ($licenseData && !empty($licenseData['expires_at'])) {
                $expiresAt = \Carbon\Carbon::parse($licenseData['expires_at']);
                $daysRemaining = now()->diffInDays($expiresAt, false);
                
                if ($daysRemaining > 0) {
                    $this->info('✓ Days remaining: ' . $daysRemaining);
                }
            }
            
            return Command::SUCCESS;
        }

        $this->error('✗ License validation failed!');
        $this->newLine();
        $this->warn('Possible reasons:');
        $this->line('  - Domain or IP mismatch');
        $this->line('  - License server unreachable');
        $this->line('  - Invalid license key');
        $this->newLine();
        $this->warn('Check Laravel logs for detailed error messages:');
        $this->line('  tail -f storage/logs/laravel.log');
        
        return Command::FAILURE;
    }

    /**
     * Get license data from database
     */
    protected function getLicenseData(): ?array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('licenses')) {
                return null;
            }

            $license = DB::table('licenses')
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$license) {
                return null;
            }

            // Check if expires_at column exists (for backward compatibility)
            $expiresAt = null;
            if (DB::getSchemaBuilder()->hasColumn('licenses', 'expires_at')) {
                $expiresAt = $license->expires_at ?? null;
            }

            return [
                'expires_at' => $expiresAt,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}

