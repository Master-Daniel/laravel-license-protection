# Authentication Guide for Private Repository Installation

When your package is in a private Git repository, users need to authenticate to install it via Composer. This guide explains all authentication methods.

## Overview

There are several ways to authenticate with private repositories:

1. **Global Composer Config** (Recommended for users)
2. **Auth.json File** (Project-specific, not committed)
3. **SSH Keys** (Alternative method)
4. **Token in Repository URL** (Not recommended, visible in composer.json)

## Method 1: Global Composer Config (Recommended)

This is the best method for end users. They configure authentication once globally.

### For GitHub

1. **User creates a Personal Access Token:**
   - Go to: https://github.com/settings/tokens
   - Click "Generate new token (classic)"
   - Name: "Composer - Laravel License Protection"
   - Select scope: `repo` (full control of private repositories)
   - Generate and copy the token

2. **User configures Composer globally:**
   ```bash
   composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN
   ```

3. **User installs package:**
   ```bash
   cd backend
   composer require elite-codec/laravel-license-protection
   ```

### For GitLab

1. **User creates a Personal Access Token:**
   - Go to: https://gitlab.com/-/user_settings/personal_access_tokens
   - Name: "Composer - Laravel License Protection"
   - Select scope: `read_repository`
   - Generate and copy the token

2. **User configures Composer globally:**
   ```bash
   composer config --global gitlab-token.gitlab.com YOUR_GITLAB_TOKEN
   ```

3. **User installs package:**
   ```bash
   cd backend
   composer require elite-codec/laravel-license-protection
   ```

### For Bitbucket

1. **User creates an App Password:**
   - Go to: https://bitbucket.org/account/settings/app-passwords/
   - Create app password with `Repositories: Read` permission
   - Copy the password

2. **User configures Composer globally:**
   ```bash
   composer config --global bitbucket-oauth.bitbucket.org YOUR_USERNAME YOUR_APP_PASSWORD
   ```

3. **User installs package:**
   ```bash
   cd backend
   composer require elite-codec/laravel-license-protection
   ```

## Method 2: Auth.json File (Project-Specific)

Users can create a local `auth.json` file in their project root (this file should NOT be committed).

### Create auth.json

**For GitHub:**
```json
{
    "github-oauth": {
        "github.com": "YOUR_GITHUB_TOKEN"
    }
}
```

**For GitLab:**
```json
{
    "gitlab-token": {
        "gitlab.com": "YOUR_GITLAB_TOKEN"
    }
}
```

**For Bitbucket:**
```json
{
    "bitbucket-oauth": {
        "bitbucket.org": {
            "consumer-key": "YOUR_USERNAME",
            "consumer-secret": "YOUR_APP_PASSWORD"
        }
    }
}
```

### Add to .gitignore

```bash
echo "auth.json" >> .gitignore
```

## Method 3: SSH Keys (Alternative)

Users can use SSH instead of HTTPS for authentication.

### Setup SSH Key

1. **Generate SSH key (if not exists):**
   ```bash
   ssh-keygen -t ed25519 -C "your_email@example.com"
   ```

2. **Add SSH key to Git provider:**
   - GitHub: https://github.com/settings/keys
   - GitLab: https://gitlab.com/-/profile/keys
   - Bitbucket: https://bitbucket.org/account/settings/ssh-keys/

3. **Update composer.json to use SSH:**
   ```json
   {
       "repositories": [
           {
               "type": "vcs",
               "url": "git@github.com:yourusername/laravel-license-protection.git"
           }
       ]
   }
   ```

## Method 4: Token in URL (Not Recommended)

⚠️ **Warning**: This method exposes the token in `composer.json` which is usually committed to version control. Only use for testing.

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://YOUR_TOKEN@github.com/yourusername/laravel-license-protection.git"
        }
    ]
}
```

## Instructions for Package Users

### Quick Setup Instructions

Include these instructions when distributing your package:

```markdown
## Installation

### Step 1: Get Authentication Token

**For GitHub:**
1. Go to https://github.com/settings/tokens
2. Generate new token (classic) with `repo` scope
3. Copy the token

**For GitLab:**
1. Go to https://gitlab.com/-/user_settings/personal_access_tokens
2. Create token with `read_repository` scope
3. Copy the token

### Step 2: Configure Composer

```bash
# For GitHub
composer config --global github-oauth.github.com YOUR_TOKEN

# For GitLab
composer config --global gitlab-token.gitlab.com YOUR_TOKEN
```

### Step 3: Install Package

```bash
cd backend
composer require elite-codec/laravel-license-protection
```
```

## Troubleshooting

### "Authentication required" Error

1. Verify token is correct
2. Check token has correct permissions (repo access)
3. Verify repository URL is correct
4. Try clearing Composer cache: `composer clear-cache`

### "Repository not found" Error

1. Verify repository exists and is accessible
2. Check token has access to the repository
3. Verify repository URL in `composer.json`

### Token Expired

1. Generate new token
2. Update Composer config with new token
3. Clear cache: `composer clear-cache`
4. Try installation again

## Best Practices

### For Package Developers

1. **Provide clear instructions** in README.md
2. **Include authentication steps** in setup guide
3. **Offer support** for authentication issues
4. **Consider using SSH** as alternative (no token management)

### For Package Users

1. **Use global config** (Method 1) for convenience
2. **Never commit tokens** to version control
3. **Use strong tokens** with minimal required permissions
4. **Rotate tokens** periodically for security

## Security Considerations

1. **Never commit tokens** in `composer.json` or `auth.json`
2. **Use minimal permissions** - only `repo` or `read_repository` scope
3. **Rotate tokens** every 90 days
4. **Use environment variables** for CI/CD pipelines
5. **Add auth.json to .gitignore** if using Method 2

## CI/CD Integration

For automated deployments, use environment variables:

```bash
# GitHub Actions
composer config github-oauth.github.com ${{ secrets.GITHUB_TOKEN }}

# GitLab CI
composer config gitlab-token.gitlab.com $CI_JOB_TOKEN

# Custom
composer config github-oauth.github.com $COMPOSER_GITHUB_TOKEN
```

## Summary

**Recommended Flow:**
1. User gets Personal Access Token from Git provider
2. User configures Composer globally: `composer config --global github-oauth.github.com TOKEN`
3. User installs package: `composer require elite-codec/laravel-license-protection`
4. Package installs successfully from private repository

This is the most secure and user-friendly approach.

