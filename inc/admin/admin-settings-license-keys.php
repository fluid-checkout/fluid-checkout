<?php
/**
 * Fluid Checkout License Keys Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_LicenseKeys', false ) ) {
	FluidCheckout_Settings_LicenseKeys::hooks();
	return;
}

/**
 * FluidCheckout_Settings_LicenseKeys.
 */
class FluidCheckout_Settings_LicenseKeys {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Settings, runs before licensed products add the same settings group
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 5, 2 );
	}



	/**
	 * Add the License Keys settings group.
	 * Licensed products add their license key fields with the filter `fc_license_keys_settings_add`.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public static function add_settings( $settings, $current_section ) {
		// Bail if not on the license keys section
		if ( 'license_keys' !== $current_section ) { return $settings; }

		$settings_new = array();

		// Site key card — Lite owns the shell; PRO may replace the inner contents
		$settings_new[] = array(
			'type'     => 'fc_site_key',
			'is_card'  => true,
			'autoload' => false,
		);

		$settings_new[] = array(
			'title' => _x( 'License Keys', 'Settings section title', 'fluid-checkout' ),
			'type'  => 'title',
			'id'    => 'fc_license_keys',
		);

		$settings_add = apply_filters( 'fc_' . $current_section . '_settings_add', array(), $current_section );

		// Maybe add a notice when no license keys are available
		if ( ! is_array( $settings_add ) || 0 === count( $settings_add ) ) {
			$settings_add = array(
				array(
					'type'        => 'fc_paragraph',
					'desc'        => __( 'No license keys to manage at the moment. License keys will appear here when a licensed Fluid Checkout plugin or add-on is active.', 'fluid-checkout' ),
					'id'          => 'fc_no_license_keys',
				),
				array(
					'type'        => 'fc_paragraph',
					'desc'        => '<a href="https://fluidcheckout.com/?mtm_campaign=addons&mtm_kwd=license-keys&mtm_source=lite-plugin" target="_blank" rel="noopener noreferrer">' . __( 'Visit our website for more information about our plugins and add-ons.', 'fluid-checkout' ) . '</a>',
					'id'          => 'fc_no_license_keys_link',
				),
			);
		}

		$settings_new = array_merge( $settings_new, $settings_add );

		// Close the license keys section
		$settings_new[] = array(
			'type' => 'sectionend',
			'id'   => 'fc_license_keys',
		);

		$settings = apply_filters( 'fc_' . $current_section . '_settings', $settings_new, $current_section );

		return $settings;
	}

}

FluidCheckout_Settings_LicenseKeys::hooks();
