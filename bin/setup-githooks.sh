#!/bin/sh
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

git config --local core.hooksPath .githooks
chmod +x .githooks/post-checkout

echo "Git hooks enabled: core.hooksPath=.githooks"
echo "gulp build will run automatically after branch checkouts."
