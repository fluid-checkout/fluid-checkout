#!/bin/bash

# Load the configuration file (which contains the LOCALWP_SCRIPT_PATH)
source "./env.local.config.sh"

# Check if the LocalWP script exists
if [ -f "$LOCALWP_SCRIPT_PATH" ]; then
    # Output a message to indicate the script is being processed
    echo "Processing $LOCALWP_SCRIPT_PATH..."

    # Remove the last 5 lines from the script and store it in a variable
    MODIFIED_SCRIPT=$( sed '$d' "$LOCALWP_SCRIPT_PATH" | sed '$d' | sed '$d' | sed '$d' | sed '$d' )

    # Optionally, save the modified script to a temp file (if needed for debugging)
    rm /tmp/modified-local-env.sh
    echo "$MODIFIED_SCRIPT" > /tmp/modified-local-env.sh

    # Execute the modified script
    source "/tmp/modified-local-env.sh"

    echo "LocalWP environment loaded with the last 5 lines removed."
else
    echo "Error: LocalWP environment script not found at $LOCALWP_SCRIPT_PATH."
fi
