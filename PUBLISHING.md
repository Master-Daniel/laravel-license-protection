# Publishing License Protection Package

This guide explains how to publish this package so it can be installed via Composer into the `vendor/` directory.

## Option 1: Private Git Repository (Recommended)

This is the best option for proprietary packages. The package will be installed from your private Git repository.

### Step 1: Create Git Repository

```bash
cd license-protection
git init
git add .
git commit -m "Initial commit: Laravel License Protection Package"
```

### Step 2: Push to Remote Repository

**Option A: GitHub (Private Repository)**
```bash
# Create a private repository on GitHub, then:
git remote add origin https://github.com/master-daniel/laravel-license-protection.git
git branch -M main
git push -u origin main
```

**Option B: GitLab (Private Repository)**
```bash
# Create a private repository on GitLab, then:
git remote add origin https://gitlab.com/yourusername/laravel-license-protection.git
git branch -M main
git push -u origin main
```

**Option C: Bitbucket (Private Repository)**
```bash
# Create a private repository on Bitbucket, then:
git remote add origin https://bitbucket.org/yourusername/laravel-license-protection.git
git branch -M main
git push -u origin main
```

### Step 3: Update Main composer.json

Update `backend/composer.json` to point to your repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/yourusername/laravel-license-protection.git"
        }
    ],
    "require": {
        "elite-codec/laravel-license-protection": "*"
    }
}
```

### Step 4: Install via Composer

```bash
cd backend
composer require elite-codec/laravel-license-protection
```

The package will now be installed in `vendor/elite-codec/laravel-license-protection/`

### Step 5: Remove Local Package

After successful installation:

```bash
# Remove the local package directory
rm -rf license-protection
```

## Option 2: Private Packagist

If you want to use a private Packagist instance:

1. Sign up at https://packagist.com (paid service)
2. Add your Git repository to Packagist
3. Update `composer.json` to use Packagist repository
4. Install via `composer require elite-codec/laravel-license-protection`

## Option 3: Local Path Repository (Development Only)

For development, you can use a local path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./license-protection"
        }
    ],
    "require": {
        "elite-codec/laravel-license-protection": "*"
    }
}
```

**Note**: This is NOT secure for production as the package can still be easily removed.

## Option 4: Private Composer Repository (Satis)

Set up your own private Composer repository using Satis:

1. Install Satis: `composer create-project composer/satis --stability=dev`
2. Configure Satis to include your Git repository
3. Host the generated repository
4. Add repository URL to `composer.json`

## Authentication for Private Repositories

When users install your package from a private repository, they need to authenticate. See `AUTHENTICATION_GUIDE.md` for complete instructions.

### Quick Setup for Users

**For GitHub:**
1. User creates Personal Access Token at https://github.com/settings/tokens (scope: `repo`)
2. User runs: `composer config --global github-oauth.github.com THEIR_TOKEN`
3. User installs: `composer require elite-codec/laravel-license-protection`

**For GitLab:**
1. User creates Personal Access Token at https://gitlab.com/-/user_settings/personal_access_tokens (scope: `read_repository`)
2. User runs: `composer config --global gitlab-token.gitlab.com THEIR_TOKEN`
3. User installs: `composer require elite-codec/laravel-license-protection`

**For Bitbucket:**
1. User creates App Password at https://bitbucket.org/account/settings/app-passwords/ (permission: `Repositories: Read`)
2. User runs: `composer config --global bitbucket-oauth.bitbucket.org USERNAME APP_PASSWORD`
3. User installs: `composer require elite-codec/laravel-license-protection`

### Include Instructions in Your Package

When distributing your package, include authentication instructions in your README or provide `AUTHENTICATION_GUIDE.md` to users.

## Versioning

Tag releases for version control:

```bash
git tag -a v1.0.0 -m "Version 1.0.0"
git push origin v1.0.0
```

Then require specific version:

```json
{
    "require": {
        "elite-codec/laravel-license-protection": "^1.0.0"
    }
}
```

## Updating the Package

After making changes to the package:

```bash
cd license-protection
git add .
git commit -m "Update: Description of changes"
git push
```

Then in the main application:

```bash
cd backend
composer update elite-codec/laravel-license-protection
```

## Security Benefits

Once published and installed via Composer:

✅ Package is in `vendor/` directory (harder to accidentally delete)
✅ Managed by Composer (version controlled)
✅ Can be updated via `composer update`
✅ Protected by Git repository access
✅ Can be versioned and tagged
✅ Works with CI/CD pipelines

## Troubleshooting

### "Package not found"

- Verify repository URL is correct
- Check authentication credentials
- Ensure repository is accessible
- Run `composer clear-cache`

### "Authentication required"

- Set up Git credentials or tokens
- Configure Composer authentication
- Check repository permissions

### "Package still in packages/ directory"

- Remove from `composer.json` autoload
- Run `composer dump-autoload`
- Verify package is in `vendor/` directory

