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
		$json = FluidCheckout_AdminSettingsTools_Service::instance()->get_export_json();

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
	 * Creates an automatic backup before applying settings unless --dry-run is used.
	 * Default mode updates matching settings only. Use --replace to clear saved
	 * Fluid Checkout settings first, then apply the file.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Path to the JSON settings file.
	 *
	 * [--replace]
	 * : Clear saved Fluid Checkout settings, then apply the file.
	 *
	 * [--dry-run]
	 * : Show a diff summary without changing settings.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fc settings import --file=/tmp/fc-settings.json
	 *     wp fc settings import --file=/tmp/fc-settings.json --replace
	 *     wp fc settings import --file=/tmp/fc-settings.json --replace --yes
	 *     wp fc settings import --file=/tmp/fc-settings.json --dry-run
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

		$file_size = filesize( $path );

		// Bail if file size could not be read
		if ( false === $file_size ) {
			WP_CLI::error( sprintf( 'Could not read settings file: %s', $path ) );
		}

		$service = FluidCheckout_AdminSettingsTools_Service::instance();

		// Bail if file exceeds the allowed size
		if ( $service->import_file_exceeds_max_bytes( $file_size ) ) {
			WP_CLI::error(
				sprintf(
					'The settings file is too large. Maximum size is %s.',
					size_format( FluidCheckout_AdminSettingsTools_Service::IMPORT_FILE_MAX_BYTES )
				)
			);
		}

		$mode = ! empty( $assoc_args[ 'replace' ] ) ? 'replace' : 'update';
		$json = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = $service->decode_import_json( $json );

		// Bail if JSON or payload is invalid
		if ( is_wp_error( $data ) ) {
			WP_CLI::error( $data->get_error_message() );
		}

		$diff = $service->get_import_diff( $data, $mode );

		// Bail if validation errors
		if ( ! empty( $diff[ 'errors' ] ) ) {
			WP_CLI::error( implode( ' ', $diff[ 'errors' ] ) );
		}

		WP_CLI::log( sprintf(
			'Mode: %s. Changed: %d. Added: %d. Unchanged: %d. Skipped: %d. Will clear: %d.',
			$mode,
			count( $diff[ 'changed' ] ),
			count( $diff[ 'added' ] ),
			(int) $diff[ 'unchanged_count' ],
			(int) $diff[ 'skipped_count' ],
			count( $diff[ 'will_clear' ] )
		) );

		// Maybe stop after dry-run
		if ( ! empty( $assoc_args[ 'dry-run' ] ) ) {
			WP_CLI::success( 'Dry run complete. No settings were changed.' );
			return;
		}

		// Maybe confirm replace mode before changing settings
		if ( 'replace' === $mode ) {
			WP_CLI::confirm( 'Replace all Fluid Checkout settings from this file?', $assoc_args );
		}

		$result = $service->import_settings( $data, true, $mode );

		// Bail if validation errors
		if ( ! empty( $result[ 'errors' ] ) ) {
			WP_CLI::error( implode( ' ', $result[ 'errors' ] ) );
		}

		if ( 'replace' === $result[ 'mode' ] ) {
			WP_CLI::success(
				sprintf(
					'Settings replaced. Applied: %d. Skipped: %d.',
					(int) $result[ 'imported' ],
					(int) $result[ 'skipped' ]
				)
			);
			return;
		}

		WP_CLI::success(
			sprintf(
				'Settings updated. Applied: %d. Skipped: %d.',
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
		WP_CLI::confirm( 'Reset Fluid Checkout settings to defaults?', $assoc_args );

		$result = FluidCheckout_AdminSettingsTools_Service::instance()->reset_settings( true );

		WP_CLI::success(
			sprintf(
				'Settings reset to defaults. Reset: %d.',
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
		WP_CLI::confirm( 'Restore Fluid Checkout settings from the automatic backup?', $assoc_args );

		$result = FluidCheckout_AdminSettingsTools_Service::instance()->restore_last_backup();

		// Bail if validation errors
		if ( ! empty( $result[ 'errors' ] ) ) {
			WP_CLI::error( implode( ' ', $result[ 'errors' ] ) );
		}

		WP_CLI::success(
			sprintf(
				'Previous settings restored. Restored: %d. Removed: %d.',
				(int) $result[ 'restored' ],
				(int) $result[ 'deleted' ]
			)
		);
	}

}
