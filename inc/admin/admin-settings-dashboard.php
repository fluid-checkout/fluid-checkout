<?php
/**
 * Fluid Checkout Tools Settings
 *
 * @package fluid-checkout
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Addons_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_Addons_Settings();
}

/**
 * WC_Settings_FluidCheckout_Addons_Settings.
 */
class WC_Settings_FluidCheckout_Addons_Settings extends WC_Settings_Page {

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
		add_filter( 'woocommerce_get_sections_fc_checkout', array( $this, 'add_sections' ), 10 );

		// Settings
		add_filter( 'woocommerce_get_settings_fc_checkout', array( $this, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public function add_sections( $sections ) {
		$sections = array_merge( $sections, array(
			'' => __( 'Dashboard', 'fluid-checkout' ),
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
		if ( '' === $current_section ) {

			$settings = array(

				array(
					'type'  => 'title',
					'id'    => 'fc_checkout_addons_options',
				),

				array(
					'type'             => 'fc_setup',
					'autoload'         => false,
				),
				array(
					'type'             => 'fc_addons',
					'autoload'         => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_addons_options',
				),

			);

			$settings = apply_filters( 'fc_'.$current_section.'_settings', $settings, $current_section );
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_Addons_Settings();
