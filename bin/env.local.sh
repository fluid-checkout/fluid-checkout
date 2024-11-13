#!/bin/bash

# Get folder paths
SCRIPT_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
TMPDIR=${TMPDIR-/tmp}

# Load the configuration file (which contains the LOCALWP_SCRIPT_PATH)
CONFIG_FILE="$SCRIPT_PATH/bin/env.local.config.sh"
if [ ! -f "$CONFIG_FILE" ]; then
	echo "Error: Configuration file not found at $CONFIG_FILE."
	CONFIG_FILE="$SCRIPT_PATH/env.local.config.sh"
fi

source "$CONFIG_FILE"

# Check if the LocalWP script exists
if [ -f "$LOCALWP_SCRIPT_PATH" ]; then
	# Output a message to indicate the script is being processed
	echo "Processing $LOCALWP_SCRIPT_PATH..."

	# Remove the last 5 lines from the script and store it in a variable
	MODIFIED_SCRIPT_CONTENT=$( sed '$d' "$LOCALWP_SCRIPT_PATH" | sed '$d' | sed '$d' | sed '$d' | sed '$d' )

	# Save the modified script to a temp file
	MODIFIED_SCRIPT_PATH="$TMPDIR/modified-local-env.sh"
	rm $MODIFIED_SCRIPT_PATH
	echo "$MODIFIED_SCRIPT_CONTENT" > $MODIFIED_SCRIPT_PATH

	# Execute the modified script
	source $MODIFIED_SCRIPT_PATH

	echo "LocalWP environment loaded with the last 5 lines removed."
else
	echo "Error: LocalWP environment script not found at $LOCALWP_SCRIPT_PATH."
fi
