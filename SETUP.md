# License Protection Package - Setup Guide

## Quick Setup

### 1. Run Composer Autoload

```bash
cd backend
composer dump-autoload
```

### 2. Copy Configuration (Optional)

The package works out of the box! Configuration is optional and only needed if you want to customize cache TTL:

```bash
php artisan vendor:publish --tag=license-config
```

Then optionally add to `.env`:

```env
LICENSE_CACHE_TTL=3600  # Optional: Cache TTL in seconds (default: 3600)
```

### 3. Run Migrations

```bash
php artisan migrate
```

This will create the `licenses` table with domain and IP binding support.

### 4. Set Your License Key

The license will be automatically bound to your domain and server IP:

```bash
php artisan license:set YOUR_LICENSE_KEY
```

The command will:
- Automatically detect your domain
- Automatically detect your server IP
- Show a confirmation with the binding information
- Store the license with domain/IP binding

**Important**: 
- The license is **permanently bound** to the domain and IP where it's set
- One license = One installation (domain + IP combination)
- Licenses do not expire but are restricted to the bound domain/IP

### 5. Validate License

```bash
php artisan license:validate
```

## What Changed?

### ✅ No Environment Configuration Needed

**Before**: Required multiple `.env` variables that could be spoofed:
- `LICENSE_VALIDATION_SERVER`
- `LICENSE_APP_ID`
- `LICENSE_SECRET_KEY`
- `LICENSE_GRACE_PERIOD_DAYS`
- `LICENSE_STRICT_MODE`

**Now**: All critical settings are embedded in the package code (`src/Config/LicenseConfig.php`)

### ✅ Domain and IP Binding

**Before**: License could be used on any domain/IP

**Now**: 
- License is automatically bound to domain when set
- License is automatically bound to server IP when set
- License validation checks domain and IP match
- One license = One installation

### ✅ Permanent Licenses

**Before**: Licenses could expire or have grace periods

**Now**:
- Licenses are permanent (no expiration)
- But restricted to one domain + IP combination
- Cannot be moved to different server without new license

### ✅ No Grace Period

**Before**: Could work offline for X days

**Now**: 
- Must validate online
- No offline operation
- Prevents spoofing attempts

## Verification

After setup, the application will:

1. ✅ Check license on every request (via middleware)
2. ✅ Check license on application boot (via service provider)
3. ✅ Verify domain matches stored domain
4. ✅ Verify IP matches stored IP
5. ✅ Validate with remote server
6. ✅ Block all API routes if license is invalid

## Testing

To test the protection:

1. Set an invalid license key: `php artisan license:set invalid-key`
2. Try accessing any API endpoint
3. You should receive a 403 error with "License validation failed"

To test domain/IP binding:

1. Set a license on one domain/IP
2. Try to use the same license on different domain/IP
3. License validation should fail

## License Server Requirements

Your license validation server must implement:

**Endpoint**: `POST /api/validate`

**Expected Request**:
```json
{
    "license_key": "xxx-xxx-xxx",
    "app_id": "elite-codec-laravel-license-protection-v1",
    "domain": "example.com",
    "ip": "192.168.1.1",
    "server_fingerprint": "hash",
    "timestamp": 1234567890
}
```

**Expected Response (Valid)**:
```json
{
    "valid": true,
    "domain": "example.com",
    "ip": "192.168.1.1"
}
```

**Expected Response (Invalid)**:
```json
{
    "valid": false,
    "message": "License key is invalid"
}
```

**Important**: The server should verify:
- License key is valid
- Domain matches the license
- IP matches the license
- License is active

## Troubleshooting

### "Class LicenseProtection\LicenseServiceProvider not found"

Run: `composer dump-autoload`

### "Table licenses does not exist"

Run: `php artisan migrate`

### "License validation failed" even with valid key

1. Check domain matches: `php artisan license:validate` will show details
2. Check IP matches: Verify server IP hasn't changed
3. Verify license server is accessible
4. Check `storage/logs/laravel.log` for errors
5. Run: `php artisan cache:clear`

### "Domain mismatch" or "IP mismatch"

- License is bound to specific domain/IP
- Cannot use same license on different domain/IP
- Contact support for license transfer or new license

### Application works without license

1. Verify service provider is registered in `bootstrap/providers.php`
2. Check middleware is registered in `bootstrap/app.php`
3. Verify `AppServiceProvider` has license check

## Security Notes

- **No configuration needed** - Settings embedded in package
- **Domain/IP binding** - One license per installation
- **Permanent licenses** - No expiration but restricted
- **Online validation** - Must validate with server
- **Monitor logs** - Check for suspicious activity
