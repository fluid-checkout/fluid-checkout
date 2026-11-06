#!/bin/bash
set -euo pipefail

if ! command -v composer >/dev/null 2>&1; then
	echo "Error: composer is not on PATH. Install Composer and retry." >&2
	exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
	echo "Error: jq is not on PATH. Install jq and retry." >&2
	exit 1
fi

# Install production dependencies only when composer.json declares non-platform packages.
# Many FC plugins only set require.php (or ext-*); running `composer install --no-dev` still
# creates vendor/composer/* with nothing to autoload — skip and drop a stale vendor/ tree.
non_platform_require_count="$(
	jq '[ (.require // {}) | to_entries[] | select(.key != "php" and (.key | startswith("ext-") | not)) ] | length' composer.json
)"

if [ "${non_platform_require_count}" -gt 0 ]; then
	echo "Installing production dependencies..."
	composer install --no-dev
else
	echo "Skipping production Composer install (require has only platform constraints, no packages)."
	if [ -d vendor ]; then
		rm -rf vendor
		echo "Removed empty or unused vendor/ directory."
	fi
fi

# Copy the composer.json file to a temporary file
cp composer.json composer.dev.json

# Modify the temporary composer.json file to install only dev dependencies
# Route the test script through vendor-dev/bin/phpunit (Composer does not put vendor-dev on PATH).
jq 'del(.require) | .config["vendor-dir"] = "vendor-dev" | .scripts.test = "./vendor-dev/bin/phpunit --configuration phpunit.xml.dist"' composer.dev.json > composer.dev.json.tmp && mv composer.dev.json.tmp composer.dev.json

# Ensure the composer.dev.json file exists before proceeding
if [ ! -f composer.dev.json ]; then
	echo "Error: composer.dev.json file was not created."
	exit 1
fi

# Install development dependencies into vendor-dev directory
echo "Installing development dependencies..."
COMPOSER=composer.dev.json composer install --no-scripts

echo "Production and development dependencies installed successfully."
