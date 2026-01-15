# Migration Guide: From Local Package to Composer Package

This guide helps you migrate from the local `packages/` directory to a Composer-installed package in `vendor/`.

## Current State

- Package is in: `license-protection/`
- Autoloaded from: `composer.json` autoload section
- Can be easily deleted

## Target State

- Package will be in: `backend/vendor/elite-codec/laravel-license-protection/`
- Installed via: `composer require`
- Managed by Composer
- Harder to accidentally remove

## Migration Steps

### Step 1: Initialize Git Repository

```bash
cd license-protection
git init
git add .
git commit -m "Initial commit: Laravel License Protection Package v1.0.0"
```

### Step 2: Create Remote Repository

Create a private repository on:
- GitHub: https://github.com/new (make it private)
- GitLab: https://gitlab.com/projects/new (make it private)
- Bitbucket: https://bitbucket.org/repo/create (make it private)

### Step 3: Push to Remote

```bash
# Replace with your actual repository URL
git remote add origin https://github.com/yourusername/laravel-license-protection.git
git branch -M main
git push -u origin main

# Tag the first version
git tag -a v1.0.0 -m "Version 1.0.0"
git push origin v1.0.0
```

### Step 4: Update Main composer.json

Edit `backend/composer.json`:

**Remove from autoload:**
```json
// REMOVE THIS:
"LicenseProtection\\": "license-protection/src/"
```

**Add repository and require:**
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

### Step 5: Authenticate with Private Repository

**Before installing, users need to authenticate:**

**For GitHub:**
```bash
# User gets token from: https://github.com/settings/tokens (scope: repo)
composer config --global github-oauth.github.com THEIR_GITHUB_TOKEN
```

**For GitLab:**
```bash
# User gets token from: https://gitlab.com/-/user_settings/personal_access_tokens (scope: read_repository)
composer config --global gitlab-token.gitlab.com THEIR_GITLAB_TOKEN
```

**For Bitbucket:**
```bash
# User gets app password from: https://bitbucket.org/account/settings/app-passwords/
composer config --global bitbucket-oauth.bitbucket.org USERNAME APP_PASSWORD
```

See `AUTHENTICATION_GUIDE.md` for complete authentication instructions.

### Step 6: Install via Composer

```bash
cd backend

# Remove old autoload entry
composer dump-autoload

# Install the package
composer require elite-codec/laravel-license-protection

# Or if already in require, just update
composer update elite-codec/laravel-license-protection
```

### Step 7: Verify Installation

Check that package is in vendor:

```bash
ls -la vendor/elite-codec/laravel-license-protection/
```

Verify autoload:

```bash
composer dump-autoload
php artisan license:validate
```

### Step 8: Remove Local Package (After Verification)

**ONLY after confirming everything works:**

```bash
# Backup first (optional)
cp -r license-protection license-protection.backup

# Remove local package
rm -rf license-protection
```

### Step 9: Test Application

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Test the application
php artisan serve

# Test license validation
php artisan license:validate
```

## Authentication Setup

### For GitHub Private Repository

```bash
# Generate Personal Access Token on GitHub
# Settings > Developer settings > Personal access tokens > Tokens (classic)
# Scopes: repo (full control of private repositories)

# Configure Composer
composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN
```

### For GitLab Private Repository

Add token to repository URL in `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://oauth2:YOUR_TOKEN@gitlab.com/yourusername/laravel-license-protection.git"
        }
    ]
}
```

## Rollback Plan

If something goes wrong:

```bash
# Restore from backup
cp -r license-protection.backup license-protection

# Revert composer.json changes
git checkout composer.json

# Restore autoload
composer dump-autoload
```

## Verification Checklist

- [ ] Package is in `vendor/elite-codec/laravel-license-protection/`
- [ ] `composer show elite-codec/laravel-license-protection` shows package info
- [ ] `php artisan license:validate` works
- [ ] Application boots without errors
- [ ] API routes are protected
- [ ] License middleware is active
- [ ] No references to `license-protection` in code (except as Composer package)

## Future Updates

To update the package:

```bash
# Make changes in the Git repository
cd path/to/laravel-license-protection-repo
# ... make changes ...
git add .
git commit -m "Update: Description"
git tag -a v1.0.1 -m "Version 1.0.1"
git push origin main --tags

# Update in application
cd backend
composer update elite-codec/laravel-license-protection
```

## Benefits After Migration

✅ Package in `vendor/` (standard location)
✅ Managed by Composer (version control)
✅ Can't be easily deleted
✅ Versioned releases
✅ Easy updates via `composer update`
✅ Works with CI/CD
✅ Professional package structure

