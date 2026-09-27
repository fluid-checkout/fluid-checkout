<?php
/**
 * Fluid Checkout Dashboard Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_Dashboard', false ) ) {
	FluidCheckout_Settings_Dashboard::hooks();
	return;
}

/**
 * FluidCheckout_Settings_Dashboard.
 */
class FluidCheckout_Settings_Dashboard {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Sections
		add_filter( 'fc_admin_settings_sections', array( __CLASS__, 'add_sections' ), 10 );

		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public static function add_sections( $sections ) {
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
	public static function add_settings( $settings, $current_section ) {
		if ( '' === $current_section ) {

			// Dashboard field types output their own settings cards
			$settings = array(

				array(
					'type'             => 'fc_setup',
					'is_card'          => true,
					'autoload'         => false,
				),
				array(
					'type'             => 'fc_addons',
					'is_card'          => true,
					'autoload'         => false,
				),
				array(
					'type'             => 'fc_plugins_catalog',
					'is_card'          => true,
					'autoload'         => false,
				),

			);

			$settings = apply_filters( 'fc_'.$current_section.'_settings', $settings, $current_section );
		}

		return $settings;
	}

}

FluidCheckout_Settings_Dashboard::hooks();
