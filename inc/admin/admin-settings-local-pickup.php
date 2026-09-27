<?php
/**
 * Fluid Checkout Local Pickup Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_LocalPickup', false ) ) {
	FluidCheckout_Settings_LocalPickup::hooks();
	return;
}

/**
 * FluidCheckout_Settings_LocalPickup.
 */
class FluidCheckout_Settings_LocalPickup {

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
			'local_pickup' => __( 'Local Pickup', 'fluid-checkout' ),
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
		if ( 'local_pickup' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_local_pickup_settings',
			array(
				array(
					'title' => __( 'Local Pickup', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_local_pickup_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'local-pickup' ),
					'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-local-pickup/' ),
				),

				array(
					'title'                 => __( 'Local pickup', 'fluid-checkout' ),
					'desc'                  => __( 'Removes shipping address section when a local pickup shipping method is selected.', 'fluid-checkout' ),
					'desc_tip'              => __( 'Replaces the shipping address with the pickup point location when a local pickup shipping method is selected.', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_local_pickup',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_local_pickup' ),
					'checkboxgroup'         => 'start',
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Show option to clear shipping methods in the pickup location substep', 'fluid-checkout' ),
					'desc_tip'              => __( 'Show a link button on the pickup location substep to clear the chosen shipping methods. This can be used to allow showing the shipping address section again if a local pickup method was previously selected.', 'fluid-checkout' ),
					'id'                    => 'fc_local_pickup_display_clear_shipping_methods_button',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_local_pickup_display_clear_shipping_methods_button' ),
					'checkboxgroup'         => 'end',
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_enable_checkout_local_pickup',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'desc'                  => '',
					'desc_tip'          => __( 'Choose which address to save as the shipping address for local pickup orders.', 'fluid-checkout' ),
					'id'                    => 'fc_local_pickup_save_shipping_address',
					'type'                  => 'fc_select',
					'options'               => array(
						'same_as_pickup_location'    => __( 'Save the selected pickup location', 'fluid-checkout' ),
						'same_as_billing'            => __( 'Save same as the billing address', 'fluid-checkout' ),
						'no'                         => __( 'Do not save any shipping address', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_local_pickup_save_shipping_address' ),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_local_pickup_options',
				),
			)
		);
	}

}

FluidCheckout_Settings_LocalPickup::hooks();
