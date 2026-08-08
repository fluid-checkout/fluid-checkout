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
	 * How long a pending import preview remains available.
	 *
	 * @var int
	 */
	const IMPORT_PREVIEW_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Transient prefix for pending import previews (suffix with user ID).
	 *
	 * @var string
	 */
	const IMPORT_PREVIEW_TRANSIENT = 'fc_settings_tools_import_preview';

	/**
	 * Max characters when formatting diff values for display.
	 *
	 * @var int
	 */
	const DIFF_VALUE_MAX_LENGTH = 120;

	/**
	 * Max upload size for settings import files (1 MB).
	 *
	 * @var int
	 */
	const IMPORT_FILE_MAX_BYTES = 1048576;



	/**
	 * __construct function.
	 */
	public function __construct() {
		// No hooks — service only.
	}



	/**
	 * Whether a settings import file exceeds the allowed size.
	 *
	 * @param  int  $file_size  File size in bytes.
	 */
	public function import_file_exceeds_max_bytes( $file_size ) {
		return (int) $file_size > self::IMPORT_FILE_MAX_BYTES;
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
		 */
		return apply_filters( 'fc_settings_tools_troubleshooting_option_keys', $keys );
	}

	/**
	 * Whether an option key is a troubleshooting option.
	 *
	 * Includes Lite keys from `get_troubleshooting_option_keys()` and add-on debug
	 * options such as `fc_gaa_debug_mode` and `fc_paddle_load_unminified_assets`.
	 *
	 * @param  string  $option  Option name.
	 */
	public function is_troubleshooting_option_key( $option ) {
		if ( in_array( $option, $this->get_troubleshooting_option_keys(), true ) ) {
			return true;
		}

		// Add-on debug / unminified asset toggles (Fluid Checkout product keys only)
		if ( $this->is_fc_product_option_key( $option )
			&& (bool) preg_match( '/_(debug_mode|load_unminified_assets)$/', $option ) ) {
			return true;
		}

		return false;
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
	 * Whether an option key is excluded from settings tools.
	 *
	 * Excludes backup storage, secrets, install metadata, dismissed admin notices,
	 * per-site diagnostic logs, license updater credentials, and admin UI state
	 * so those values survive export, import, and reset.
	 *
	 * @param  string  $option  Option name.
	 */
	public function is_excluded_option_key( $option ) {
		$excluded = self::BACKUP_OPTION_KEY === $option
			|| 'fc_pro_address_book_migration' === $option
			|| (bool) preg_match( '/_license_key(_activated)?$/', $option )
			|| (bool) preg_match( '/_api_key$/', $option )
			|| (bool) preg_match( '/_consumer_secret$/', $option )
			|| (bool) preg_match( '/_consumer_key$/', $option )
			|| (bool) preg_match( '/_plugin_activation_time$/', $option )
			|| (bool) preg_match( '/_db_version$/', $option )
			|| (bool) preg_match( '/_show_db_update_notice$/', $option )
			|| (bool) preg_match( '/_webhook_log$/', $option )
			|| false !== strpos( $option, '_dismissed_notice_' )
			|| false !== strpos( $option, '_setup_wizard_' );

		/**
		 * Filter whether an option key is excluded from settings tools.
		 *
		 * @param  string  $option  Option name.
		 */
		return true === apply_filters( 'fc_settings_tools_is_excluded_option_key', $excluded, $option );
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
	 * Whether an option key can be exported or imported.
	 *
	 * Troubleshooting options are omitted from transfer but remain resettable.
	 *
	 * @param  string  $option  Option name.
	 */
	public function is_transferable_option_key( $option ) {
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
	 * Whether an option key should be included in export or import.
	 *
	 * Prefer `is_transferable_option_key()`.
	 *
	 * @param  string  $option  Option name.
	 */
	public function should_include_option_key( $option ) {
		return $this->is_transferable_option_key( $option );
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
		// Prefer get_transferable_option_keys() for export/import callers
		if ( $for_transfer ) {
			return $this->get_transferable_option_keys();
		}

		$keys = array_keys( $this->get_default_option_values() );
		$keys = array_merge( $keys, $this->get_saved_fc_product_option_keys() );
		$keys = array_unique( $keys );

		return array_values( array_filter( $keys, array( $this, 'is_managed_option_key' ) ) );
	}

	/**
	 * Get option keys included in export and import (managed, excluding troubleshooting).
	 *
	 * @return array
	 */
	public function get_transferable_option_keys() {
		return array_values( array_filter( $this->get_managed_option_keys(), array( $this, 'is_transferable_option_key' ) ) );
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
		$option_keys = $for_transfer ? $this->get_transferable_option_keys() : $this->get_managed_option_keys();
		$settings = array();

		foreach ( $option_keys as $option ) {
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
	 * Normalize an import mode string.
	 *
	 * @param  string  $mode  Requested mode.
	 * @return string  `update` or `replace`.
	 */
	public function normalize_import_mode( $mode ) {
		return ( 'replace' === $mode ) ? 'replace' : 'update';
	}

	/**
	 * Whether the export payload structure is valid.
	 *
	 * @param  mixed  $data  Decoded export data.
	 */
	public function is_valid_import_data( $data ) {
		return is_array( $data )
			&& 'fluid-checkout' === ( $data[ 'generator' ] ?? '' )
			&& self::EXPORT_FORMAT_VERSION === (string) ( $data[ 'format_version' ] ?? '' )
			&& array_key_exists( 'settings', $data )
			&& is_array( $data[ 'settings' ] );
	}

	/**
	 * Format a value for diff display (scalars as string; arrays/objects as truncated JSON).
	 *
	 * @param  mixed  $value  Option value.
	 * @return string
	 */
	public function format_diff_value( $value ) {
		if ( is_bool( $value ) ) {
			$formatted = $value ? 'true' : 'false';
		}
		elseif ( is_scalar( $value ) || null === $value ) {
			$formatted = (string) $value;
		}
		else {
			$formatted = wp_json_encode( $value );
			if ( false === $formatted ) {
				$formatted = '';
			}
		}

		if ( strlen( $formatted ) > self::DIFF_VALUE_MAX_LENGTH ) {
			return substr( $formatted, 0, self::DIFF_VALUE_MAX_LENGTH - 1 ) . '…';
		}

		return $formatted;
	}

	/**
	 * Whether values are considered equal for import diff purposes.
	 *
	 * @param  mixed  $a  First value.
	 * @param  mixed  $b  Second value.
	 */
	public function values_are_equal( $a, $b ) {
		return maybe_serialize( $a ) === maybe_serialize( $b );
	}

	/**
	 * Build a diff between an import payload and the current site settings.
	 *
	 * @param  array   $data  Decoded export data.
	 * @param  string  $mode  `update` or `replace`.
	 * @return array{ mode: string, changed: array, added: array, will_clear: array, unchanged_count: int, skipped_count: int, errors: array }
	 */
	public function get_import_diff( $data, $mode = 'update' ) {
		$mode = $this->normalize_import_mode( $mode );
		$result = array(
			'mode'            => $mode,
			'changed'         => array(),
			'added'           => array(),
			'will_clear'      => array(),
			'unchanged_count' => 0,
			'skipped_count'   => 0,
			'errors'          => array(),
		);

		// Bail if data is invalid
		if ( ! $this->is_valid_import_data( $data ) ) {
			$result[ 'errors' ][] = __( 'Invalid settings file. Expected a Fluid Checkout settings export.', 'fluid-checkout' );
			return $result;
		}

		$file_transfer_keys = array();

		foreach ( $data[ 'settings' ] as $option => $value ) {
			$option = sanitize_text_field( $option );

			// Skip unknown, excluded, or non-transferable keys
			if ( ! $this->is_transferable_option_key( $option ) ) {
				$result[ 'skipped_count' ]++;
				continue;
			}

			$file_transfer_keys[ $option ] = true;

			// Option not saved on this site yet
			if ( ! $this->option_exists( $option ) ) {
				$result[ 'added' ][ $option ] = array(
					'from' => null,
					'to'   => $this->format_diff_value( $value ),
				);
				continue;
			}

			$current = get_option( $option );

			// Unchanged
			if ( $this->values_are_equal( $current, $value ) ) {
				$result[ 'unchanged_count' ]++;
				continue;
			}

			$result[ 'changed' ][ $option ] = array(
				'from' => $this->format_diff_value( $current ),
				'to'   => $this->format_diff_value( $value ),
			);
		}

		// Replace mode: list managed keys that would be cleared and not restored from the file
		if ( 'replace' === $mode ) {
			foreach ( $this->get_managed_option_keys() as $option ) {
				// Bail if option is not currently saved
				if ( ! $this->option_exists( $option ) ) { continue; }

				// Bail if file will restore this transferable key
				if ( isset( $file_transfer_keys[ $option ] ) ) { continue; }

				// Troubleshooting keys are cleared by reset but never restored from transfer files
				$result[ 'will_clear' ][] = $option;
			}
		}

		return $result;
	}

	/**
	 * Get an empty import result structure.
	 *
	 * @param  string  $mode    `update` or `replace`.
	 * @param  array   $errors  Optional error messages.
	 * @return array{ imported: int, skipped: int, reset: int, mode: string, errors: array, backup_created: bool }
	 */
	public function get_empty_import_result( $mode = 'update', $errors = array() ) {
		return array(
			'imported'       => 0,
			'skipped'        => 0,
			'reset'          => 0,
			'mode'           => $this->normalize_import_mode( $mode ),
			'errors'         => $errors,
			'backup_created' => false,
		);
	}

	/**
	 * Import settings from a decoded data array.
	 *
	 * @param  array   $data           Decoded export data.
	 * @param  bool    $create_backup  Whether to create an automatic backup before applying.
	 * @param  string  $mode           `update` (merge) or `replace` (reset then apply).
	 * @return array{ imported: int, skipped: int, reset: int, mode: string, errors: array, backup_created: bool }
	 */
	public function import_settings( $data, $create_backup = false, $mode = 'update' ) {
		$mode = $this->normalize_import_mode( $mode );
		$result = $this->get_empty_import_result( $mode );

		// Bail if data is invalid
		if ( ! $this->is_valid_import_data( $data ) ) {
			$result[ 'errors' ][] = __( 'Invalid settings file. Expected a Fluid Checkout settings export.', 'fluid-checkout' );
			return $result;
		}

		// Maybe create automatic backup before changing settings
		if ( $create_backup ) {
			$this->create_auto_backup( 'import' );
			$result[ 'backup_created' ] = true;
		}

		// Replace mode: clear managed settings first (backup already created above when requested)
		if ( 'replace' === $mode ) {
			$reset_result = $this->reset_settings( false );
			$result[ 'reset' ] = (int) $reset_result[ 'reset' ];
		}

		foreach ( $data[ 'settings' ] as $option => $value ) {
			$option = sanitize_text_field( $option );

			// Skip unknown, excluded, or non-transferable keys
			if ( ! $this->is_transferable_option_key( $option ) ) {
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
	 * Decode and validate a settings import JSON string.
	 *
	 * @param  string  $json  JSON string.
	 * @return array|\WP_Error  Decoded export data, or WP_Error on failure.
	 */
	public function decode_import_json( $json ) {
		$data = json_decode( $json, true );

		// Bail if JSON is invalid
		if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error(
				'fc_settings_tools_invalid_json',
				__( 'Could not parse the settings file. Make sure it is valid JSON.', 'fluid-checkout' )
			);
		}

		// Bail if payload structure is invalid
		if ( ! $this->is_valid_import_data( $data ) ) {
			return new WP_Error(
				'fc_settings_tools_invalid_payload',
				__( 'Invalid settings file. Expected a Fluid Checkout settings export.', 'fluid-checkout' )
			);
		}

		return $data;
	}

	/**
	 * Import settings from a JSON string.
	 *
	 * @param  string  $json           JSON string.
	 * @param  bool    $create_backup  Whether to create an automatic backup before applying.
	 * @param  string  $mode           `update` or `replace`.
	 * @return array{ imported: int, skipped: int, reset: int, mode: string, errors: array, backup_created: bool }
	 */
	public function import_settings_from_json( $json, $create_backup = false, $mode = 'update' ) {
		$data = $this->decode_import_json( $json );

		// Bail if JSON or payload is invalid
		if ( is_wp_error( $data ) ) {
			return $this->get_empty_import_result( $mode, array( $data->get_error_message() ) );
		}

		return $this->import_settings( $data, $create_backup, $mode );
	}

	/**
	 * Get the pending import preview transient key for a user.
	 *
	 * @param  int  $user_id  User ID.
	 */
	public function get_import_preview_transient_key( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		return self::IMPORT_PREVIEW_TRANSIENT . '_' . $user_id;
	}

	/**
	 * Store a pending import preview for the current user.
	 *
	 * @param  array  $preview  Preview payload with `json`, `mode`, and `diff`.
	 */
	public function set_import_preview( $preview ) {
		set_transient( $this->get_import_preview_transient_key(), $preview, self::IMPORT_PREVIEW_TTL );
	}

	/**
	 * Get the pending import preview for the current user, or null.
	 *
	 * @return array|null
	 */
	public function get_import_preview() {
		$preview = get_transient( $this->get_import_preview_transient_key() );

		// Bail if preview is missing or invalid
		if ( ! is_array( $preview ) || empty( $preview[ 'json' ] ) || empty( $preview[ 'mode' ] ) || empty( $preview[ 'diff' ] ) || ! is_array( $preview[ 'diff' ] ) ) {
			return null;
		}

		$preview[ 'mode' ] = $this->normalize_import_mode( $preview[ 'mode' ] );
		return $preview;
	}

	/**
	 * Clear the pending import preview for the current user.
	 */
	public function clear_import_preview() {
		delete_transient( $this->get_import_preview_transient_key() );
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
