@echo off
REM Quick Publish Script for License Protection Package (Windows)
REM This script helps you quickly publish the package to a Git repository

echo.
echo 🚀 Publishing Laravel License Protection Package
echo.

REM Check if git is initialized
if not exist ".git" (
    echo 📦 Initializing Git repository...
    git init
    git add .
    git commit -m "Initial commit: Laravel License Protection Package v1.0.0"
    echo ✅ Git repository initialized
) else (
    echo ✅ Git repository already exists
)

REM Check if remote exists
git remote show origin >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Remote repository already configured
    for /f "tokens=*" %%i in ('git remote get-url origin') do set REMOTE_URL=%%i
    echo    Remote: %REMOTE_URL%
) else (
    echo.
    echo ⚠️  No remote repository configured
    echo.
    echo Please create a private repository on:
    echo   - GitHub: https://github.com/new
    echo   - GitLab: https://gitlab.com/projects/new
    echo   - Bitbucket: https://bitbucket.org/repo/create
    echo.
    set /p REPO_URL="Enter your repository URL: "
    
    if "%REPO_URL%"=="" (
        echo ❌ Repository URL is required
        exit /b 1
    )
    
    git remote add origin "%REPO_URL%"
    echo ✅ Remote repository added
)

REM Push to remote
echo.
echo 📤 Pushing to remote repository...
git branch -M main
git push -u origin main

REM Create and push tag
echo.
echo 🏷️  Creating version tag...
git tag -a v1.0.0 -m "Version 1.0.0"
git push origin v1.0.0

echo.
echo ✅ Package published successfully!
echo.
echo Next steps:
echo 1. Update backend/composer.json with your repository URL
echo 2. Run: composer require elite-codec/laravel-license-protection
echo 3. See MIGRATION_GUIDE.md for complete instructions
echo.

pause

