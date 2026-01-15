# Laravel License Validation Server Implementation

Complete Laravel implementation for the license validation server.

## Setup

### 1. Create New Laravel Project

```bash
composer create-project laravel/laravel license-server
cd license-server
```

### 2. Create Migration

```bash
php artisan make:migration create_licenses_table
```

**Migration File** (`database/migrations/xxxx_create_licenses_table.php`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->string('domain');
            $table->string('server_ip', 45);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_validated_at')->nullable();
            $table->unsignedBigInteger('validation_count')->default(0);
            $table->timestamps();

            $table->index(['domain', 'server_ip']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
```

### 3. Create Model

```bash
php artisan make:model License
```

**Model File** (`app/Models/License.php`):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'domain',
        'server_ip',
        'is_active',
        'last_validated_at',
        'validation_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_validated_at' => 'datetime',
        'validation_count' => 'integer',
    ];
}
```

### 4. Create Controller

```bash
php artisan make:controller Api/LicenseValidationController
```

**Controller File** (`app/Http/Controllers/Api/LicenseValidationController.php`):

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LicenseValidationController extends Controller
{
    protected $appId = 'elite-codec-laravel-license-protection-v1';
    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = env('LICENSE_SECRET_KEY');
    }

    /**
     * Validate license key
     */
    public function validate(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'app_id' => 'required|string',
            'domain' => 'required|string',
            'ip' => 'required|ip',
            'server_fingerprint' => 'nullable|string',
            'timestamp' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid request parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        // Verify authentication token
        if (!$this->verifyAuthToken($request)) {
            Log::warning('Invalid authentication token', [
                'ip' => $request->ip(),
                'license_key' => $request->license_key
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Invalid authentication token'
            ], 401);
        }

        // Verify app ID
        if ($request->app_id !== $this->appId) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid application ID'
            ], 400);
        }

        // Check timestamp (prevent replay attacks)
        $timestamp = $request->timestamp;
        $currentTime = time();
        if (abs($currentTime - $timestamp) > 300) { // 5 minutes
            return response()->json([
                'valid' => false,
                'message' => 'Request timestamp is too old or too far in the future'
            ], 400);
        }

        // Find license
        $license = License::where('license_key', $request->license_key)
            ->where('is_active', true)
            ->first();

        if (!$license) {
            Log::warning('License key not found or inactive', [
                'license_key' => $request->license_key,
                'domain' => $request->domain,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'License key is invalid or inactive'
            ], 404);
        }

        // Verify domain matches
        if ($license->domain !== $request->domain) {
            Log::warning('Domain mismatch', [
                'license_key' => $request->license_key,
                'expected_domain' => $license->domain,
                'received_domain' => $request->domain
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'License is bound to a different domain: ' . $license->domain
            ], 403);
        }

        // Verify IP matches
        if ($license->server_ip !== $request->ip) {
            Log::warning('IP mismatch', [
                'license_key' => $request->license_key,
                'expected_ip' => $license->server_ip,
                'received_ip' => $request->ip
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'License is bound to a different IP address: ' . $license->server_ip
            ], 403);
        }

        // Update validation stats
        $license->update([
            'last_validated_at' => now(),
            'validation_count' => $license->validation_count + 1,
        ]);

        // Return success
        return response()->json([
            'valid' => true,
            'domain' => $license->domain,
            'ip' => $license->server_ip,
            'message' => 'License is valid'
        ], 200);
    }

    /**
     * Verify HMAC authentication token
     */
    protected function verifyAuthToken(Request $request): bool
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }

        $receivedToken = substr($authHeader, 7); // Remove "Bearer "
        $licenseKey = $request->license_key;
        $timestamp = $request->timestamp;

        // Generate expected token
        $payload = $this->appId . '|' . $licenseKey . '|' . $timestamp;
        $expectedToken = hash_hmac('sha256', $payload, $this->secretKey);

        // Compare tokens (use hash_equals to prevent timing attacks)
        return hash_equals($expectedToken, $receivedToken);
    }
}
```

### 5. Add Route

**Routes File** (`routes/api.php`):

```php
<?php

use App\Http\Controllers\Api\LicenseValidationController;
use Illuminate\Support\Facades\Route;

Route::post('/validate', [LicenseValidationController::class, 'validate']);
// Or if you want it at /api/validate:
// Route::post('/api/validate', [LicenseValidationController::class, 'validate']);
```

### 6. Configure Environment

**`.env` file**:

```env
LICENSE_SECRET_KEY=your-strong-secret-key-minimum-64-characters-long-change-this-to-something-secure
```

**Important**: This must match the `secretKey` in `LicenseConfig.php` of the package.

### 7. Run Migrations

```bash
php artisan migrate
```

### 8. Create License (Seeder or Manual)

**Seeder** (`database/seeders/LicenseSeeder.php`):

```php
<?php

namespace Database\Seeders;

use App\Models\License;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    public function run(): void
    {
        License::create([
            'license_key' => 'CUSTOMER-LICENSE-KEY-12345',
            'domain' => 'customer-domain.com',
            'server_ip' => '192.168.1.100',
            'is_active' => true,
        ]);
    }
}
```

Run seeder:
```bash
php artisan db:seed --class=LicenseSeeder
```

## Testing

### Test with Artisan Tinker

```bash
php artisan tinker
```

```php
$license = License::create([
    'license_key' => 'TEST-KEY-12345',
    'domain' => 'localhost',
    'server_ip' => '127.0.0.1',
    'is_active' => true,
]);
```

### Test with cURL

```bash
# Generate token
TOKEN=$(php -r "echo hash_hmac('sha256', 'elite-codec-laravel-license-protection-v1|TEST-KEY-12345|' . time(), 'your-secret-key');")
TIMESTAMP=$(date +%s)

# Test validation
curl -X POST http://localhost:8000/api/validate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"license_key\": \"TEST-KEY-12345\",
    \"app_id\": \"elite-codec-laravel-license-protection-v1\",
    \"domain\": \"localhost\",
    \"ip\": \"127.0.0.1\",
    \"server_fingerprint\": \"test\",
    \"timestamp\": $TIMESTAMP
  }"
```

## Security Enhancements

### Add Rate Limiting

**`app/Http/Kernel.php`** or **`bootstrap/app.php`**:

```php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/validate', [LicenseValidationController::class, 'validate']);
});
```

### Add CORS (if needed)

```php
Route::middleware(['cors'])->group(function () {
    Route::post('/validate', [LicenseValidationController::class, 'validate']);
});
```

### Add Request Logging

Add to controller:

```php
Log::info('License validation request', [
    'license_key' => $request->license_key,
    'domain' => $request->domain,
    'ip' => $request->ip(),
    'timestamp' => $request->timestamp
]);
```

## Deployment

1. Deploy to server with HTTPS
2. Set `LICENSE_SECRET_KEY` in production `.env`
3. Update `LicenseConfig.php` in package with production URL
4. Create licenses for customers
5. Monitor logs for suspicious activity

## Admin Panel (Optional)

Create an admin panel to:
- View all licenses
- Create new licenses
- Deactivate licenses
- View validation statistics
- Monitor validation attempts

