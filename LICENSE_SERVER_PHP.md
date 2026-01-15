# Simple PHP License Validation Server

A minimal PHP implementation for the license validation server (no framework required).

## File Structure

```
license-server/
├── index.php          # Main validation endpoint
├── config.php         # Configuration
├── database.php       # Database connection
├── .htaccess         # Apache rewrite rules (optional)
└── licenses.sql      # Database schema
```

## Database Schema

**`licenses.sql`**:

```sql
CREATE TABLE licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(255) UNIQUE NOT NULL,
    domain VARCHAR(255) NOT NULL,
    server_ip VARCHAR(45) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_validated_at TIMESTAMP NULL,
    validation_count INT UNSIGNED DEFAULT 0,
    INDEX idx_license_key (license_key),
    INDEX idx_domain_ip (domain, server_ip)
);
```

## Configuration

**`config.php`**:

```php
<?php

return [
    'app_id' => 'elite-codec-laravel-license-protection-v1',
    'secret_key' => getenv('LICENSE_SECRET_KEY') ?: 'your-secret-key-minimum-64-chars',
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: 'license_server',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',
];
```

## Database Connection

**`database.php`**:

```php
<?php

function getDbConnection() {
    $config = require __DIR__ . '/config.php';
    
    try {
        $pdo = new PDO(
            "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
            $config['db_user'],
            $config['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['valid' => false, 'message' => 'Database connection failed']);
        exit;
    }
}
```

## Main Validation Endpoint

**`index.php`**:

```php
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Validate required fields
$required = ['license_key', 'app_id', 'domain', 'ip', 'timestamp'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

$config = require __DIR__ . '/config.php';

// Verify app ID
if ($input['app_id'] !== $config['app_id']) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'message' => 'Invalid application ID']);
    exit;
}

// Verify authentication token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['valid' => false, 'message' => 'Missing or invalid authorization header']);
    exit;
}

$receivedToken = $matches[1];
$payload = $config['app_id'] . '|' . $input['license_key'] . '|' . $input['timestamp'];
$expectedToken = hash_hmac('sha256', $payload, $config['secret_key']);

if (!hash_equals($expectedToken, $receivedToken)) {
    http_response_code(401);
    echo json_encode(['valid' => false, 'message' => 'Invalid authentication token']);
    exit;
}

// Check timestamp (prevent replay attacks)
$timestamp = (int)$input['timestamp'];
$currentTime = time();
if (abs($currentTime - $timestamp) > 300) { // 5 minutes
    http_response_code(400);
    echo json_encode(['valid' => false, 'message' => 'Request timestamp is invalid']);
    exit;
}

// Get database connection
$pdo = getDbConnection();

// Find license
$stmt = $pdo->prepare("
    SELECT * FROM licenses 
    WHERE license_key = ? AND is_active = 1
");
$stmt->execute([$input['license_key']]);
$license = $stmt->fetch();

if (!$license) {
    http_response_code(404);
    echo json_encode(['valid' => false, 'message' => 'License key is invalid or inactive']);
    exit;
}

// Verify domain matches
if ($license['domain'] !== $input['domain']) {
    http_response_code(403);
    echo json_encode([
        'valid' => false, 
        'message' => 'License is bound to a different domain: ' . $license['domain']
    ]);
    exit;
}

// Verify IP matches
if ($license['server_ip'] !== $input['ip']) {
    http_response_code(403);
    echo json_encode([
        'valid' => false, 
        'message' => 'License is bound to a different IP address: ' . $license['server_ip']
    ]);
    exit;
}

// Update validation stats
$updateStmt = $pdo->prepare("
    UPDATE licenses 
    SET last_validated_at = NOW(), 
        validation_count = validation_count + 1 
    WHERE id = ?
");
$updateStmt->execute([$license['id']]);

// Return success
http_response_code(200);
echo json_encode([
    'valid' => true,
    'domain' => $license['domain'],
    'ip' => $license['server_ip'],
    'message' => 'License is valid'
]);
```

## Apache Configuration

**`.htaccess`** (if using Apache):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/validate$ index.php [L]
```

## Nginx Configuration

```nginx
location /api/validate {
    try_files $uri $uri/ /index.php?$query_string;
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
}
```

## Testing

```bash
# Generate token
TOKEN=$(php -r "echo hash_hmac('sha256', 'elite-codec-laravel-license-protection-v1|TEST-KEY|' . time(), 'your-secret-key');")
TIMESTAMP=$(date +%s)

# Test
curl -X POST https://your-server.com/api/validate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"license_key\": \"TEST-KEY\",
    \"app_id\": \"elite-codec-laravel-license-protection-v1\",
    \"domain\": \"example.com\",
    \"ip\": \"192.168.1.1\",
    \"server_fingerprint\": \"test\",
    \"timestamp\": $TIMESTAMP
  }"
```

## Security Notes

1. Use HTTPS only
2. Store secret key in environment variable
3. Implement rate limiting
4. Log all validation attempts
5. Monitor for suspicious activity

