# Installation Guide for End Users

This guide is for users who want to install the Laravel License Protection package from a private repository.

## Prerequisites

- Laravel application
- Composer installed
- Access to the private Git repository (GitHub/GitLab/Bitbucket)

## Step-by-Step Installation

### Step 1: Get Authentication Token

You need a Personal Access Token (PAT) to authenticate with the private repository.

#### For GitHub

1. Go to: https://github.com/settings/tokens
2. Click **"Generate new token (classic)"**
3. Give it a name: "Composer - Laravel License Protection"
4. Select scope: **`repo`** (full control of private repositories)
5. Click **"Generate token"**
6. **Copy the token immediately** (you won't see it again)

#### For GitLab

1. Go to: https://gitlab.com/-/user_settings/personal_access_tokens
2. Give it a name: "Composer - Laravel License Protection"
3. Select scope: **`read_repository`**
4. Set expiration (optional)
5. Click **"Create personal access token"**
6. **Copy the token immediately**

#### For Bitbucket

1. Go to: https://bitbucket.org/account/settings/app-passwords/
2. Click **"Create app password"**
3. Label: "Composer - Laravel License Protection"
4. Select permission: **`Repositories: Read`**
5. Click **"Create"**
6. **Copy the password immediately**

### Step 2: Configure Composer Authentication

Configure Composer to use your token globally (one-time setup):

**For GitHub:**
```bash
composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN
```

**For GitLab:**
```bash
composer config --global gitlab-token.gitlab.com YOUR_GITLAB_TOKEN
```

**For Bitbucket:**
```bash
composer config --global bitbucket-oauth.bitbucket.org YOUR_USERNAME YOUR_APP_PASSWORD
```

### Step 3: Add Repository to composer.json

Add the repository to your `composer.json`:

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

**Replace the URL** with the actual repository URL provided by the package developer.

### Step 4: Install the Package

```bash
cd backend
composer require elite-codec/laravel-license-protection
```

Or if already in `require`:
```bash
composer update elite-codec/laravel-license-protection
```

### Step 5: Run Migrations

```bash
php artisan migrate
```

### Step 6: Set License Key

```bash
php artisan license:set YOUR_LICENSE_KEY
```

The license will be automatically bound to your domain and server IP.

### Step 7: Validate License

```bash
php artisan license:validate
```

## Troubleshooting

### "Authentication required" Error

- Verify your token is correct
- Check token has correct permissions (repo/read_repository)
- Try: `composer clear-cache`
- Verify repository URL is correct

### "Repository not found" Error

- Verify repository exists and is accessible
- Check your token has access to the repository
- Verify repository URL in `composer.json`

### Token Expired

1. Generate a new token
2. Update Composer config: `composer config --global github-oauth.github.com NEW_TOKEN`
3. Clear cache: `composer clear-cache`
4. Try installation again

## Alternative: Project-Specific Authentication

If you prefer not to use global config, create `auth.json` in your project root:

```json
{
    "github-oauth": {
        "github.com": "YOUR_GITHUB_TOKEN"
    }
}
```

**Important**: Add `auth.json` to `.gitignore` to prevent committing tokens:

```bash
echo "auth.json" >> .gitignore
```

## Need Help?

- See `AUTHENTICATION_GUIDE.md` for detailed authentication methods
- See `SETUP.md` for package setup instructions
- Contact package developer for repository access

