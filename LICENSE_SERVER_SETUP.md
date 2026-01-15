# License Validation Server Setup Guide

This guide explains how to set up a license validation server that works with the Laravel License Protection package.

## Overview

Your license validation server needs to:
1. Store license keys in a database
2. Validate license keys against domain and IP
3. Return JSON responses matching the expected format
4. Verify HMAC authentication tokens

## Server Requirements

- PHP 8.2+ (or Node.js, Python, etc.)
- Database (MySQL, PostgreSQL, or SQLite)
- HTTPS enabled (required for security)
- API endpoint: `POST /api/validate` (or `/validate` depending on your setup)

## API Endpoint Specification

### Endpoint

**POST** `/api/validate` (or `/validate`)

### Request Headers

```
Authorization: Bearer {HMAC_SHA256_TOKEN}
Content-Type: application/json
```

### Request Body

```json
{
    "license_key": "xxx-xxx-xxx-xxx",
    "app_id": "elite-codec-laravel-license-protection-v1",
    "domain": "example.com",
    "ip": "192.168.1.1",
    "server_fingerprint": "hash_string",
    "timestamp": 1234567890
}
```

### Response (Valid License)

```json
{
    "valid": true,
    "domain": "example.com",
    "ip": "192.168.1.1",
    "message": "License is valid"
}
```

### Response (Invalid License)

```json
{
    "valid": false,
    "message": "License key is invalid or expired"
}
```

### Response (Domain/IP Mismatch)

```json
{
    "valid": false,
    "message": "License is bound to a different domain or IP address"
}
```

## Authentication Token Verification

The server must verify the HMAC-SHA256 token:

```php
// Expected token generation (matches package)
$payload = $appId . '|' . $licenseKey . '|' . $timestamp;
$expectedToken = hash_hmac('sha256', $payload, $secretKey);

// Verify token matches
if ($receivedToken !== $expectedToken) {
    return json_encode(['valid' => false, 'message' => 'Invalid authentication']);
}
```

**Important**: The `secretKey` in your server must match the `secretKey` in `LicenseConfig.php`.

## Database Schema

### Licenses Table

```sql
CREATE TABLE licenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(255) UNIQUE NOT NULL,
    domain VARCHAR(255) NOT NULL,
    server_ip VARCHAR(45) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_validated_at TIMESTAMP NULL,
    validation_count INT UNSIGNED DEFAULT 0,
    INDEX idx_license_key (license_key),
    INDEX idx_domain_ip (domain, server_ip),
    INDEX idx_active (is_active)
);
```

### License Generation

When creating a license for a customer:

```sql
INSERT INTO licenses (license_key, domain, server_ip, is_active)
VALUES ('CUSTOMER-LICENSE-KEY-12345', 'customer-domain.com', '192.168.1.100', TRUE);
```

## Implementation Examples

### Option 1: Laravel API (Recommended)

See `LICENSE_SERVER_LARAVEL.md` for complete Laravel implementation.

### Option 2: Simple PHP API

See `LICENSE_SERVER_PHP.md` for a simple PHP implementation.

### Option 3: Node.js/Express

See `LICENSE_SERVER_NODE.md` for Node.js implementation.

## Validation Logic

The server should:

1. **Verify Authentication Token**
   - Extract token from `Authorization: Bearer {token}` header
   - Generate expected token using HMAC-SHA256
   - Compare tokens

2. **Validate License Key**
   - Check if license key exists in database
   - Check if license is active
   - Verify domain matches stored domain
   - Verify IP matches stored IP

3. **Update Validation Stats**
   - Update `last_validated_at` timestamp
   - Increment `validation_count`

4. **Return Response**
   - Return JSON with `valid: true/false`
   - Include domain and IP in response for verification

## Security Best Practices

1. **Use HTTPS** - Never use HTTP for license validation
2. **Rate Limiting** - Implement rate limiting to prevent abuse
3. **Token Expiration** - Check timestamp is recent (within 5 minutes)
4. **IP Whitelisting** - Optionally whitelist known IPs
5. **Logging** - Log all validation attempts for monitoring
6. **Secret Key Security** - Store secret key securely (environment variable)

## Testing

### Test with cURL

```bash
# Generate token (PHP)
php -r "echo hash_hmac('sha256', 'elite-codec-laravel-license-protection-v1|TEST-KEY|' . time(), 'YOUR_SECRET_KEY');"

# Test validation
curl -X POST https://your-license-server.com/api/validate \
  -H "Authorization: Bearer GENERATED_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "TEST-KEY",
    "app_id": "elite-codec-laravel-license-protection-v1",
    "domain": "example.com",
    "ip": "192.168.1.1",
    "server_fingerprint": "test",
    "timestamp": '$(date +%s)'
  }'
```

## Deployment

1. **Deploy server** to a secure, HTTPS-enabled domain
2. **Update LicenseConfig.php** in package with your server URL
3. **Set secret key** in both server and package
4. **Create licenses** in database for customers
5. **Test validation** with real license keys

## Monitoring

Monitor:
- Validation success/failure rates
- Unusual validation patterns
- Failed authentication attempts
- Domain/IP mismatches

## Support

For issues or questions, refer to:
- Package README.md
- API documentation
- Server logs

