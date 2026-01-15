# Node.js/Express License Validation Server

A Node.js implementation for the license validation server.

## Setup

### 1. Initialize Project

```bash
npm init -y
npm install express mysql2 crypto dotenv
```

### 2. Project Structure

```
license-server/
├── index.js
├── config.js
├── database.js
├── .env
└── package.json
```

### 3. Configuration

**`.env`**:

```
LICENSE_SECRET_KEY=your-secret-key-minimum-64-chars
DB_HOST=localhost
DB_NAME=license_server
DB_USER=root
DB_PASS=
PORT=3000
```

**`config.js`**:

```javascript
require('dotenv').config();

module.exports = {
    appId: 'elite-codec-laravel-license-protection-v1',
    secretKey: process.env.LICENSE_SECRET_KEY,
    port: process.env.PORT || 3000,
};
```

### 4. Database Connection

**`database.js`**:

```javascript
const mysql = require('mysql2/promise');
require('dotenv').config();

const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'license_server',
    waitForConnections: true,
    connectionLimit: 10,
});

module.exports = pool;
```

### 5. Main Server

**`index.js`**:

```javascript
const express = require('express');
const crypto = require('crypto');
const config = require('./config');
const db = require('./database');

const app = express();
app.use(express.json());

// Validation endpoint
app.post('/api/validate', async (req, res) => {
    try {
        const { license_key, app_id, domain, ip, server_fingerprint, timestamp } = req.body;

        // Validate required fields
        if (!license_key || !app_id || !domain || !ip || !timestamp) {
            return res.status(400).json({
                valid: false,
                message: 'Missing required fields'
            });
        }

        // Verify app ID
        if (app_id !== config.appId) {
            return res.status(400).json({
                valid: false,
                message: 'Invalid application ID'
            });
        }

        // Verify authentication token
        const authHeader = req.headers.authorization;
        if (!authHeader || !authHeader.startsWith('Bearer ')) {
            return res.status(401).json({
                valid: false,
                message: 'Missing or invalid authorization header'
            });
        }

        const receivedToken = authHeader.substring(7);
        const payload = `${config.appId}|${license_key}|${timestamp}`;
        const expectedToken = crypto
            .createHmac('sha256', config.secretKey)
            .update(payload)
            .digest('hex');

        if (receivedToken !== expectedToken) {
            return res.status(401).json({
                valid: false,
                message: 'Invalid authentication token'
            });
        }

        // Check timestamp (prevent replay attacks)
        const currentTime = Math.floor(Date.now() / 1000);
        if (Math.abs(currentTime - timestamp) > 300) { // 5 minutes
            return res.status(400).json({
                valid: false,
                message: 'Request timestamp is invalid'
            });
        }

        // Find license
        const [licenses] = await db.execute(
            'SELECT * FROM licenses WHERE license_key = ? AND is_active = 1',
            [license_key]
        );

        if (licenses.length === 0) {
            return res.status(404).json({
                valid: false,
                message: 'License key is invalid or inactive'
            });
        }

        const license = licenses[0];

        // Verify domain matches
        if (license.domain !== domain) {
            return res.status(403).json({
                valid: false,
                message: `License is bound to a different domain: ${license.domain}`
            });
        }

        // Verify IP matches
        if (license.server_ip !== ip) {
            return res.status(403).json({
                valid: false,
                message: `License is bound to a different IP address: ${license.server_ip}`
            });
        }

        // Update validation stats
        await db.execute(
            'UPDATE licenses SET last_validated_at = NOW(), validation_count = validation_count + 1 WHERE id = ?',
            [license.id]
        );

        // Return success
        return res.json({
            valid: true,
            domain: license.domain,
            ip: license.server_ip,
            message: 'License is valid'
        });

    } catch (error) {
        console.error('Validation error:', error);
        return res.status(500).json({
            valid: false,
            message: 'Internal server error'
        });
    }
});

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok' });
});

app.listen(config.port, () => {
    console.log(`License server running on port ${config.port}`);
});
```

### 6. Run Server

```bash
node index.js
```

Server runs at: `http://localhost:3000/api/validate`

## Testing

```bash
# Generate token (Node.js)
node -e "const crypto = require('crypto'); const timestamp = Math.floor(Date.now() / 1000); console.log(crypto.createHmac('sha256', 'your-secret-key').update('elite-codec-laravel-license-protection-v1|TEST-KEY|' + timestamp).digest('hex'));"

# Test
curl -X POST http://localhost:3000/api/validate \
  -H "Authorization: Bearer GENERATED_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "TEST-KEY",
    "app_id": "elite-codec-laravel-license-protection-v1",
    "domain": "localhost",
    "ip": "127.0.0.1",
    "server_fingerprint": "test",
    "timestamp": '$(date +%s)'
  }'
```

