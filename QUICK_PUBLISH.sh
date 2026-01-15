#!/bin/bash

# Quick Publish Script for License Protection Package
# This script helps you quickly publish the package to a Git repository

set -e

echo "🚀 Publishing Laravel License Protection Package"
echo ""

# Check if git is initialized
if [ ! -d ".git" ]; then
    echo "📦 Initializing Git repository..."
    git init
    git add .
    git commit -m "Initial commit: Laravel License Protection Package v1.0.0"
    echo "✅ Git repository initialized"
else
    echo "✅ Git repository already exists"
fi

# Check if remote exists
if git remote | grep -q "origin"; then
    echo "✅ Remote repository already configured"
    REMOTE_URL=$(git remote get-url origin)
    echo "   Remote: $REMOTE_URL"
else
    echo ""
    echo "⚠️  No remote repository configured"
    echo ""
    echo "Please create a private repository on:"
    echo "  - GitHub: https://github.com/new"
    echo "  - GitLab: https://gitlab.com/projects/new"
    echo "  - Bitbucket: https://bitbucket.org/repo/create"
    echo ""
    read -p "Enter your repository URL: " REPO_URL
    
    if [ -z "$REPO_URL" ]; then
        echo "❌ Repository URL is required"
        exit 1
    fi
    
    git remote add origin "$REPO_URL"
    echo "✅ Remote repository added"
fi

# Push to remote
echo ""
echo "📤 Pushing to remote repository..."
git branch -M main
git push -u origin main

# Create and push tag
echo ""
echo "🏷️  Creating version tag..."
git tag -a v1.0.0 -m "Version 1.0.0"
git push origin v1.0.0

echo ""
echo "✅ Package published successfully!"
echo ""
echo "Next steps:"
echo "1. Update backend/composer.json with your repository URL"
echo "2. Run: composer require elite-codec/laravel-license-protection"
echo "3. See MIGRATION_GUIDE.md for complete instructions"
echo ""

