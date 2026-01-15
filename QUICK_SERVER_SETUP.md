# Quick License Server Setup

Fastest way to get a license validation server running.

## Option 1: Laravel Server (Recommended - 10 minutes)

### Step 1: Create Laravel Project

```bash
composer create-project laravel/laravel license-server
cd license-server
```

### Step 2: Copy Implementation

Copy the files from `LICENSE_SERVER_LARAVEL.md`:
- Migration
- Model
- Controller
- Route

### Step 3: Configure

```bash
# .env
LICENSE_SECRET_KEY=your-secret-key-must-match-package-config
```

### Step 4: Run

```bash
php artisan migrate
php artisan serve
```

Server runs at: `http://localhost:8000/api/validate`

## Option 2: Simple PHP (5 minutes)

### Step 1: Create Files

Create `license-server/` directory with:
- `index.php` (from LICENSE_SERVER_PHP.md)
- `config.php`
- `database.php`

### Step 2: Setup Database

```sql
CREATE DATABASE license_server;
-- Run licenses.sql schema
```

### Step 3: Configure

Update `config.php` with your database and secret key.

### Step 4: Deploy

Upload to web server with PHP 8.2+ and MySQL.

## Update Package Configuration

After setting up server, update `license-protection/src/Config/LicenseConfig.php`:

```php
protected static $validationServer = 'https://your-license-server.com/api';
protected static $secretKey = 'your-secret-key-must-match-server';
```

## Create Test License

```sql
INSERT INTO licenses (license_key, domain, server_ip, is_active)
VALUES ('TEST-KEY-12345', 'localhost', '127.0.0.1', TRUE);
```

## Test

```bash
# In your Laravel app
php artisan license:set TEST-KEY-12345
php artisan license:validate
```

## Production Deployment

1. Deploy server with HTTPS
2. Update `LicenseConfig.php` with production URL
3. Set strong secret key (64+ characters)
4. Create licenses for customers
5. Monitor server logs

