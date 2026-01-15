# Laravel License Protection Package

A bulletproof license protection package for Laravel applications that ensures your application cannot run without a valid license key.

## ⚠️ Important: Publishing Required

**This package must be published to a Git repository and installed via Composer to be secure.**

Currently, the package is in `license-protection/` directory. To make it secure:

1. **Publish to Git repository** (see `PUBLISHING.md` or `MIGRATION_GUIDE.md`)
2. **Install via Composer** - Package will go to `vendor/` directory
3. **Remove local package** - After successful installation

**Quick Start**: See `PACKAGE_PUBLISHING_INSTRUCTIONS.md` in the backend root directory.

**Note**: Documentation files (except README.md) are excluded from the Composer package installation using `.gitattributes` to keep the installed package clean. Only essential files (`src/`, `config/`, `database/`, `composer.json`, `README.md`) are installed.

## Features

- **Embedded Configuration**: Server settings embedded in package (prevents spoofing)
- **No Environment Configuration**: Works out of the box - no .env setup needed
- **Domain Binding**: License permanently bound to installation domain
- **IP Binding**: License permanently bound to server IP address
- **Permanent Licenses**: No expiration, but restricted to one installation (domain + IP)
- **Remote License Validation**: Validates license keys against your remote server
- **Multiple Protection Layers**: License checks at service provider, middleware, and controller levels
- **Caching**: Efficient caching to minimize server load (1 hour cache)
- **Server Fingerprinting**: Additional security through server fingerprinting
- **Encrypted Storage**: License keys are encrypted in the database
- **Comprehensive Logging**: All validation attempts are logged
- **One License = One Installation**: Domain + IP combination ensures single use

## Installation (After Publishing)

### 1. Add Repository to composer.json

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/yourusername/laravel-license-protection.git"
        }
    ],
    "require": {
        "elite-codec/laravel-license-protection": "^1.0.0"
    }
}
```

### 2. Get Repository Access

**Contact the package developer** to get access to the private repository. You must be added as a collaborator or granted access before you can install.

### 3. Authenticate with Private Repository

**Each user creates their own token** from their own GitHub/GitLab account:

**For GitHub (Fine-Grained Token - Recommended):**
```bash
# 1. Create fine-grained token at: https://github.com/settings/tokens?type=beta
#    - Select "Only select repositories" and choose license-protection repo only
#    - Permissions: Contents (Read-only), Metadata (Read-only)
# 2. Configure Composer:
composer config --global github-oauth.github.com YOUR_FINE_GRAINED_TOKEN
```

**For GitHub (Classic Token - Less Secure):**
```bash
# ⚠️ Warning: Classic tokens with 'repo' scope access ALL your repositories
# 1. Create token at: https://github.com/settings/tokens (scope: repo)
# 2. Configure Composer:
composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN
```

**For GitLab:**
```bash
# 1. Create token at: https://gitlab.com/-/user_settings/personal_access_tokens (scope: read_repository)
# 2. Configure Composer:
composer config --global gitlab-token.gitlab.com YOUR_GITLAB_TOKEN
```

**For Bitbucket:**
```bash
# 1. Create app password at: https://bitbucket.org/account/settings/app-passwords/
# 2. Configure Composer:
composer config --global bitbucket-oauth.bitbucket.org USERNAME APP_PASSWORD
```

See `AUTHENTICATION_GUIDE.md` for detailed instructions and alternative methods.

### 4. Install via Composer

```bash
composer require elite-codec/laravel-license-protection
```

### 5. Run Migrations

**Note**: The configuration file is automatically published when the package boots. No manual step needed.

```bash
php artisan migrate
```

### 6. Configure License Server (Package Developer Only)

**Important**: Before distributing the package, update the embedded configuration in:
`src/Config/LicenseConfig.php`

- Set `$validationServer` to your license server URL
- Set `$secretKey` to a strong random string (minimum 64 characters)
- The `$appId` can remain as-is or be customized

**No configuration needed in the Laravel application** - all settings are embedded in the package to prevent spoofing.

### 7. Set License Key

The license key will be automatically bound to the current domain and server IP:

```bash
php artisan license:set YOUR_LICENSE_KEY
```

The command will:
- Automatically detect the current domain
- Automatically detect the server IP address
- Permanently bind the license to these values
- Show a confirmation before proceeding

**Important**: 
- The license is **permanently bound** to the domain and IP where it's set
- One license = One installation (domain + IP combination)
- Licenses do not expire but are restricted to the bound domain/IP

### 8. Validate License

```bash
php artisan license:validate
```

## How It Works

### Protection Layers

1. **Service Provider Level**: License is checked when the application boots
2. **Middleware Level**: All API routes are protected by `LicenseMiddleware`
3. **AppServiceProvider**: Additional check in the application service provider
4. **Controller Level**: Use `RequiresLicense` trait for additional protection

### License Validation Flow

1. License key is retrieved from database (encrypted)
2. **Domain and IP validation**: Current domain and IP are checked against stored values
3. Request is sent to validation server with:
   - License key
   - Domain (must match stored domain)
   - IP address (must match stored IP)
   - Server fingerprint
4. Server validates and confirms domain/IP binding
5. Result is cached for performance (1 hour)
6. Application is blocked if validation fails

**Key Features**:
- ✅ **No configuration needed** - All settings embedded in package
- ✅ **Domain binding** - License tied to specific domain
- ✅ **IP binding** - License tied to specific server IP
- ✅ **Permanent licenses** - No expiration, but restricted to one installation
- ✅ **No grace period** - Must validate online (prevents offline spoofing)

### Security Features

- **Embedded Configuration**: Server URL and secret key are embedded in package code (prevents spoofing)
- **Encryption**: License keys are encrypted using Laravel's encryption
- **Domain Binding**: License is permanently tied to specific domain
- **IP Binding**: License is permanently tied to specific server IP
- **Server Fingerprinting**: Unique server fingerprint prevents key sharing
- **Multiple Checkpoints**: License is validated at multiple points
- **No Grace Period**: Must validate online (prevents offline spoofing)
- **One License = One Installation**: Domain + IP combination ensures single use

## Usage

### Basic Usage

The package automatically protects all API routes. No additional code needed.

### Advanced Usage

#### Add License Check to Specific Controllers

```php
use LicenseProtection\Traits\RequiresLicense;

class OrderController extends Controller
{
    use RequiresLicense;
    
    // Your controller methods
}
```

#### Manual License Check

```php
use LicenseProtection\Helpers\LicenseHelper;

if (!LicenseHelper::check()) {
    // Handle invalid license
}

// Or assert (will abort if invalid)
LicenseHelper::assert();
```

## License Server Setup

**You need to set up a license validation server** to validate license keys. See:

- `LICENSE_SERVER_SETUP.md` - Complete setup guide
- `LICENSE_SERVER_LARAVEL.md` - Laravel implementation (recommended)
- `LICENSE_SERVER_PHP.md` - Simple PHP implementation
- `LICENSE_SERVER_NODE.md` - Node.js/Express implementation
- `QUICK_SERVER_SETUP.md` - Quick start guide

## License Server API

Your license validation server should implement the following endpoint:

**POST** `/api/validate`

**Headers:**
```
Authorization: Bearer {HMAC_SHA256_TOKEN}
Content-Type: application/json
```

**Request Body:**
```json
{
    "license_key": "xxx-xxx-xxx",
    "app_id": "your-app-id",
    "domain": "example.com",
    "ip": "192.168.1.1",
    "server_fingerprint": "hash",
    "timestamp": 1234567890
}
```

**Response (Valid):**
```json
{
    "valid": true,
    "expires_at": "2024-12-31 23:59:59",
    "message": "License is valid"
}
```

**Response (Invalid):**
```json
{
    "valid": false,
    "message": "License key is invalid or expired"
}
```

### Generating Authorization Token

The authorization token is generated using HMAC-SHA256:

```php
$payload = $appId . '|' . $licenseKey . '|' . time();
$token = hash_hmac('sha256', $payload, $secretKey);
```

## Configuration

### Package Configuration (For Package Developer)

Before distributing the package, update `src/Config/LicenseConfig.php`:

```php
protected static $validationServer = 'https://your-license-server.com/api';
protected static $secretKey = 'YOUR_STRONG_RANDOM_SECRET_KEY_MIN_64_CHARS';
```

### Application Configuration (Optional)

Only cache TTL can be configured in the application (optional):

```env
LICENSE_CACHE_TTL=3600  # 1 hour in seconds (optional, default: 3600)
```

**All other settings are embedded in the package** to prevent spoofing.

### License Binding

- **Domain**: Automatically detected and stored when license is set
- **Server IP**: Automatically detected and stored when license is set
- **Permanent**: License does not expire but is restricted to bound domain/IP
- **One Installation**: Each license works only on one domain + IP combination

## Troubleshooting

### License validation fails

1. Check license key is set: `php artisan license:validate`
2. Verify environment variables are correct
3. Check validation server is accessible
4. Review logs: `storage/logs/laravel.log`

### Application blocked even with valid license

1. Clear cache: `php artisan cache:clear`
2. Force revalidation: `php artisan license:validate`
3. Check database connection
4. Verify migrations ran successfully

### Package not found errors

1. Run `composer dump-autoload`
2. Verify package is in `vendor/elite-codec/laravel-license-protection/`
3. Check repository URL in `composer.json`

## Security Considerations

### For Package Developer

1. **Update LicenseConfig.php** before distribution:
   - Set validation server URL
   - Generate strong secret key (minimum 64 characters)
   - Use HTTPS for validation server
2. **Obfuscate package code** (optional but recommended)
3. **Monitor validation server** for suspicious activity
4. **Rotate secret keys** periodically (requires package update)

### For Application Users

1. **No configuration needed** - Package is ready to use
2. **License is automatically bound** to domain and IP
3. **Cannot be moved** to different domain/IP without new license
4. **One license per installation** - Domain + IP combination

## Making It Harder to Bypass

This package implements multiple protection layers:

1. **Service Provider Checks**: License validated before app fully boots
2. **Middleware Protection**: All routes protected
3. **Database Dependency**: License must exist in database
4. **Remote Validation**: Cannot work offline indefinitely
5. **Server Fingerprinting**: Prevents key sharing
6. **Encrypted Storage**: Keys encrypted at rest
7. **Multiple Checkpoints**: Hard to bypass all checks
8. **Composer Package**: Installed in `vendor/` (harder to remove)

### Additional Hardening (Optional)

For even stronger protection, consider:

1. **Code Obfuscation**: Obfuscate the package code
2. **Binary Extension**: Move critical checks to PHP extension
3. **File Integrity Checks**: Verify package files haven't been modified
4. **Time-based Validation**: More frequent validation checks
5. **Tamper Detection**: Detect if code has been modified

## Publishing the Package

**IMPORTANT**: This package must be published to a Git repository and installed via Composer.

See:
- `PUBLISHING.md` - Publishing options and methods
- `MIGRATION_GUIDE.md` - Step-by-step migration guide
- `../PACKAGE_PUBLISHING_INSTRUCTIONS.md` - Quick start guide

## License

This package is proprietary software. Unauthorized use is prohibited.

## Support

For support, contact: engrdanywiss@gmail.com
