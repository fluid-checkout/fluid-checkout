#!/bin/bash

# Path to your LocalWP site environment script (one file per Local site).
#
# Copy this file to env.local.config.sh (gitignored) in the plugin root.
# Agents and scripts load the site via: source env.local.sh
#
# How to find the path:
# 1. In Local, select your site → "Open site shell", or
# 2. Browse ssh-entry and pick the .sh file for that site (site ID in the filename).
#
# - macOS: `/Users/your-username/Library/Application Support/Local/ssh-entry/`
# - Linux: `/home/your-username/.config/Local/ssh-entry/`
LOCALWP_SCRIPT_PATH="/path/to/LocalWP/files/Local/ssh-entry/site-file.sh"
