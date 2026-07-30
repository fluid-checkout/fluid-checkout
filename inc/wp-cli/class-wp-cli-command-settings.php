<?php
defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI commands for Fluid Checkout settings tools.
 */
class FluidCheckout_WPCLI_Command_Settings {

	/**
	 * Export Fluid Checkout settings to a JSON file.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : Path to write the JSON file. Prints to STDOUT when omitted.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fc settings export
	 *     wp fc settings export --file=/tmp/fc-settings.json
	 *
	 * @when after_wp_load
	 *
	 * @param  array  $args        Positional arguments.
	 * @param  array  $assoc_args  Associative arguments.
	 */
	public function export( $args, $assoc_args ) {
		$json = FluidCheckout_Admin_Settings_Tools_Service::instance()->get_export_json();

		// Bail if export failed
		if ( false === $json ) {
			WP_CLI::error( 'Could not generate the settings export JSON.' );
		}

		if ( ! empty( $assoc_args[ 'file' ] ) ) {
			$path = $assoc_args[ 'file' ];
			$result = file_put_contents( $path, $json ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

			// Bail if write failed
			if ( false === $result ) {
				WP_CLI::error( sprintf( 'Could not write export file: %s', $path ) );
			}

			WP_CLI::success( sprintf( 'Settings exported to %s', $path ) );
			return;
		}

		WP_CLI::line( $json );
	}

	/**
	 * Import Fluid Checkout settings from a JSON file.
	 *
	 * Creates an automatic backup before applying settings.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Path to the JSON settings file.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fc settings import --file=/tmp/fc-settings.json
	 *
	 * @when after_wp_load
	 *
	 * @param  array  $args        Positional arguments.
	 * @param  array  $assoc_args  Associative arguments.
	 */
	public function import( $args, $assoc_args ) {
		// Bail if file argument is missing
		if ( empty( $assoc_args[ 'file' ] ) ) {
			WP_CLI::error( 'Please provide a settings file with --file=<path>.' );
		}

		$path = $assoc_args[ 'file' ];

		// Bail if file is not readable
		if ( ! is_readable( $path ) ) {
			WP_CLI::error( sprintf( 'Could not read settings file: %s', $path ) );
		}

		$json = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$result = FluidCheckout_Admin_Settings_Tools_Service::instance()->import_settings_from_json( $json, true );

		// Bail if validation errors
		if ( ! empty( $result[ 'errors' ] ) ) {
			WP_CLI::error( implode( ' ', $result[ 'errors' ] ) );
		}

		WP_CLI::success(
			sprintf(
				'Settings imported. Imported: %d. Skipped: %d. Automatic backup created. License keys and API keys were not changed.',
				(int) $result[ 'imported' ],
				(int) $result[ 'skipped' ]
			)
		);
	}

	/**
	 * Reset Fluid Checkout settings to their defaults.
	 *
	 * Creates an automatic backup before resetting.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fc settings reset
	 *     wp fc settings reset --yes
	 *
	 * @when after_wp_load
	 *
	 * @param  array  $args        Positional arguments.
	 * @param  array  $assoc_args  Associative arguments.
	 */
	public function reset( $args, $assoc_args ) {
		WP_CLI::confirm( 'Are you sure you want to reset Fluid Checkout settings to defaults? An automatic backup will be created first.', $assoc_args );

		$result = FluidCheckout_Admin_Settings_Tools_Service::instance()->reset_settings( true );

		WP_CLI::success(
			sprintf(
				'Settings reset to defaults. Reset: %d. Automatic backup created. License keys and API keys were not changed.',
				(int) $result[ 'reset' ]
			)
		);
	}

	/**
	 * Restore Fluid Checkout settings from the last automatic backup.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fc settings restore
	 *     wp fc settings restore --yes
	 *
	 * @when after_wp_load
	 *
	 * @param  array  $args        Positional arguments.
	 * @param  array  $assoc_args  Associative arguments.
	 */
	public function restore( $args, $assoc_args ) {
		WP_CLI::confirm( 'Are you sure you want to restore Fluid Checkout settings from the automatic backup?', $assoc_args );

		$result = FluidCheckout_Admin_Settings_Tools_Service::instance()->restore_last_backup();

		// Bail if validation errors
		if ( ! empty( $result[ 'errors' ] ) ) {
			WP_CLI::error( implode( ' ', $result[ 'errors' ] ) );
		}

		WP_CLI::success(
			sprintf(
				'Previous settings restored. Restored: %d. Removed: %d. License keys and API keys were not changed.',
				(int) $result[ 'restored' ],
				(int) $result[ 'deleted' ]
			)
		);
	}

}
