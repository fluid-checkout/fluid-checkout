<?php
/**
 * Fluid Checkout License Key Settings
 *
 * @package fluid-checkout
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckoutLicenseKeys_Settings', false ) ) {
	return new WC_Settings_FluidCheckoutLicenseKeys_Settings();
}

/**
 * WC_Settings_FluidCheckoutLicenseKeys_Settings.
 */
class WC_Settings_FluidCheckoutLicenseKeys_Settings extends WC_Settings_Page {

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

}

return new WC_Settings_FluidCheckoutLicenseKeys_Settings();
