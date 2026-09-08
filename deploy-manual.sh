#!/usr/bin/env bash
# ============================================================
# MANUAL DEPLOYMENT SCRIPT - PMU to Hostinger
# ============================================================
# Structure:
#   public_html/
#     ├── index.html          <- Nuxt frontend
#     ├── _nuxt/              <- Nuxt assets
#     ├── .htaccess           <- Routes /api to /api/index.php
#     └── api/
#         ├── pmuapi/         <- Full Laravel app (protected by .htaccess)
#         ├── index.php       <- Entry point: loads pmuapi/public/index.php
#         └── .htaccess       <- URL rewrite + security rules
# ============================================================

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
PMUUI="$PROJECT_ROOT/PMUUI"
PMUAPI="$PROJECT_ROOT/PMUAPI"
OUTPUT_DIR="$PMUUI/.output/public"
DEPLOY_DIR="$PROJECT_ROOT/_deploy"
LARAVEL_DIR="pmuapi"

echo ""
echo "=========================================="
echo "STEP 1: Building Nuxt frontend"
echo "=========================================="

cd "$PMUUI"

echo "Installing npm dependencies..."
npm install --no-audit --no-fund

echo "Generating Nuxt frontend..."
npm run generate

test -f "$OUTPUT_DIR/index.html" || {
    echo "ERROR: Nuxt build failed - index.html not found"
    exit 1
}
echo "Build succeeded!"


echo ""
echo "=========================================="
echo "STEP 2: Preparing deployment package"
echo "=========================================="

rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

echo "Copying Nuxt frontend..."
cp -a "$OUTPUT_DIR/." "$DEPLOY_DIR/"

echo "Copying Laravel API..."
mkdir -p "$DEPLOY_DIR/api/$LARAVEL_DIR"
cp -a "$PMUAPI/." "$DEPLOY_DIR/api/$LARAVEL_DIR/"


echo ""
echo "=========================================="
echo "STEP 3: Creating API entry points"
echo "=========================================="

# /api/index.php - loads Laravel from /api/pmuapi/public/index.php
printf '%s\n' \
    '<?php' \
    "" \
    "require __DIR__ . '/$LARAVEL_DIR/public/index.php';" \
    > "$DEPLOY_DIR/api/index.php"

# /api/.htaccess - URL rewrite + security rules
printf '%s\n' \
    '<IfModule mod_rewrite.c>' \
    '' \
    '    RewriteEngine On' \
    '    RewriteBase /api/' \
    '' \
    '    RewriteCond %{REQUEST_FILENAME} !-f' \
    '    RewriteCond %{REQUEST_FILENAME} !-d' \
    '    RewriteRule ^ index.php [L]' \
    '' \
    '    RewriteCond %{REQUEST_URI} ^/api/pmuapi/\.env [NC]' \
    '    RewriteRule ^ - [F,L]' \
    '' \
    '    RewriteCond %{REQUEST_URI} ^/api/pmuapi/(config|app|vendor|bootstrap|resources|routes|storage) [NC]' \
    '    RewriteRule ^ - [F,L]' \
    '' \
    '    RewriteCond %{REQUEST_URI} /(^|/)\. [NC]' \
    '    RewriteRule ^ - [F,L]' \
    '</IfModule>' \
    > "$DEPLOY_DIR/api/.htaccess"

# Root .htaccess - routes /api/ to /api/index.php, everything else to Nuxt
printf '%s\n' \
    '<IfModule mod_rewrite.c>' \
    '' \
    '    RewriteEngine On' \
    '' \
    '    RewriteCond %{REQUEST_FILENAME} -f [OR]' \
    '    RewriteCond %{REQUEST_FILENAME} -d' \
    '    RewriteRule ^ - [L]' \
    '' \
    '    RewriteRule ^api(?:/(.*))?$ api/index.php [L,QSA]' \
    '' \
    '    RewriteRule ^ index.html [L]' \
    '</IfModule>' \
    > "$DEPLOY_DIR/.htaccess"


echo ""
echo "=========================================="
echo "STEP 4: Final verification"
echo "=========================================="

echo "Checking frontend..."
test -f "$DEPLOY_DIR/index.html" && echo "  OK: index.html"
test -d "$DEPLOY_DIR/_nuxt" && echo "  OK: _nuxt/"

echo "Checking API..."
test -f "$DEPLOY_DIR/api/index.php" && echo "  OK: api/index.php"
test -f "$DEPLOY_DIR/api/.htaccess" && echo "  OK: api/.htaccess"
test -f "$DEPLOY_DIR/.htaccess" && echo "  OK: .htaccess (root)"
test -d "$DEPLOY_DIR/api/$LARAVEL_DIR" && echo "  OK: api/pmuapi/"
test -f "$DEPLOY_DIR/api/$LARAVEL_DIR/public/index.php" && echo "  OK: api/pmuapi/public/index.php"


echo ""
echo "=========================================="
echo "STEP 5: Publishing to git deploy branch"
echo "=========================================="

DEPLOY_BRANCH="deploy"

git config --global user.name  "${GIT_USER_NAME:-github-actions[bot]}"
git config --global user.email "${GIT_USER_EMAIL:-github-actions[bot]@users.noreply.github.com}"

git checkout main 2>/dev/null || true
git fetch --all

if git rev-parse --verify "$DEPLOY_BRANCH" >/dev/null 2>&1; then
    echo "Switching to existing $DEPLOY_BRANCH branch..."
    git checkout "$DEPLOY_BRANCH"
    git rm -rf . || true
else
    echo "Creating orphan $DEPLOY_BRANCH branch..."
    git checkout --orphan "$DEPLOY_BRANCH"
    git rm -rf . || true
fi

cat > .gitignore << 'GITIGNORE'
.env
.env.backup
.env.production
vendor/
node_modules/
storage/*.key
public/build/
public/hot/
public/storage/
public/fonts-manifest.dev.json
.phpunit.result.cache
.phpunit.cache
_ide_helper.php
auth.json
GITIGNORE

cp -a "$DEPLOY_DIR/." ./

git add -A

if git diff --cached --quiet; then
    echo "No changes to commit - deploy branch is up to date."
else
    git commit -m "Deploy build (commit $(git rev-parse --short HEAD 2>/dev/null || echo 'manual'))"
    git push origin "$DEPLOY_BRANCH" --force
    echo "Deploy branch updated and pushed."
fi

git checkout main


echo ""
echo "=========================================="
echo "DEPLOYMENT PACKAGE READY"
echo "=========================================="
echo ""
echo "Folder structure (in deploy branch):"
echo "  index.html              -> public_html/index.html"
echo "  _nuxt/                  -> public_html/_nuxt/"
echo "  .htaccess               -> public_html/.htaccess"
echo "  api/                    -> public_html/api/"
echo "  api/index.php           -> public_html/api/index.php"
echo "  api/.htaccess           -> public_html/api/.htaccess"
echo "  api/pmuapi/             -> public_html/api/pmuapi/  (full Laravel)"
echo "  api/pmuapi/public/      -> public_html/api/pmuapi/public/"
echo ""
echo "API calls:"
echo "  Frontend: https://yoursite.com"
echo "  API:      https://yoursite.com/api"
echo "  Laravel:  https://yoursite.com/api/pmuapi/"
echo ""
echo "To deploy to Hostinger:"
echo "  1. git checkout deploy"
echo "  2. Upload all files to public_html/ via FTP/SFTP"
echo ""