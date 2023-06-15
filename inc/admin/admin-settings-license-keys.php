<?php
/**
 * Fluid Checkout License Key Settings
 *
 * @package fluid-checkout
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_LicenseKeys_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_LicenseKeys_Settings();
}

/**
 * WC_Settings_FluidCheckout_LicenseKeys_Settings.
 */
class WC_Settings_FluidCheckout_LicenseKeys_Settings extends WC_Settings_Page {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->id = 'fc_checkout';
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Sections
		add_filter( 'woocommerce_get_sections_fc_checkout', array( $this, 'add_sections' ), 20 );

		// Settings
		add_filter( 'woocommerce_get_settings_fc_checkout', array( $this, 'add_settings' ), 20, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public function add_sections( $sections ) {
		// Bail if not plugins with require license keys are active
		if ( false === apply_filters( 'fc_show_settings_license_keys', false ) ) { return $sections; }

		// Insert as the last section
		$sections = array_merge( $sections, array(
			'license_keys' => __( 'License keys', 'fluid-checkout' ),
		) );
		
		return $sections;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		if ( 'license_keys' === $current_section ) {

			$settings_new = array(
				array(
					'title' => _x( 'License keys', 'Settings section title', 'fluid-checkout' ),
					'type'  => 'title',
					'id'    => 'fc_license_keys',
				),
			);

			$settings_add = apply_filters( 'fc_'.$current_section.'_settings_add', array(), $current_section );

			// Maybe add notice when no integrations are available
			if ( 0 == count( $settings_add ) ) {
				$settings_add[] = array(
					'type'        => 'fc_paragraph',
					'desc'        => __( 'No license keys options available at the moment on this section. The options related to each plugin or add-on will appear here when that plugin is activated.', 'fluid-checkout' ),
					'id'          => 'fc_no_license_keys',
				);
				$settings_add[] = array(
					'type'        => 'fc_paragraph',
					'desc'        => '<a href="https://fluidcheckout.com/?mtm_campaign=addons&mtm_kwd=license-keys&mtm_source=lite-plugin">' . __( 'Visit our website for more information about our plugins and add-ons.', 'fluid-checkout' ) . '</a>',
					'id'          => 'fc_no_license_keys',
				);
			}

			// Add new settings, if any
			$settings_new = array_merge( $settings_new, $settings_add );

			// Close integrations section to avoid errors with other sections
			$settings_new = array_merge( $settings_new, array(
				array(
					'type' => 'sectionend',
					'id'   => 'fc_license_keys',
				),
			) );

			$settings = apply_filters( 'fc_'.$current_section.'_settings', $settings_new, $current_section );
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_LicenseKeys_Settings();
