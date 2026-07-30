<?php
defined( 'ABSPATH' ) || exit;

/**
 * Service for exporting, importing, and resetting Fluid Checkout settings.
 */
class FluidCheckout_Admin_Settings_Tools_Service extends FluidCheckout {

	/**
	 * Export format version.
	 *
	 * @var string
	 */
	const EXPORT_FORMAT_VERSION = '1';

	/**
	 * Option key for the automatic pre-import/reset backup.
	 *
	 * @var string
	 */
	const BACKUP_OPTION_KEY = 'fc_settings_tools_last_backup';

	/**
	 * How long the automatic backup remains available for restore.
	 *
	 * @var int
	 */
	const BACKUP_TTL = WEEK_IN_SECONDS;



	/**
	 * __construct function.
	 */
	public function __construct() {
		// No hooks — service only.
	}



	/**
	 * Get default option values for managed settings.
	 *
	 * Lite defaults already include Pro / Address Book / VAT via `fc_default_option_values`.
	 * Fluid Checkout product keys (`fc_*`) are managed even when absent from this map.
	 *
	 * @return array
	 */
	public function get_default_option_values() {
		return FluidCheckout_Settings::instance()->get_default_option_values();
	}



	/**
	 * Get troubleshooting option keys.
	 *
	 * @return array
	 */
	public function get_troubleshooting_option_keys() {
		$keys = array(
			'fc_debug_mode',
			'fc_load_unminified_assets',
			'fc_use_enhanced_select_components',
			'fc_fix_zoom_in_form_fields_mobile_devices',
		);

		/**
		 * Filter troubleshooting option keys for settings tools.
		 *
		 * @param  array  $keys  Option keys treated as troubleshooting options.
		 */
		return apply_filters( 'fc_settings_tools_troubleshooting_option_keys', $keys );
	}

	/**
	 * Whether an option key is a troubleshooting option.
	 *
	 * @param  string  $option  Option name.
	 */
	public function is_troubleshooting_option_key( $option ) {
		return in_array( $option, $this->get_troubleshooting_option_keys(), true );
	}

	/**
	 * Whether an option key belongs to a Fluid Checkout product.
	 *
	 * Covers Lite, PRO, Address Book, Google Address Autocomplete, and VAT Assistant.
	 *
	 * @param  string  $option  Option name.
	 */
	public function is_fc_product_option_key( $option ) {
		return 0 === strpos( $option, 'fc_' );
	}

	/**
	 * Get option keys that must never be exported, imported, or reset.
	 *
	 * @return array
	 */
	public function get_excluded_option_keys() {
		$excluded = array(
			// License keys and activation flags
			'fc_pro_license_key',
			'fc_pro_license_key_activated',
			'fc_adb_license_key',
			'fc_adb_license_key_activated',
			'fc_gaa_license_key',
			'fc_gaa_license_key_activated',
			'fc_vat_license_key',
			'fc_vat_license_key_activated',

			// API keys
			'fc_gaa_google_places_api_key',

			// Internal backup storage
			self::BACKUP_OPTION_KEY,
		);

		/**
		 * Filter excluded option keys for settings tools.
		 *
		 * @param  array  $excluded  Option keys that must not be exported, imported, or reset.
		 */
		return apply_filters( 'fc_settings_tools_excluded_option_keys', $excluded );
	}

	/**
	 * Whether an option key is excluded from settings tools.
	 *
	 * @param  string  $option  Option name.
	 */
	public function is_excluded_option_key( $option ) {
		// Bail if in the hardcoded exclude list
		if ( in_array( $option, $this->get_excluded_option_keys(), true ) ) {
			return true;
		}

		// Bail if runtime / site meta option
		if ( preg_match( '/_plugin_activation_time$/', $option ) || preg_match( '/_db_version$/', $option ) ) {
			return true;
		}

		// Bail if dismissed admin notice flags
		if ( 0 === strpos( $option, 'fc_dismissed_notice_' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether an option key can be exported, imported, or reset.
	 *
	 * Fluid Checkout product keys are always eligible, even when that add-on is inactive.
	 * WooCommerce and third-party keys are eligible when present in the active defaults map.
	 *
	 * @param  string  $option  Option name.
	 */
	public function is_managed_option_key( $option ) {
		// Bail if excluded
		if ( $this->is_excluded_option_key( $option ) ) {
			return false;
		}

		// Fluid Checkout product options are always managed
		if ( $this->is_fc_product_option_key( $option ) ) {
			return true;
		}

		// WooCommerce / third-party keys from active defaults
		return array_key_exists( $option, $this->get_default_option_values() );
	}

	/**
	 * Whether an option key should be included in export or import.
	 *
	 * Troubleshooting options are omitted from transfer but remain resettable.
	 *
	 * @param  string  $option  Option name.
	 */
	public function should_include_option_key( $option ) {
		// Bail if not managed
		if ( ! $this->is_managed_option_key( $option ) ) {
			return false;
		}

		// Skip troubleshooting options from export/import (still resettable)
		if ( $this->is_troubleshooting_option_key( $option ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get saved Fluid Checkout product option keys from the database.
	 *
	 * Includes options for inactive add-ons that still have values stored.
	 *
	 * @return array
	 */
	public function get_saved_fc_product_option_keys() {
		global $wpdb;

		$like = $wpdb->esc_like( 'fc_' ) . '%';
		$names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);

		// Bail if query failed
		if ( ! is_array( $names ) ) {
			return array();
		}

		return array_values( array_filter( $names, array( $this, 'is_managed_option_key' ) ) );
	}

	/**
	 * Get managed option keys that can be exported, imported, or reset.
	 *
	 * @param  bool  $for_transfer  When true, omit troubleshooting options used only for reset/backup.
	 * @return array
	 */
	public function get_managed_option_keys( $for_transfer = false ) {
		$keys = array_keys( $this->get_default_option_values() );
		$keys = array_merge( $keys, $this->get_saved_fc_product_option_keys() );
		$keys = array_unique( $keys );

		// Maybe filter for export/import
		if ( $for_transfer ) {
			return array_values( array_filter( $keys, array( $this, 'should_include_option_key' ) ) );
		}

		// Remove excluded keys
		return array_values( array_filter( $keys, array( $this, 'is_managed_option_key' ) ) );
	}



	/**
	 * Check whether an option exists in the database.
	 *
	 * @param  string  $option  Option name.
	 */
	public function option_exists( $option ) {
		$sentinel = new stdClass();
		$value    = get_option( $option, $sentinel );

		return $sentinel !== $value;
	}

	/**
	 * Build a settings snapshot of saved managed options.
	 *
	 * @param  bool  $for_transfer  When true, omit troubleshooting options.
	 * @return array
	 */
	public function get_snapshot_data( $for_transfer = false ) {
		$settings = array();

		foreach ( $this->get_managed_option_keys( $for_transfer ) as $option ) {
			// Only include values saved in the database
			if ( ! $this->option_exists( $option ) ) { continue; }

			$settings[ $option ] = get_option( $option );
		}

		return array(
			'generator'      => 'fluid-checkout',
			'format_version' => self::EXPORT_FORMAT_VERSION,
			'generated_at'   => gmdate( 'c' ),
			'plugins'        => $this->get_active_plugin_versions(),
			'settings'       => $settings,
		);
	}

	/**
	 * Build the export data array.
	 *
	 * @return array
	 */
	public function get_export_data() {
		return $this->get_snapshot_data( true );
	}

	/**
	 * Get versions of active Fluid Checkout plugins.
	 *
	 * @return array
	 */
	public function get_active_plugin_versions() {
		$plugins = array(
			'fluid-checkout' => self::$version,
		);

		$plugin_map = array(
			'fluid-checkout-pro'               => 'FluidCheckout_PRO',
			'fc-address-book'                  => 'FC_AddressBook',
			'fc-google-address-autocomplete'   => 'FC_GoogleAddressAutocomplete',
			'fc-vat-assistant'                 => 'FC_VAT_Assistant',
		);

		foreach ( $plugin_map as $slug => $class_name ) {
			// Bail if class is not available
			if ( ! class_exists( $class_name ) ) { continue; }

			// Bail if version property is not available
			if ( ! property_exists( $class_name, 'version' ) ) { continue; }

			$plugins[ $slug ] = $class_name::$version;
		}

		return $plugins;
	}

	/**
	 * Get export data as a JSON string.
	 *
	 * @return string|false
	 */
	public function get_export_json() {
		return wp_json_encode( $this->get_export_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}



	/**
	 * Import settings from a decoded data array.
	 *
	 * @param  array  $data           Decoded export data.
	 * @param  bool   $create_backup  Whether to create an automatic backup before applying.
	 * @return array{ imported: int, skipped: int, errors: array, backup_created: bool }
	 */
	public function import_settings( $data, $create_backup = false ) {
		$result = array(
			'imported'       => 0,
			'skipped'        => 0,
			'errors'         => array(),
			'backup_created' => false,
		);

		// Bail if data is invalid
		if ( ! is_array( $data ) || 'fluid-checkout' !== ( $data[ 'generator' ] ?? '' ) || ! array_key_exists( 'settings', $data ) || ! is_array( $data[ 'settings' ] ) ) {
			$result[ 'errors' ][] = __( 'Invalid settings file. Expected a Fluid Checkout settings export.', 'fluid-checkout' );
			return $result;
		}

		// Maybe create automatic backup before changing settings
		if ( $create_backup ) {
			$this->create_auto_backup( 'import' );
			$result[ 'backup_created' ] = true;
		}

		foreach ( $data[ 'settings' ] as $option => $value ) {
			$option = sanitize_text_field( $option );

			// Skip unknown, excluded, or non-transferable keys
			if ( ! $this->should_include_option_key( $option ) ) {
				$result[ 'skipped' ]++;
				continue;
			}

			update_option( $option, $value );
			$result[ 'imported' ]++;
		}

		// Clear caches after import
		wp_cache_flush();

		return $result;
	}

	/**
	 * Import settings from a JSON string.
	 *
	 * @param  string  $json           JSON string.
	 * @param  bool    $create_backup  Whether to create an automatic backup before applying.
	 * @return array{ imported: int, skipped: int, errors: array, backup_created: bool }
	 */
	public function import_settings_from_json( $json, $create_backup = false ) {
		$data = json_decode( $json, true );

		// Bail if JSON is invalid
		if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
			return array(
				'imported'       => 0,
				'skipped'        => 0,
				'errors'         => array( __( 'Could not parse the settings file. Make sure it is valid JSON.', 'fluid-checkout' ) ),
				'backup_created' => false,
			);
		}

		return $this->import_settings( $data, $create_backup );
	}



	/**
	 * Reset managed settings to defaults by deleting saved options.
	 *
	 * @param  bool  $create_backup  Whether to create an automatic backup before resetting.
	 * @return array{ reset: int, skipped: int, backup_created: bool }
	 */
	public function reset_settings( $create_backup = false ) {
		$result = array(
			'reset'          => 0,
			'skipped'        => 0,
			'backup_created' => false,
		);

		// Maybe create automatic backup before resetting
		if ( $create_backup ) {
			$this->create_auto_backup( 'reset' );
			$result[ 'backup_created' ] = true;
		}

		foreach ( $this->get_managed_option_keys() as $option ) {
			// Only delete options that exist in the database
			if ( ! $this->option_exists( $option ) ) {
				$result[ 'skipped' ]++;
				continue;
			}

			delete_option( $option );
			$result[ 'reset' ]++;
		}

		// Clear caches after reset
		wp_cache_flush();

		return $result;
	}



	/**
	 * Create an automatic backup of the current managed settings.
	 *
	 * @param  string  $created_by  Action that triggered the backup (`import` or `reset`).
	 * @return array
	 */
	public function create_auto_backup( $created_by = 'import' ) {
		$created_by = in_array( $created_by, array( 'import', 'reset' ), true ) ? $created_by : 'import';

		$backup = array(
			'created_at' => gmdate( 'c' ),
			'created_by' => $created_by,
			'data'       => $this->get_snapshot_data(),
		);

		update_option( self::BACKUP_OPTION_KEY, $backup, false );

		return $backup;
	}

	/**
	 * Whether a backup array is structurally valid.
	 *
	 * @param  mixed  $backup  Backup data.
	 */
	public function is_valid_backup( $backup ) {
		return is_array( $backup )
			&& ! empty( $backup[ 'created_at' ] )
			&& isset( $backup[ 'data' ] )
			&& is_array( $backup[ 'data' ] )
			&& isset( $backup[ 'data' ][ 'settings' ] )
			&& is_array( $backup[ 'data' ][ 'settings' ] );
	}

	/**
	 * Whether a backup has expired.
	 *
	 * @param  array  $backup  Backup data.
	 */
	public function is_backup_expired( $backup ) {
		// Bail if created_at is missing
		if ( empty( $backup[ 'created_at' ] ) ) { return true; }

		$created_at = strtotime( $backup[ 'created_at' ] );

		// Bail if timestamp is invalid
		if ( false === $created_at ) { return true; }

		return ( time() - $created_at ) > self::BACKUP_TTL;
	}

	/**
	 * Get the last automatic backup, or null when missing/invalid/expired.
	 *
	 * @return array|null
	 */
	public function get_last_backup() {
		$backup = get_option( self::BACKUP_OPTION_KEY, null );

		// Bail if backup is missing or invalid
		if ( ! $this->is_valid_backup( $backup ) ) {
			return null;
		}

		// Maybe clear and ignore expired backups
		if ( $this->is_backup_expired( $backup ) ) {
			$this->clear_last_backup();
			return null;
		}

		return $backup;
	}

	/**
	 * Delete the automatic backup.
	 */
	public function clear_last_backup() {
		delete_option( self::BACKUP_OPTION_KEY );
	}

	/**
	 * Restore managed settings from the last automatic backup.
	 *
	 * Re-applies every managed value stored in the backup (including Fluid Checkout
	 * product keys that only existed via DB scan at backup time), then deletes
	 * currently managed options that were not saved at backup time.
	 *
	 * @return array{ restored: int, deleted: int, errors: array }
	 */
	public function restore_last_backup() {
		$result = array(
			'restored' => 0,
			'deleted'  => 0,
			'errors'   => array(),
		);

		$backup = $this->get_last_backup();

		// Bail if no backup is available
		if ( null === $backup ) {
			$result[ 'errors' ][] = __( 'No settings backup is available to restore.', 'fluid-checkout' );
			return $result;
		}

		$settings = $backup[ 'data' ][ 'settings' ];

		// Re-apply backup values even when those options no longer exist in the DB
		// (e.g. after reset removed inactive add-on keys from the managed-key scan).
		foreach ( $settings as $option => $value ) {
			// Bail if option is not managed (secrets, runtime meta, unknown keys)
			if ( ! $this->is_managed_option_key( $option ) ) { continue; }

			update_option( $option, $value );
			$result[ 'restored' ]++;
		}

		// Remove managed options that were not saved when the backup was created
		foreach ( $this->get_managed_option_keys() as $option ) {
			// Bail if option is present in the backup (already restored above)
			if ( array_key_exists( $option, $settings ) ) { continue; }

			// Bail if option does not exist in the database
			if ( ! $this->option_exists( $option ) ) { continue; }

			delete_option( $option );
			$result[ 'deleted' ]++;
		}

		// Clear caches after restore
		wp_cache_flush();

		return $result;
	}

}

FluidCheckout_Admin_Settings_Tools_Service::instance();
