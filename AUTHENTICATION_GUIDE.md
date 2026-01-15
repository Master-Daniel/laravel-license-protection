# Authentication Guide for Private Repository Installation

## ⚠️ Important: Who Needs to Authenticate?

**Each end user** who wants to install your package must:
1. **Have access** to your private repository (you grant them access)
2. **Create their own token** from their own GitHub/GitLab account
3. **Configure it themselves** - you never share your token

**You (the package developer) do NOT:**
- ❌ Share your personal token with users
- ❌ Give users your credentials
- ❌ Run authentication commands for users

**You (the package developer) DO:**
- ✅ Grant users access to your private repository
- ✅ Provide instructions on how to authenticate
- ✅ Support users if they have authentication issues

## Granting Repository Access

Before users can install, you must grant them access to your private repository:

### For GitHub

1. Go to your repository → **Settings** → **Collaborators**
2. Click **"Add people"**
3. Enter their GitHub username or email
4. Select permission level: **Read** (they only need read access)
5. They will receive an invitation email

**Or use GitHub Organizations:**
- Add users to your organization
- Grant them access to the repository
- More scalable for multiple users

### For GitLab

1. Go to your repository → **Settings** → **Members**
2. Click **"Invite members"**
3. Enter their GitLab username or email
4. Select role: **Reporter** or **Developer** (read access)
5. They will receive an invitation

### For Bitbucket

1. Go to your repository → **Settings** → **User and group access**
2. Click **"Add users"**
3. Enter their Bitbucket username
4. Select permission: **Read**
5. They will receive an invitation

## Authentication Methods

Once users have repository access, they authenticate using one of these methods:

---

## Method 1: Fine-Grained Token (Recommended for GitHub)

### ⚠️ Security Note

**Each user creates their own token** from their own GitHub account. You never share tokens.

### For GitHub - Fine-Grained Token

1. **User creates their own Fine-Grained Personal Access Token:**
   - User goes to: https://github.com/settings/tokens?type=beta
   - User clicks "Generate new token"
   - User names it: "Composer - Laravel License Protection"
   - User sets expiration
   - **Repository access**: User selects "Only select repositories" and chooses your license-protection repository
   - **Permissions**: 
     - Repository permissions → Contents: Read-only
     - Repository permissions → Metadata: Read-only
   - User generates and copies the token

2. **User configures Composer globally (on their machine):**
   ```bash
   composer config --global github-oauth.github.com THEIR_OWN_TOKEN
   ```

3. **User installs package:**
   ```bash
   cd backend
   composer require elite-codec/laravel-license-protection
   ```

### For GitHub - Classic Token (Less Secure)

⚠️ **Warning**: Classic tokens with `repo` scope access ALL repositories the user has access to.

1. **User creates their own Personal Access Token (classic):**
   - User goes to: https://github.com/settings/tokens
   - User clicks "Generate new token (classic)"
   - User names it: "Composer - Laravel License Protection"
   - User selects scope: `repo` (full control of private repositories)
   - ⚠️ **Note**: This token can access ALL the user's repositories
   - User generates and copies the token

2. **User configures Composer globally:**
   ```bash
   composer config --global github-oauth.github.com THEIR_OWN_TOKEN
   ```

3. **User installs package:**
   ```bash
   cd backend
   composer require elite-codec/laravel-license-protection
   ```

### For GitLab

1. **User creates their own Personal Access Token:**
   - User goes to: https://gitlab.com/-/user_settings/personal_access_tokens
   - User names it: "Composer - Laravel License Protection"
   - User selects scope: `read_repository`
   - User generates and copies the token

2. **User configures Composer globally:**
   ```bash
   composer config --global gitlab-token.gitlab.com THEIR_OWN_TOKEN
   ```

3. **User installs package:**
   ```bash
   cd backend
   composer require elite-codec/laravel-license-protection
   ```

### For Bitbucket

1. **User creates their own App Password:**
   - User goes to: https://bitbucket.org/account/settings/app-passwords/
   - User creates app password with `Repositories: Read` permission
   - User copies the password

2. **User configures Composer globally:**
   ```bash
   composer config --global bitbucket-oauth.bitbucket.org USERNAME THEIR_APP_PASSWORD
   ```

3. **User installs package:**
   ```bash
   cd backend
   composer require elite-codec/laravel-license-protection
   ```

---

## Method 2: Auth.json File (Project-Specific)

Users can create a local `auth.json` file in their project root (this file should NOT be committed).

### Create auth.json

**For GitHub:**
```json
{
    "github-oauth": {
        "github.com": "USER'S_OWN_TOKEN"
    }
}
```

**For GitLab:**
```json
{
    "gitlab-token": {
        "gitlab.com": "USER'S_OWN_TOKEN"
    }
}
```

**For Bitbucket:**
```json
{
    "bitbucket-oauth": {
        "bitbucket.org": {
            "consumer-key": "USERNAME",
            "consumer-secret": "USER'S_APP_PASSWORD"
        }
    }
}
```

### Add to .gitignore

```bash
echo "auth.json" >> .gitignore
```

---

## Method 3: Deploy Keys (Most Secure - Recommended)

Deploy keys are SSH keys that grant access to a single repository. This is the most secure method.

### For GitHub

1. **User generates SSH key (if not exists):**
   ```bash
   ssh-keygen -t ed25519 -C "user_email@example.com" -f ~/.ssh/license-protection
   ```

2. **You (package developer) add deploy key to repository:**
   - Go to repository → Settings → Deploy keys
   - Click "Add deploy key"
   - Title: "User Name - Composer Access"
   - Key: User provides their public key (`~/.ssh/license-protection.pub`)
   - ✅ Check "Allow write access" (if needed for updates)
   - Click "Add key"

3. **User configures SSH:**
   ```bash
   # Add to ~/.ssh/config
   Host github-license-protection
       HostName github.com
       User git
       IdentityFile ~/.ssh/license-protection
   ```

4. **User updates composer.json to use SSH:**
   ```json
   {
       "repositories": [
           {
               "type": "vcs",
               "url": "git@github-license-protection:yourusername/laravel-license-protection.git"
           }
       ]
   }
   ```

5. **User installs package:**
   ```bash
   composer require elite-codec/laravel-license-protection
   ```

**Benefits:**
- ✅ Repository-specific access (most secure)
- ✅ No token management
- ✅ Can be revoked per repository
- ✅ No access to other repositories

---

## Method 4: SSH Keys (Alternative)

Users can use SSH instead of HTTPS for authentication.

### Setup SSH Key

1. **User generates SSH key (if not exists):**
   ```bash
   ssh-keygen -t ed25519 -C "user_email@example.com"
   ```

2. **User adds SSH key to Git provider:**
   - GitHub: https://github.com/settings/keys
   - GitLab: https://gitlab.com/-/profile/keys
   - Bitbucket: https://bitbucket.org/account/settings/ssh-keys/

3. **User updates composer.json to use SSH:**
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

---

## Method 5: Token in URL (Not Recommended)

⚠️ **Warning**: This method exposes the token in `composer.json` which is usually committed to version control. Only use for testing.

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://USER_TOKEN@github.com/yourusername/laravel-license-protection.git"
        }
    ]
}
```

---

## Instructions for Package Users

### Quick Setup Instructions

Include these instructions when distributing your package:

```markdown
## Installation

### Step 1: Get Repository Access

Contact the package developer to get access to the private repository.

### Step 2: Get Authentication Token

**For GitHub (Fine-Grained Token - Recommended):**
1. Go to https://github.com/settings/tokens?type=beta
2. Generate new token
3. Select "Only select repositories" and choose the license-protection repository
4. Permissions: Contents (Read-only), Metadata (Read-only)
5. Copy the token

**For GitHub (Classic Token):**
1. Go to https://github.com/settings/tokens
2. Generate new token (classic) with `repo` scope
3. ⚠️ Warning: This accesses ALL your repositories
4. Copy the token

### Step 3: Configure Composer

```bash
# For GitHub
composer config --global github-oauth.github.com YOUR_TOKEN

# For GitLab
composer config --global gitlab-token.gitlab.com YOUR_TOKEN
```

### Step 4: Install Package

```bash
cd backend
composer require elite-codec/laravel-license-protection
```
```

---

## Troubleshooting

### "Authentication required" Error

1. Verify user has repository access
2. Verify token is correct
3. Check token has correct permissions
4. Verify repository URL is correct
5. Try clearing Composer cache: `composer clear-cache`

### "Repository not found" Error

1. Verify user has been granted access to the repository
2. Verify repository exists and is accessible
3. Check token has access to the repository
4. Verify repository URL in `composer.json`

### Token Expired

1. User generates new token
2. User updates Composer config with new token
3. Clear cache: `composer clear-cache`
4. Try installation again

---

## Best Practices

### For Package Developers

1. **Grant repository access** to users before they try to install
2. **Recommend fine-grained tokens or deploy keys** for better security
3. **Warn users** about classic token security implications
4. **Provide clear instructions** in README.md
5. **Support users** with authentication issues
6. **Never share your own tokens** with users

### For Package Users

1. **Request repository access** from the package developer first
2. **Create your own token** from your own account
3. **Use fine-grained tokens** (GitHub) or deploy keys for best security
4. **Avoid classic tokens with `repo` scope** if possible (accesses all repos)
5. **Use repository-specific access** when available
6. **Never commit tokens** to version control
7. **Use minimal required permissions** only
8. **Rotate tokens** periodically for security
9. **Revoke tokens** when no longer needed

---

## Security Considerations

1. **Never share tokens** - Each user creates their own
2. **Never commit tokens** in `composer.json` or `auth.json`
3. **Use minimal permissions** - Only `repo` or `read_repository` scope
4. **Rotate tokens** every 90 days
5. **Use environment variables** for CI/CD pipelines
6. **Add auth.json to .gitignore** if using Method 2
7. **Grant only read access** to repository (users don't need write)

---

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

---

## Summary

### Workflow

1. **You (developer)** grant user access to private repository
2. **User** creates their own token from their account
3. **User** configures Composer with their token
4. **User** installs package: `composer require elite-codec/laravel-license-protection`

### Security Recommendations (Best to Worst)

1. **Deploy Keys (Method 3)** - Most secure, repository-specific
2. **Fine-Grained Tokens (Method 1A)** - Repository-specific, good security
3. **SSH Keys (Method 4)** - Good security, requires SSH setup
4. **Classic Tokens (Method 1B)** - ⚠️ Accesses all repositories, less secure
5. **Token in URL (Method 5)** - ⚠️ Exposed in composer.json, not recommended

### Key Points

- ✅ **Each user creates their own token** - You never share yours
- ✅ **Grant repository access first** - Users need access before installing
- ✅ **Use fine-grained tokens or deploy keys** - Better security
- ✅ **Never commit tokens** - Keep them secure
