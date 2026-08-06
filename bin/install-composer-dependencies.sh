#!/bin/bash

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
