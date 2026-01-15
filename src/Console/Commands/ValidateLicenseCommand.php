<?php

namespace LicenseProtection\Console\Commands;

use Illuminate\Console\Command;
use LicenseProtection\Services\LicenseValidator;

class ValidateLicenseCommand extends Command
{
    protected $signature = 'license:validate';
    protected $description = 'Validate the current license key';

    public function handle(LicenseValidator $validator): int
    {
        $this->info('Validating license...');

        if ($validator->forceRevalidation()) {
            $this->info('✓ License is valid!');
            return Command::SUCCESS;
        }

        $this->error('✗ License validation failed!');
        $this->warn('Please ensure you have a valid license key configured.');
        return Command::FAILURE;
    }
}

