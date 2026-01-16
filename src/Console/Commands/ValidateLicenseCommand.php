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

        // Get license data to show expiration info
        $licenseData = $this->getLicenseData();

        if ($validator->forceRevalidation()) {
            $this->info('✓ License is valid!');
            
            if ($licenseData && !empty($licenseData['expires_at'])) {
                $expiresAt = \Carbon\Carbon::parse($licenseData['expires_at']);
                $daysRemaining = now()->diffInDays($expiresAt, false);
                
                $this->info('✓ License expires on: ' . $expiresAt->format('Y-m-d H:i:s'));
                
                if ($daysRemaining > 0) {
                    $this->info('✓ Days remaining: ' . $daysRemaining);
                } else {
                    $this->warn('⚠ License has expired!');
                }
            }
            
            return Command::SUCCESS;
        }

        $this->error('✗ License validation failed!');
        
        if ($licenseData && !empty($licenseData['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($licenseData['expires_at']);
            if (now()->greaterThan($expiresAt)) {
                $this->error('✗ License expired on: ' . $expiresAt->format('Y-m-d H:i:s'));
            }
        }
        
        $this->warn('Please ensure you have a valid license key configured.');
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

            return [
                'expires_at' => $license->expires_at,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}

