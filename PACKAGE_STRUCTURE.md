# Package Structure and Installation

## What Gets Installed?

When users install this package via Composer, only essential files are included:

### ✅ Included in Installation

- `src/` - All source code (required)
- `config/` - Configuration files (required)
- `database/migrations/` - Database migrations (required)
- `composer.json` - Package definition (required)
- `README.md` - Main documentation (helpful for users)

### ❌ Excluded from Installation

The following files are excluded using `.gitattributes`:

- `AUTHENTICATION_GUIDE.md` - Development documentation
- `INSTALLATION.md` - Development documentation
- `SETUP.md` - Development documentation
- `PUBLISHING.md` - Development documentation
- `MIGRATION_GUIDE.md` - Development documentation
- `PACKAGE_STRUCTURE.md` - This file
- `QUICK_PUBLISH.sh` - Development script
- `QUICK_PUBLISH.bat` - Development script
- `.gitattributes` - Git configuration
- `.gitignore` - Git configuration

## Why Exclude Documentation?

1. **Cleaner Installation**: Users don't need development docs in their vendor directory
2. **Smaller Package**: Reduces installed package size
3. **Security**: Development scripts and guides stay in repository only
4. **Standard Practice**: Most Composer packages exclude non-essential files

## Accessing Full Documentation

All documentation remains available in the Git repository:

- Clone the repository to see all files
- View on GitHub/GitLab/Bitbucket web interface
- Documentation is for package developers, not end users

## For Package Developers

When developing or updating the package:

1. All files are available in the Git repository
2. Use `QUICK_PUBLISH.sh` or `QUICK_PUBLISH.bat` to publish
3. Documentation helps with maintenance and updates
4. `.gitattributes` ensures only essential files are distributed

## For End Users

When installing the package:

1. Only essential files are installed to `vendor/elite-codec/laravel-license-protection/`
2. Main `README.md` is included for basic usage
3. Full documentation is available in the repository if needed
4. Package is clean and focused on functionality

## How It Works

The `.gitattributes` file uses `export-ignore` to exclude files:

```
*.md export-ignore
!README.md export-ignore
```

This tells Git to exclude all `.md` files except `README.md` when creating the Composer package archive.

## Verifying Installation

After installation, check what was installed:

```bash
ls -la vendor/elite-codec/laravel-license-protection/
```

You should see:
- `src/` directory
- `config/` directory
- `database/` directory
- `composer.json`
- `README.md`

You should NOT see:
- `AUTHENTICATION_GUIDE.md`
- `INSTALLATION.md`
- `SETUP.md`
- `PUBLISHING.md`
- Development scripts

