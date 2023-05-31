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
		// Define sections to insert
		$insert_sections = array(
			'addons' => __( 'Add-ons', 'fluid-checkout' ),
		);

		// Get token position
		$position_index = count( $sections );
		for ( $index = 0; $index < count( $sections ); $index++ ) {
			if ( 'advanced' == array_keys( $sections )[ $index ] ) {
				$position_index = $index;
			}
		}

		// Insert at token position
		$new_sections = array_slice( $sections, 0, $position_index );
		$new_sections = array_merge( $new_sections, $insert_sections );
		$new_sections = array_merge( $new_sections, array_slice( $sections, $position_index, count( $sections ) ) );
		
		return $new_sections;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		if ( 'addons' === $current_section ) {

			$settings = array(

				array(
					'title' => __( 'Add-ons', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_checkout_addons_options',
				),

				array(
					'title'            => __( 'Debug options', 'fluid-checkout' ),
					'desc'             => __( 'Debug mode', 'fluid-checkout' ),
					'desc_tip'         => __( 'Using debug mode affects the website performance. Only use this option while troubleshooting.', 'fluid-checkout' ),
					'type'             => 'fc_addons',
					'default'          => FluidCheckout_Settings::instance()->get_option_default( 'fc_debug_mode' ),
					'checkboxgroup'    => 'start',
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
