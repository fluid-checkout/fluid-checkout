<?php
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit test: FluidCheckout_Admin_Settings_Tools_Service.
 */
class Admin_Settings_Tools_Service_Test extends TestCase {
	use OptionsTestClassTrait;

	/**
	 * SUMMARY OF TESTS
	 *
	 * Option keys
	 * Test: Managed option keys: exclude secrets and runtime meta.
	 * Test: Excluded option keys: secrets by pattern and filter extensions.
	 * Test: Troubleshooting and transferable option keys are identified.
	 * Test: WooCommerce options in defaults map: included in managed keys.
	 * Test: Fluid Checkout product keys are managed without being in the defaults map.
	 * Test: Backup option key is excluded from managed keys.
	 *
	 * Export
	 * Test: Export: includes only saved managed values.
	 * Test: Export: omits secrets even when present in the database.
	 * Test: Export: omits troubleshooting options.
	 * Test: Export JSON: valid structure and metadata.
	 * Test: Export: includes inactive Fluid Checkout product settings from the database.
	 *
	 * Import
	 * Test: Import: applies known managed settings.
	 * Test: Import: skips troubleshooting options.
	 * Test: Import: skips unknown keys and secrets.
	 * Test: Import: rejects invalid payload, unsupported format version, and invalid JSON.
	 * Test: Import: preserves existing secrets.
	 * Test: Import: applies Fluid Checkout product settings even when the add-on is inactive.
	 * Test: Import with backup: creates backup before applying and invalid import does not replace backup.
	 * Test: Update import: leaves extra local keys unchanged.
	 * Test: Replace import: removes extra local keys then applies the file.
	 * Test: Import diff: changed, added, unchanged, and will_clear for replace.
	 * Test: Import diff: secrets and invalid payloads are skipped or errored without values.
	 * Test: Invalid replace import: does not create a backup or reset settings.
	 * Test: Round-trip: export then import restores saved settings.
	 *
	 * Restore
	 * Test: Auto-backup: stores managed values including troubleshooting and omits secrets.
	 * Test: Auto-backup: expired backups are cleared and unavailable.
	 * Test: Reset with backup: creates backup then restore undoes the reset.
	 * Test: Restore after reset: restores inactive Fluid Checkout product settings not in defaults.
	 * Test: Restore after import: restores previous values and removes imported-only keys.
	 * Test: Restore: leaves secrets unchanged and errors when no backup exists.
	 *
	 * Reset
	 * Test: Reset: deletes managed saved options and restores defaults via getter.
	 * Test: Reset: leaves secrets and runtime meta in place.
	 * Test: Reset: deletes inactive Fluid Checkout product settings from the database.
	 */



	/**
	 * Settings tools service instance.
	 *
	 * @var FluidCheckout_Admin_Settings_Tools_Service
	 */
	protected $service;

	/**
	 * Per-test setup.
	 */
	public function setUpInstance() {
		$this->service = FluidCheckout_Admin_Settings_Tools_Service::instance();
		$this->track_option( FluidCheckout_Admin_Settings_Tools_Service::BACKUP_OPTION_KEY );
		$this->service->clear_last_backup();
	}



	/**
	 * Assert an option does not exist in the database.
	 *
	 * @param  string  $option  Option name.
	 */
	protected function assert_option_does_not_exist( $option ) {
		$sentinel = new stdClass();
		$this->assertSame( $sentinel, get_option( $option, $sentinel ), sprintf( 'Option "%s" should not exist in the database.', $option ) );
	}

	/**
	 * Assert an option exists in the database.
	 *
	 * @param  string  $option  Option name.
	 */
	protected function assert_option_exists( $option ) {
		$sentinel = new stdClass();
		$this->assertNotSame( $sentinel, get_option( $option, $sentinel ), sprintf( 'Option "%s" should exist in the database.', $option ) );
	}



	// Option keys

	/**
	 * Test: Managed option keys: exclude secrets and runtime meta.
	 */
	public function test_managed_option_keys_exclude_secrets_and_runtime_meta() {
		$managed = $this->service->get_managed_option_keys();

		$this->assertIsArray( $managed );
		$this->assertNotEmpty( $managed );
		$this->assertContains( 'fc_checkout_layout', $managed );
		$this->assertNotContains( 'fc_pro_license_key', $managed );
		$this->assertNotContains( 'fc_gaa_google_places_api_key', $managed );
		$this->assertNotContains( 'fc_plugin_activation_time', $managed );
		$this->assertNotContains( 'fc_db_version', $managed );
	}

	/**
	 * Test: Excluded option keys: secrets by pattern and filter extensions.
	 */
	public function test_excluded_option_keys_hardcoded_and_filterable() {
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_pro_license_key' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_pro_license_key_activated' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_adb_license_key' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_gaa_license_key' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_vat_license_key' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_paddle_license_key' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_paddle_license_key_activated' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_gaa_google_places_api_key' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_plugin_activation_time' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_pro_db_version' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_dismissed_notice_review_request_timed' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_pro_dismissed_notice_review_request_timed' ) );
		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_adb_dismissed_notice_review_request_timed' ) );
		$this->assertFalse( $this->service->is_excluded_option_key( 'fc_checkout_layout' ) );

		$filter = function( $excluded, $option ) {
			return 'fc_checkout_layout' === $option ? true : $excluded;
		};
		add_filter( 'fc_settings_tools_is_excluded_option_key', $filter, 10, 2 );

		$this->assertTrue( $this->service->is_excluded_option_key( 'fc_checkout_layout' ) );

		remove_filter( 'fc_settings_tools_is_excluded_option_key', $filter );
		$this->assertFalse( $this->service->is_excluded_option_key( 'fc_checkout_layout' ) );
	}

	/**
	 * Test: Troubleshooting and transferable option keys are identified.
	 */
	public function test_troubleshooting_and_transferable_option_keys_are_identified() {
		$this->assertTrue( $this->service->is_troubleshooting_option_key( 'fc_debug_mode' ) );
		$this->assertTrue( $this->service->is_troubleshooting_option_key( 'fc_load_unminified_assets' ) );
		$this->assertTrue( $this->service->is_troubleshooting_option_key( 'fc_use_enhanced_select_components' ) );
		$this->assertTrue( $this->service->is_troubleshooting_option_key( 'fc_fix_zoom_in_form_fields_mobile_devices' ) );
		$this->assertFalse( $this->service->is_troubleshooting_option_key( 'fc_checkout_layout' ) );
		$this->assertFalse( $this->service->is_transferable_option_key( 'fc_debug_mode' ) );
		$this->assertFalse( $this->service->is_transferable_option_key( 'fc_use_enhanced_select_components' ) );
		$this->assertTrue( $this->service->is_transferable_option_key( 'fc_checkout_layout' ) );
		$this->assertTrue( $this->service->is_transferable_option_key( 'woocommerce_checkout_phone_field' ) );

		// Alias kept for backward compatibility
		$this->assertSame(
			$this->service->is_transferable_option_key( 'fc_checkout_layout' ),
			$this->service->should_include_option_key( 'fc_checkout_layout' )
		);
	}

	/**
	 * Test: WooCommerce options in defaults map: included in managed keys.
	 */
	public function test_woocommerce_options_in_defaults_are_managed() {
		$managed = $this->service->get_managed_option_keys();

		$this->assertContains( 'woocommerce_checkout_phone_field', $managed );
		$this->assertContains( 'woocommerce_checkout_company_field', $managed );

		$this->set_tracked_option( 'woocommerce_checkout_phone_field', 'optional' );

		$data = $this->service->get_export_data();
		$this->assertSame( 'optional', $data[ 'settings' ][ 'woocommerce_checkout_phone_field' ] );

		$result = $this->service->import_settings( array(
			'generator'      => 'fluid-checkout',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'woocommerce_checkout_phone_field' => 'required',
			),
		) );

		$this->assertSame( 1, $result[ 'imported' ] );
		$this->assertSame( 'required', get_option( 'woocommerce_checkout_phone_field' ) );

		$this->service->reset_settings();
		$this->assert_option_does_not_exist( 'woocommerce_checkout_phone_field' );
	}

	/**
	 * Test: Fluid Checkout product keys are managed without being in the defaults map.
	 */
	public function test_fc_product_keys_managed_without_defaults_map_entry() {
		$defaults = $this->service->get_default_option_values();
		$this->assertArrayNotHasKey( 'fc_gaa_enabled', $defaults );

		$this->assertTrue( $this->service->is_managed_option_key( 'fc_gaa_enabled' ) );
		$this->assertTrue( $this->service->is_transferable_option_key( 'fc_gaa_enabled' ) );
		$this->assertFalse( $this->service->is_transferable_option_key( 'fc_gaa_google_places_api_key' ) );

		$this->set_tracked_option( 'fc_gaa_enabled', 'yes' );
		$this->set_tracked_option( 'fc_gaa_google_places_api_key', 'api-should-stay' );

		$export = $this->service->get_export_data();
		$this->assertSame( 'yes', $export[ 'settings' ][ 'fc_gaa_enabled' ] );
		$this->assertArrayNotHasKey( 'fc_gaa_google_places_api_key', $export[ 'settings' ] );

		$result = $this->service->import_settings( array(
			'generator'      => 'fluid-checkout',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'fc_gaa_enabled'               => 'no',
				'fc_gaa_google_places_api_key' => 'should-not-import',
			),
		) );

		$this->assertSame( 1, $result[ 'imported' ] );
		$this->assertSame( 1, $result[ 'skipped' ] );
		$this->assertSame( 'no', get_option( 'fc_gaa_enabled' ) );
		$this->assertSame( 'api-should-stay', get_option( 'fc_gaa_google_places_api_key' ) );

		$this->service->reset_settings();
		$this->assert_option_does_not_exist( 'fc_gaa_enabled' );
		$this->assertSame( 'api-should-stay', get_option( 'fc_gaa_google_places_api_key' ) );
	}

	/**
	 * Test: Backup option key is excluded from managed keys.
	 */
	public function test_backup_option_key_is_excluded_from_managed_keys() {
		$this->assertTrue( $this->service->is_excluded_option_key( FluidCheckout_Admin_Settings_Tools_Service::BACKUP_OPTION_KEY ) );
		$this->assertNotContains( FluidCheckout_Admin_Settings_Tools_Service::BACKUP_OPTION_KEY, $this->service->get_managed_option_keys() );
	}



	// Export

	/**
	 * Test: Export: includes only saved managed values.
	 */
	public function test_export_includes_only_saved_managed_values() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_enable_dark_mode_styles', 'yes' );

		$data = $this->service->get_export_data();

		$this->assertArrayHasKey( 'settings', $data );
		$this->assertSame( 'single-step', $data[ 'settings' ][ 'fc_checkout_layout' ] );
		$this->assertSame( 'yes', $data[ 'settings' ][ 'fc_enable_dark_mode_styles' ] );

		// Unsaved managed keys should not appear
		$this->assertArrayNotHasKey( 'fc_enable_checkout_progress_bar', $data[ 'settings' ] );
	}

	/**
	 * Test: Export: omits secrets even when present in the database.
	 */
	public function test_export_omits_secrets_even_when_saved() {
		$this->set_tracked_option( 'fc_checkout_layout', 'multi-step' );
		$this->set_tracked_option( 'fc_pro_license_key', 'secret-license' );
		$this->set_tracked_option( 'fc_gaa_google_places_api_key', 'secret-api-key' );
		$this->set_tracked_option( 'fc_plugin_activation_time', time() );

		$data = $this->service->get_export_data();

		$this->assertArrayHasKey( 'fc_checkout_layout', $data[ 'settings' ] );
		$this->assertArrayNotHasKey( 'fc_pro_license_key', $data[ 'settings' ] );
		$this->assertArrayNotHasKey( 'fc_gaa_google_places_api_key', $data[ 'settings' ] );
		$this->assertArrayNotHasKey( 'fc_plugin_activation_time', $data[ 'settings' ] );
	}

	/**
	 * Test: Export: omits troubleshooting options.
	 */
	public function test_export_omits_troubleshooting_options() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_debug_mode', 'yes' );
		$this->set_tracked_option( 'fc_load_unminified_assets', 'yes' );
		$this->set_tracked_option( 'fc_use_enhanced_select_components', 'yes' );
		$this->set_tracked_option( 'fc_fix_zoom_in_form_fields_mobile_devices', 'no' );

		$data = $this->service->get_export_data();

		$this->assertSame( 'single-step', $data[ 'settings' ][ 'fc_checkout_layout' ] );
		$this->assertArrayNotHasKey( 'fc_debug_mode', $data[ 'settings' ] );
		$this->assertArrayNotHasKey( 'fc_load_unminified_assets', $data[ 'settings' ] );
		$this->assertArrayNotHasKey( 'fc_use_enhanced_select_components', $data[ 'settings' ] );
		$this->assertArrayNotHasKey( 'fc_fix_zoom_in_form_fields_mobile_devices', $data[ 'settings' ] );
	}

	/**
	 * Test: Export JSON: valid structure and metadata.
	 */
	public function test_export_json_structure_and_metadata() {
		$this->set_tracked_option( 'fc_checkout_layout', 'multi-step' );

		$json = $this->service->get_export_json();
		$data = json_decode( $json, true );

		$this->assertIsArray( $data );
		$this->assertSame( 'fluid-checkout', $data[ 'generator' ] );
		$this->assertSame( FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION, $data[ 'format_version' ] );
		$this->assertNotEmpty( $data[ 'generated_at' ] );
		$this->assertArrayHasKey( 'fluid-checkout', $data[ 'plugins' ] );
		$this->assertSame( FluidCheckout::$version, $data[ 'plugins' ][ 'fluid-checkout' ] );
		$this->assertIsArray( $data[ 'settings' ] );
		$this->assertArrayNotHasKey( 'transfer_options', $data );
	}

	/**
	 * Test: Export: includes inactive Fluid Checkout product settings from the database.
	 */
	public function test_export_includes_inactive_fc_product_settings_from_database() {
		// Simulate Address Book / VAT settings left in the DB while those plugins are inactive
		$this->set_tracked_option( 'fc_pro_enable_address_book', 'yes' );
		$this->set_tracked_option( 'fc_adb_plugin_activation_time', time() ); // excluded runtime meta
		$this->set_tracked_option( 'fc_vat_number_field_visibility', 'yes' );
		$this->set_tracked_option( 'fc_gaa_enabled', 'yes' );
		$this->set_tracked_option( 'fc_gaa_google_places_api_key', 'secret-api' ); // excluded secret

		// Confirm these keys are not coming from active defaults alone
		$defaults = $this->service->get_default_option_values();
		$this->assertArrayNotHasKey( 'fc_pro_enable_address_book', $defaults );
		$this->assertArrayNotHasKey( 'fc_vat_number_field_visibility', $defaults );
		$this->assertArrayNotHasKey( 'fc_gaa_enabled', $defaults );

		$export = $this->service->get_export_data();

		$this->assertSame( 'yes', $export[ 'settings' ][ 'fc_pro_enable_address_book' ] );
		$this->assertSame( 'yes', $export[ 'settings' ][ 'fc_vat_number_field_visibility' ] );
		$this->assertSame( 'yes', $export[ 'settings' ][ 'fc_gaa_enabled' ] );
		$this->assertArrayNotHasKey( 'fc_adb_plugin_activation_time', $export[ 'settings' ] );
		$this->assertArrayNotHasKey( 'fc_gaa_google_places_api_key', $export[ 'settings' ] );
	}



	// Import

	/**
	 * Test: Import: applies known managed settings.
	 */
	public function test_import_applies_known_managed_settings() {
		$result = $this->service->import_settings( array(
			'generator'      => 'fluid-checkout',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'fc_checkout_layout'         => 'single-step',
				'fc_enable_dark_mode_styles' => 'yes',
			),
		) );

		$this->track_option( 'fc_checkout_layout' );
		$this->track_option( 'fc_enable_dark_mode_styles' );

		$this->assertSame( 2, $result[ 'imported' ] );
		$this->assertSame( 0, $result[ 'skipped' ] );
		$this->assertEmpty( $result[ 'errors' ] );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'yes', get_option( 'fc_enable_dark_mode_styles' ) );
	}

	/**
	 * Test: Import: skips troubleshooting options.
	 */
	public function test_import_skips_troubleshooting_options() {
		$result = $this->service->import_settings( array(
			'generator'      => 'fluid-checkout',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'fc_checkout_layout'                => 'single-step',
				'fc_debug_mode'                     => 'yes',
				'fc_use_enhanced_select_components' => 'yes',
			),
		) );

		$this->track_option( 'fc_checkout_layout' );
		$this->track_option( 'fc_debug_mode' );
		$this->track_option( 'fc_use_enhanced_select_components' );

		$this->assertSame( 1, $result[ 'imported' ] );
		$this->assertSame( 2, $result[ 'skipped' ] );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assert_option_does_not_exist( 'fc_debug_mode' );
		$this->assert_option_does_not_exist( 'fc_use_enhanced_select_components' );
	}

	/**
	 * Test: Import: skips unknown keys and secrets.
	 */
	public function test_import_skips_unknown_keys_and_secrets() {
		$this->set_tracked_option( 'fc_pro_license_key', 'keep-me' );

		$result = $this->service->import_settings( array(
			'generator'      => 'fluid-checkout',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'fc_checkout_layout'           => 'single-step',
				'fc_pro_license_key'           => 'stolen-license',
				'fc_gaa_google_places_api_key' => 'stolen-api-key',
				'not_a_real_fc_option'         => 'nope',
			),
		) );

		$this->track_option( 'fc_checkout_layout' );
		$this->track_option( 'fc_gaa_google_places_api_key' );

		$this->assertSame( 1, $result[ 'imported' ] );
		$this->assertSame( 3, $result[ 'skipped' ] );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'keep-me', get_option( 'fc_pro_license_key' ) );
		$this->assert_option_does_not_exist( 'fc_gaa_google_places_api_key' );
		$this->assert_option_does_not_exist( 'not_a_real_fc_option' );
	}

	/**
	 * Test: Import: rejects invalid payload, unsupported format version, and invalid JSON.
	 */
	public function test_import_rejects_invalid_payload_and_json() {
		$invalid_payload = $this->service->import_settings( array( 'generator' => 'fluid-checkout' ) );
		$this->assertNotEmpty( $invalid_payload[ 'errors' ] );
		$this->assertSame( 0, $invalid_payload[ 'imported' ] );

		$wrong_generator = $this->service->import_settings( array(
			'generator'      => 'other-plugin',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'fc_checkout_layout' => 'single-step',
			),
		) );
		$this->assertNotEmpty( $wrong_generator[ 'errors' ] );
		$this->assertSame( 0, $wrong_generator[ 'imported' ] );

		$unsupported_format = $this->service->import_settings( array(
			'generator'      => 'fluid-checkout',
			'format_version' => '999',
			'settings'       => array(
				'fc_checkout_layout' => 'single-step',
			),
		) );
		$this->assertNotEmpty( $unsupported_format[ 'errors' ] );
		$this->assertSame( 0, $unsupported_format[ 'imported' ] );

		$invalid_json = $this->service->import_settings_from_json( '{not-valid-json' );
		$this->assertNotEmpty( $invalid_json[ 'errors' ] );
		$this->assertSame( 0, $invalid_json[ 'imported' ] );
	}

	/**
	 * Test: Import: preserves existing secrets.
	 */
	public function test_import_preserves_existing_secrets() {
		$this->set_tracked_option( 'fc_vat_license_key', 'vat-secret' );
		$this->set_tracked_option( 'fc_vat_license_key_activated', 'yes' );
		$this->set_tracked_option( 'fc_gaa_google_places_api_key', 'places-secret' );

		$this->service->import_settings( array(
			'generator'      => 'fluid-checkout',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'fc_enable_checkout_progress_bar' => 'no',
				'fc_vat_license_key'              => 'overwrite-vat',
				'fc_vat_license_key_activated'    => 'no',
				'fc_gaa_google_places_api_key'    => 'overwrite-places',
			),
		) );

		$this->track_option( 'fc_enable_checkout_progress_bar' );

		$this->assertSame( 'no', get_option( 'fc_enable_checkout_progress_bar' ) );
		$this->assertSame( 'vat-secret', get_option( 'fc_vat_license_key' ) );
		$this->assertSame( 'yes', get_option( 'fc_vat_license_key_activated' ) );
		$this->assertSame( 'places-secret', get_option( 'fc_gaa_google_places_api_key' ) );
	}

	/**
	 * Test: Import: applies Fluid Checkout product settings even when the add-on is inactive.
	 */
	public function test_import_applies_inactive_fc_product_settings() {
		$result = $this->service->import_settings( array(
			'generator'      => 'fluid-checkout',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'fc_pro_enable_address_book'     => 'yes',
				'fc_vat_number_field_visibility' => 'optional',
				'fc_gaa_enabled'                 => 'yes',
				'fc_adb_license_key'             => 'should-skip',
				'not_an_fc_option'               => 'should-skip',
			),
		) );

		$this->track_option( 'fc_pro_enable_address_book' );
		$this->track_option( 'fc_vat_number_field_visibility' );
		$this->track_option( 'fc_gaa_enabled' );
		$this->track_option( 'fc_adb_license_key' );
		$this->track_option( 'not_an_fc_option' );

		$this->assertSame( 3, $result[ 'imported' ] );
		$this->assertSame( 2, $result[ 'skipped' ] );
		$this->assertSame( 'yes', get_option( 'fc_pro_enable_address_book' ) );
		$this->assertSame( 'optional', get_option( 'fc_vat_number_field_visibility' ) );
		$this->assertSame( 'yes', get_option( 'fc_gaa_enabled' ) );
		$this->assert_option_does_not_exist( 'fc_adb_license_key' );
		$this->assert_option_does_not_exist( 'not_an_fc_option' );
	}

	/**
	 * Test: Import with backup: creates backup before applying and invalid import does not replace backup.
	 */
	public function test_import_with_backup_and_invalid_import_preserves_existing_backup() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );

		$result = $this->service->import_settings(
			array(
				'generator'      => 'fluid-checkout',
				'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
				'settings'       => array(
					'fc_checkout_layout' => 'multi-step',
				),
			),
			true
		);

		$this->assertTrue( $result[ 'backup_created' ] );
		$this->assertTrue( null !== $this->service->get_last_backup() );
		$backup = $this->service->get_last_backup();
		$this->assertSame( 'import', $backup[ 'created_by' ] );
		$this->assertSame( 'single-step', $backup[ 'data' ][ 'settings' ][ 'fc_checkout_layout' ] );
		$this->assertSame( 'multi-step', get_option( 'fc_checkout_layout' ) );

		$invalid = $this->service->import_settings( array( 'generator' => 'fluid-checkout' ), true );
		$this->assertNotEmpty( $invalid[ 'errors' ] );
		$this->assertFalse( $invalid[ 'backup_created' ] );
		$this->assertSame( 'single-step', $this->service->get_last_backup()[ 'data' ][ 'settings' ][ 'fc_checkout_layout' ] );
	}

	/**
	 * Test: Update import: leaves extra local keys unchanged.
	 */
	public function test_update_import_leaves_extra_local_keys() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_enable_dark_mode_styles', 'yes' );

		$result = $this->service->import_settings(
			array(
				'generator'      => 'fluid-checkout',
				'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
				'settings'       => array(
					'fc_checkout_layout' => 'multi-step',
				),
			),
			false,
			'update'
		);

		$this->assertSame( 'update', $result[ 'mode' ] );
		$this->assertSame( 0, $result[ 'reset' ] );
		$this->assertSame( 1, $result[ 'imported' ] );
		$this->assertEmpty( $result[ 'errors' ] );
		$this->assertSame( 'multi-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'yes', get_option( 'fc_enable_dark_mode_styles' ) );
	}

	/**
	 * Test: Replace import: removes extra local keys then applies the file.
	 */
	public function test_replace_import_removes_extra_local_keys_then_applies_file() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_enable_dark_mode_styles', 'yes' );
		$this->set_tracked_option( 'fc_pro_license_key', 'keep-license' );

		$result = $this->service->import_settings(
			array(
				'generator'      => 'fluid-checkout',
				'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
				'settings'       => array(
					'fc_checkout_layout' => 'multi-step',
				),
			),
			true,
			'replace'
		);

		$this->assertSame( 'replace', $result[ 'mode' ] );
		$this->assertTrue( $result[ 'backup_created' ] );
		$this->assertGreaterThanOrEqual( 2, $result[ 'reset' ] );
		$this->assertSame( 1, $result[ 'imported' ] );
		$this->assertEmpty( $result[ 'errors' ] );
		$this->assertSame( 'multi-step', get_option( 'fc_checkout_layout' ) );
		$this->assert_option_does_not_exist( 'fc_enable_dark_mode_styles' );
		$this->assertSame( 'keep-license', get_option( 'fc_pro_license_key' ) );
		$this->assertSame( 'single-step', $this->service->get_last_backup()[ 'data' ][ 'settings' ][ 'fc_checkout_layout' ] );
		$this->assertSame( 'yes', $this->service->get_last_backup()[ 'data' ][ 'settings' ][ 'fc_enable_dark_mode_styles' ] );
	}

	/**
	 * Test: Import diff: changed, added, unchanged, and will_clear for replace.
	 */
	public function test_import_diff_buckets_for_update_and_replace() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_enable_dark_mode_styles', 'yes' );
		$this->set_tracked_option( 'fc_enable_checkout_progress_bar', 'yes' );

		$payload = array(
			'generator'      => 'fluid-checkout',
			'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
			'settings'       => array(
				'fc_checkout_layout'         => 'multi-step',
				'fc_enable_dark_mode_styles' => 'yes',
				'fc_gaa_enabled'             => 'yes',
			),
		);

		$update_diff = $this->service->get_import_diff( $payload, 'update' );

		$this->assertSame( 'update', $update_diff[ 'mode' ] );
		$this->assertArrayHasKey( 'fc_checkout_layout', $update_diff[ 'changed' ] );
		$this->assertSame( 'single-step', $update_diff[ 'changed' ][ 'fc_checkout_layout' ][ 'from' ] );
		$this->assertSame( 'multi-step', $update_diff[ 'changed' ][ 'fc_checkout_layout' ][ 'to' ] );
		$this->assertArrayHasKey( 'fc_gaa_enabled', $update_diff[ 'added' ] );
		$this->assertSame( 'yes', $update_diff[ 'added' ][ 'fc_gaa_enabled' ][ 'to' ] );
		$this->assertSame( 1, $update_diff[ 'unchanged_count' ] );
		$this->assertEmpty( $update_diff[ 'will_clear' ] );
		$this->assertEmpty( $update_diff[ 'errors' ] );

		$replace_diff = $this->service->get_import_diff( $payload, 'replace' );

		$this->assertSame( 'replace', $replace_diff[ 'mode' ] );
		$this->assertContains( 'fc_enable_checkout_progress_bar', $replace_diff[ 'will_clear' ] );
		$this->assertNotContains( 'fc_checkout_layout', $replace_diff[ 'will_clear' ] );
		$this->assertNotContains( 'fc_enable_dark_mode_styles', $replace_diff[ 'will_clear' ] );
	}

	/**
	 * Test: Import diff: secrets and invalid payloads are skipped or errored without values.
	 */
	public function test_import_diff_skips_secrets_and_rejects_invalid_payload() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );

		$diff = $this->service->get_import_diff(
			array(
				'generator'      => 'fluid-checkout',
				'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
				'settings'       => array(
					'fc_checkout_layout'  => 'multi-step',
					'fc_pro_license_key'  => 'stolen-license',
					'fc_gaa_google_places_api_key' => 'stolen-api-key',
					'fc_debug_mode'       => 'yes',
				),
			),
			'update'
		);

		$this->assertSame( 3, $diff[ 'skipped_count' ] );
		$this->assertArrayHasKey( 'fc_checkout_layout', $diff[ 'changed' ] );
		$this->assertArrayNotHasKey( 'fc_pro_license_key', $diff[ 'changed' ] );
		$this->assertArrayNotHasKey( 'fc_pro_license_key', $diff[ 'added' ] );
		$this->assertStringNotContainsString( 'stolen-license', wp_json_encode( $diff ) );
		$this->assertStringNotContainsString( 'stolen-api-key', wp_json_encode( $diff ) );

		$invalid = $this->service->get_import_diff( array( 'generator' => 'fluid-checkout' ), 'replace' );
		$this->assertNotEmpty( $invalid[ 'errors' ] );
		$this->assertEmpty( $invalid[ 'changed' ] );
		$this->assertEmpty( $invalid[ 'added' ] );
		$this->assertEmpty( $invalid[ 'will_clear' ] );
	}

	/**
	 * Test: Invalid replace import: does not create a backup or reset settings.
	 */
	public function test_invalid_replace_import_does_not_backup_or_reset() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_enable_dark_mode_styles', 'yes' );

		$result = $this->service->import_settings(
			array( 'generator' => 'fluid-checkout' ),
			true,
			'replace'
		);

		$this->assertNotEmpty( $result[ 'errors' ] );
		$this->assertFalse( $result[ 'backup_created' ] );
		$this->assertSame( 0, $result[ 'reset' ] );
		$this->assertSame( 0, $result[ 'imported' ] );
		$this->assertNull( $this->service->get_last_backup() );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'yes', get_option( 'fc_enable_dark_mode_styles' ) );
	}

	/**
	 * Test: Round-trip: export then import restores saved settings.
	 */
	public function test_round_trip_export_import() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_enable_dark_mode_styles', 'yes' );

		$export = $this->service->get_export_data();

		// Keep only the keys under test so leftover DB options do not affect counts
		$export[ 'settings' ] = array_intersect_key(
			$export[ 'settings' ],
			array_flip( array( 'fc_checkout_layout', 'fc_enable_dark_mode_styles' ) )
		);

		$this->assertCount( 2, $export[ 'settings' ] );

		// Change values, then import the export
		update_option( 'fc_checkout_layout', 'multi-step' );
		update_option( 'fc_enable_dark_mode_styles', 'no' );

		$result = $this->service->import_settings( $export );

		$this->assertSame( 2, $result[ 'imported' ] );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'yes', get_option( 'fc_enable_dark_mode_styles' ) );
	}



	// Restore

	/**
	 * Test: Auto-backup: stores managed values including troubleshooting and omits secrets.
	 */
	public function test_auto_backup_stores_managed_values_including_troubleshooting() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_debug_mode', 'yes' );
		$this->set_tracked_option( 'woocommerce_checkout_phone_field', 'optional' );
		$this->set_tracked_option( 'fc_pro_license_key', 'secret-license' );

		$backup = $this->service->create_auto_backup( 'reset' );

		$this->assertSame( 'reset', $backup[ 'created_by' ] );
		$this->assertNotEmpty( $backup[ 'created_at' ] );
		$this->assertSame( 'single-step', $backup[ 'data' ][ 'settings' ][ 'fc_checkout_layout' ] );
		$this->assertSame( 'yes', $backup[ 'data' ][ 'settings' ][ 'fc_debug_mode' ] );
		$this->assertSame( 'optional', $backup[ 'data' ][ 'settings' ][ 'woocommerce_checkout_phone_field' ] );
		$this->assertArrayNotHasKey( 'fc_pro_license_key', $backup[ 'data' ][ 'settings' ] );
		$this->assertTrue( null !== $this->service->get_last_backup() );
		$this->assertSame( $backup, $this->service->get_last_backup() );
	}

	/**
	 * Test: Auto-backup: expired backups are cleared and unavailable.
	 */
	public function test_auto_backup_expired_is_cleared_and_unavailable() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->service->create_auto_backup( 'import' );

		$backup = get_option( FluidCheckout_Admin_Settings_Tools_Service::BACKUP_OPTION_KEY );
		$backup[ 'created_at' ] = gmdate( 'c', time() - FluidCheckout_Admin_Settings_Tools_Service::BACKUP_TTL - 10 );
		update_option( FluidCheckout_Admin_Settings_Tools_Service::BACKUP_OPTION_KEY, $backup, false );

		$this->assertNull( $this->service->get_last_backup() );
		$this->assert_option_does_not_exist( FluidCheckout_Admin_Settings_Tools_Service::BACKUP_OPTION_KEY );
	}

	/**
	 * Test: Reset with backup: creates backup then restore undoes the reset.
	 */
	public function test_reset_with_backup_then_restore_undoes_reset() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_debug_mode', 'yes' );

		$result = $this->service->reset_settings( true );

		$this->assertTrue( $result[ 'backup_created' ] );
		$this->assert_option_does_not_exist( 'fc_checkout_layout' );
		$this->assert_option_does_not_exist( 'fc_debug_mode' );
		$this->assertTrue( null !== $this->service->get_last_backup() );

		$restore = $this->service->restore_last_backup();

		$this->assertEmpty( $restore[ 'errors' ] );
		$this->assertGreaterThanOrEqual( 2, $restore[ 'restored' ] );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'yes', get_option( 'fc_debug_mode' ) );
	}

	/**
	 * Test: Restore after reset: restores inactive Fluid Checkout product settings not in defaults.
	 */
	public function test_restore_after_reset_restores_inactive_fc_product_settings() {
		$defaults = $this->service->get_default_option_values();
		$this->assertArrayNotHasKey( 'fc_gaa_enabled', $defaults );
		$this->assertArrayNotHasKey( 'fc_vat_number_field_visibility', $defaults );

		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_gaa_enabled', 'yes' );
		$this->set_tracked_option( 'fc_vat_number_field_visibility', 'optional' );

		$this->service->reset_settings( true );

		$this->assert_option_does_not_exist( 'fc_checkout_layout' );
		$this->assert_option_does_not_exist( 'fc_gaa_enabled' );
		$this->assert_option_does_not_exist( 'fc_vat_number_field_visibility' );
		$this->assertArrayHasKey( 'fc_gaa_enabled', $this->service->get_last_backup()[ 'data' ][ 'settings' ] );

		$restore = $this->service->restore_last_backup();

		$this->assertEmpty( $restore[ 'errors' ] );
		$this->assertGreaterThanOrEqual( 3, $restore[ 'restored' ] );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'yes', get_option( 'fc_gaa_enabled' ) );
		$this->assertSame( 'optional', get_option( 'fc_vat_number_field_visibility' ) );
	}

	/**
	 * Test: Restore after import: restores previous values and removes imported-only keys.
	 */
	public function test_restore_after_import_restores_previous_and_removes_imported_only_keys() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );

		$this->service->import_settings(
			array(
				'generator'      => 'fluid-checkout',
				'format_version' => FluidCheckout_Admin_Settings_Tools_Service::EXPORT_FORMAT_VERSION,
				'settings'       => array(
					'fc_checkout_layout'         => 'multi-step',
					'fc_enable_dark_mode_styles' => 'yes',
				),
			),
			true
		);

		$this->track_option( 'fc_enable_dark_mode_styles' );
		$this->assertSame( 'multi-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'yes', get_option( 'fc_enable_dark_mode_styles' ) );

		$restore = $this->service->restore_last_backup();

		$this->assertEmpty( $restore[ 'errors' ] );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assert_option_does_not_exist( 'fc_enable_dark_mode_styles' );
		$this->assertGreaterThanOrEqual( 1, $restore[ 'deleted' ] );
	}

	/**
	 * Test: Restore: leaves secrets unchanged and errors when no backup exists.
	 */
	public function test_restore_preserves_secrets_and_errors_without_backup() {
		$this->assertNull( $this->service->get_last_backup() );
		$missing = $this->service->restore_last_backup();
		$this->assertNotEmpty( $missing[ 'errors' ] );
		$this->assertSame( 0, $missing[ 'restored' ] );

		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_pro_license_key', 'keep-license' );
		$this->service->create_auto_backup( 'import' );

		update_option( 'fc_checkout_layout', 'multi-step' );
		update_option( 'fc_pro_license_key', 'changed-license' );

		$restore = $this->service->restore_last_backup();

		$this->assertEmpty( $restore[ 'errors' ] );
		$this->assertSame( 'single-step', get_option( 'fc_checkout_layout' ) );
		$this->assertSame( 'changed-license', get_option( 'fc_pro_license_key' ) );
	}



	// Reset

	/**
	 * Test: Reset: deletes managed saved options and restores defaults via getter.
	 */
	public function test_reset_deletes_managed_options_and_restores_defaults() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_debug_mode', 'yes' );

		$result = $this->service->reset_settings();

		$this->assertGreaterThanOrEqual( 2, $result[ 'reset' ] );
		$this->assert_option_does_not_exist( 'fc_checkout_layout' );
		$this->assert_option_does_not_exist( 'fc_debug_mode' );

		// Getters should fall back to defaults
		$this->assertSame(
			FluidCheckout_Settings::instance()->get_option_default( 'fc_checkout_layout' ),
			FluidCheckout_Settings::instance()->get_option( 'fc_checkout_layout' )
		);
		$this->assertSame(
			FluidCheckout_Settings::instance()->get_option_default( 'fc_debug_mode' ),
			FluidCheckout_Settings::instance()->get_option( 'fc_debug_mode' )
		);
	}

	/**
	 * Test: Reset: leaves secrets and runtime meta in place.
	 */
	public function test_reset_leaves_secrets_and_runtime_meta() {
		$this->set_tracked_option( 'fc_checkout_layout', 'single-step' );
		$this->set_tracked_option( 'fc_pro_license_key', 'keep-license' );
		$this->set_tracked_option( 'fc_pro_license_key_activated', 'yes' );
		$this->set_tracked_option( 'fc_gaa_google_places_api_key', 'keep-api' );
		$this->set_tracked_option( 'fc_plugin_activation_time', 1234567890 );
		$this->set_tracked_option( 'fc_db_version', '4.2.6' );

		$result = $this->service->reset_settings();

		$this->assertGreaterThanOrEqual( 1, $result[ 'reset' ] );
		$this->assert_option_does_not_exist( 'fc_checkout_layout' );
		$this->assertSame( 'keep-license', get_option( 'fc_pro_license_key' ) );
		$this->assertSame( 'yes', get_option( 'fc_pro_license_key_activated' ) );
		$this->assertSame( 'keep-api', get_option( 'fc_gaa_google_places_api_key' ) );
		$this->assertSame( 1234567890, (int) get_option( 'fc_plugin_activation_time' ) );
		$this->assertSame( '4.2.6', get_option( 'fc_db_version' ) );
	}

	/**
	 * Test: Reset: deletes inactive Fluid Checkout product settings from the database.
	 */
	public function test_reset_deletes_inactive_fc_product_settings() {
		$this->set_tracked_option( 'fc_pro_enable_address_book', 'yes' );
		$this->set_tracked_option( 'fc_vat_number_field_visibility', 'yes' );
		$this->set_tracked_option( 'fc_gaa_enabled', 'yes' );
		$this->set_tracked_option( 'fc_adb_license_key', 'keep-license' );

		$result = $this->service->reset_settings();

		$this->assertGreaterThanOrEqual( 3, $result[ 'reset' ] );
		$this->assert_option_does_not_exist( 'fc_pro_enable_address_book' );
		$this->assert_option_does_not_exist( 'fc_vat_number_field_visibility' );
		$this->assert_option_does_not_exist( 'fc_gaa_enabled' );
		$this->assertSame( 'keep-license', get_option( 'fc_adb_license_key' ) );
	}

}
