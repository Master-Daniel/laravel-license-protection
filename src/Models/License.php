<?php

namespace LicenseProtection\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $table = 'licenses';

    protected $fillable = [
        'license_key',
        'is_active',
        'last_validated_at',
        'validation_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_validated_at' => 'datetime',
        'validation_count' => 'integer',
    ];

    /**
     * Get decrypted license key
     */
    public function getDecryptedKey(): ?string
    {
        try {
            return decrypt($this->license_key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted license key
     */
    public function setLicenseKeyAttribute($value): void
    {
        $this->attributes['license_key'] = encrypt($value);
    }
}

