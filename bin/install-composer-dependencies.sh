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

# Install production dependencies
echo "Installing production dependencies..."
composer install --no-dev

# Copy the composer.json file to a temporary file
cp composer.json composer.dev.json

# Modify the temporary composer.json file to install only dev dependencies
jq 'del(.require) | .config["vendor-dir"] = "vendor-dev"' composer.dev.json > composer.dev.json.tmp && mv composer.dev.json.tmp composer.dev.json

# Ensure the composer.dev.json file exists before proceeding
if [ ! -f composer.dev.json ]; then
	echo "Error: composer.dev.json file was not created."
	exit 1
fi

# Install development dependencies into vendor-dev directory
echo "Installing development dependencies..."
COMPOSER=composer.dev.json composer install --no-scripts

echo "Production and development dependencies installed successfully."
