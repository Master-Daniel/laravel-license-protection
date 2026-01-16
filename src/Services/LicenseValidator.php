<?php

namespace LicenseProtection\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LicenseProtection\Config\LicenseConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class LicenseValidator
{
    protected $client;
    protected $cacheKey = 'license_validation';
    protected $cacheTtl;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
        $this->cacheTtl = LicenseConfig::getCacheTtl();
    }

    /**
     * Check if license is valid
     */
    public function isValid(): bool
    {
        try {
            // Get license data from database first to check expiration
            $licenseData = $this->getLicenseData();
            
            if (empty($licenseData)) {
                Log::warning('No license data found in database');
                $this->cacheResult(false);
                return false;
            }

            // Check if license has expired (even if cached)
            if (!$this->checkExpiration($licenseData)) {
                Log::warning('License has expired');
                // Clear cache if expired
                Cache::forget($this->cacheKey);
                $this->cacheResult(false);
                return false;
            }

            // Check cache - if we have a valid cached result and license hasn't expired, use it
            $cached = Cache::get($this->cacheKey);
            if ($cached === true) {
                // Valid license cached permanently - return immediately
                // No need to revalidate unless cache is manually cleared or license expires
                return true;
            }
            
            // If cached as false, we still need to revalidate (might have been fixed)
            // But we'll use a shorter cache for failures

            // Validate domain and IP binding
            if (!$this->validateDomainAndIp($licenseData)) {
                Log::warning('Domain or IP validation failed');
                $this->cacheResult(false);
                return false;
            }

            // Validate with remote server
            $isValid = $this->validateWithServer($licenseData);
            
            // Cache result (permanent for valid, short for invalid)
            $this->cacheResult($isValid);
            
            if (!$isValid) {
                Log::warning('License validation with server failed');
            } else {
                Log::info('License validated successfully and cached permanently');
            }
            
            return $isValid;
        } catch (Exception $e) {
            Log::error('License validation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // If we have a cached valid result, use it even if current validation fails
            // This prevents blocking the app due to temporary network issues
            $cached = Cache::get($this->cacheKey);
            if ($cached === true) {
                Log::warning('Using cached valid license due to validation error');
                return true;
            }
            
            return false;
        }
    }

    /**
     * Get license data from database
     */
    protected function getLicenseData(): ?array
    {
        try {
            // Check if licenses table exists
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
                'license_key' => decrypt($license->license_key),
                'domain' => $license->domain,
                'server_ip' => $license->server_ip,
                'id' => $license->id,
                'expires_at' => $expiresAt,
            ];
        } catch (Exception $e) {
            Log::error('Error retrieving license data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate that current domain and IP match the license
     */
    protected function validateDomainAndIp(array $licenseData): bool
    {
        $currentDomain = $this->getCurrentDomain();
        $currentIp = $this->getCurrentServerIp();

        // Domain must match exactly
        if ($licenseData['domain'] !== $currentDomain) {
            Log::warning("License domain mismatch. Expected: {$licenseData['domain']}, Got: {$currentDomain}");
            return false;
        }

        // IP must match exactly
        if ($licenseData['server_ip'] !== $currentIp) {
            Log::warning("License IP mismatch. Expected: {$licenseData['server_ip']}, Got: {$currentIp}");
            return false;
        }

        return true;
    }

    /**
     * Check if license has expired
     */
    protected function checkExpiration(array $licenseData): bool
    {
        if (empty($licenseData['expires_at'])) {
            // No expiration date set - license is valid (backward compatibility)
            return true;
        }

        $expiresAt = \Carbon\Carbon::parse($licenseData['expires_at']);
        $now = now();

        if ($now->greaterThan($expiresAt)) {
            Log::warning("License expired on {$expiresAt->format('Y-m-d H:i:s')}");
            return false;
        }

        // Log days remaining for monitoring
        $daysRemaining = $now->diffInDays($expiresAt, false);
        if ($daysRemaining <= 30) {
            Log::warning("License expires in {$daysRemaining} days on {$expiresAt->format('Y-m-d H:i:s')}");
        }

        return true;
    }

    /**
     * Get current domain
     */
    protected function getCurrentDomain(): string
    {
        if (app()->runningInConsole()) {
            // For console, try to get from config or use a default
            return config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : 'localhost';
        }

        $host = request()->getHost();
        
        // Remove www. prefix for consistency
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
        if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            $ip = $_SERVER['SERVER_ADDR'];
        }
        // Method 2: Try to get from network interfaces (for console commands)
        elseif (app()->runningInConsole()) {
            // Try to get IP from network interfaces
            if (function_exists('shell_exec')) {
                // Try Linux/Unix method
                $commands = [
                    "hostname -I | awk '{print $1}'",
                    "ip route get 8.8.8.8 | awk '{print $7}' | head -1",
                    "ifconfig | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | head -1",
                ];
                
                foreach ($commands as $cmd) {
                    $result = trim(shell_exec($cmd . ' 2>/dev/null'));
                    if (!empty($result) && filter_var($result, FILTER_VALIDATE_IP) && $result !== '127.0.0.1') {
                        $ip = $result;
                        break;
                    }
                }
            }
        }
        // Method 3: From gethostbyname
        if (empty($ip) && function_exists('gethostbyname')) {
            $hostname = gethostname();
            if ($hostname) {
                $resolved = gethostbyname($hostname);
                if ($resolved !== $hostname && $resolved !== '127.0.0.1') {
                    $ip = $resolved;
                }
            }
        }
        // Method 4: From request (web only)
        if (empty($ip) && !app()->runningInConsole() && request()->server('SERVER_ADDR')) {
            $ip = request()->server('SERVER_ADDR');
        }

        // Fallback to localhost only if we really can't find anything
        if (empty($ip) || $ip === ($hostname ?? '')) {
            $ip = '127.0.0.1';
        }

        return $ip;
    }

    /**
     * Validate license with remote server
     */
    protected function validateWithServer(array $licenseData): bool
    {
        try {
            $domain = $this->getCurrentDomain();
            $ip = $this->getCurrentServerIp();
            $serverInfo = $this->getServerFingerprint();

            $response = $this->client->post(LicenseConfig::getValidationServer() . '/validate', [
                'json' => [
                    'license_key' => $licenseData['license_key'],
                    'app_id' => LicenseConfig::getAppId(),
                    'domain' => $domain,
                    'ip' => $ip,
                    'server_fingerprint' => $serverInfo,
                    'timestamp' => time(),
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->generateAuthToken($licenseData['license_key']),
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['valid']) && $data['valid'] === true) {
                // Verify server confirms domain and IP match
                if (isset($data['domain']) && $data['domain'] !== $domain) {
                    Log::warning("Server returned different domain. Expected: {$domain}, Got: {$data['domain']}");
                    return false;
                }

                if (isset($data['ip']) && $data['ip'] !== $ip) {
                    Log::warning("Server returned different IP. Expected: {$ip}, Got: {$data['ip']}");
                    return false;
                }

                // Update last validation time
                $this->updateLastValidation();
                return true;
            }

            return false;
        } catch (RequestException $e) {
            Log::error('License server request failed: ' . $e->getMessage());
            
            // No grace period - if server is unreachable, license is invalid
            // This ensures license must be validated online
            return false;
        } catch (Exception $e) {
            Log::error('License validation exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate authentication token for API request
     */
    protected function generateAuthToken(string $licenseKey): string
    {
        $payload = LicenseConfig::getAppId() . '|' . $licenseKey . '|' . time();
        return hash_hmac('sha256', $payload, LicenseConfig::getSecretKey());
    }

    /**
     * Get server fingerprint for additional security
     */
    protected function getServerFingerprint(): string
    {
        $data = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        ];

        return hash('sha256', json_encode($data));
    }

    /**
     * Cache validation result
     * Valid licenses are cached permanently, invalid ones for a short time
     */
    protected function cacheResult(bool $isValid): void
    {
        if ($isValid) {
            // Cache valid licenses permanently (until manually cleared)
            // Use a very long TTL (effectively permanent)
            Cache::put($this->cacheKey, true, LicenseConfig::getCacheTtl());
            
            // Also store a timestamp to track when it was validated
            Cache::put($this->cacheKey . '_validated_at', now()->toIso8601String(), LicenseConfig::getCacheTtl());
        } else {
            // Cache invalid results for a short time to allow quick retry
            Cache::put($this->cacheKey, false, LicenseConfig::getInvalidCacheTtl());
        }
    }

    /**
     * Update last validation timestamp in database
     */
    protected function updateLastValidation(): void
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('licenses')) {
                DB::table('licenses')
                    ->where('is_active', true)
                    ->update([
                        'last_validated_at' => now(),
                        'validation_count' => DB::raw('validation_count + 1'),
                    ]);
            }
        } catch (Exception $e) {
            Log::error('Error updating last validation: ' . $e->getMessage());
        }
    }

    /**
     * Force revalidation (clears cache)
     */
    public function forceRevalidation(): bool
    {
        Cache::forget($this->cacheKey);
        return $this->isValid();
    }
}
