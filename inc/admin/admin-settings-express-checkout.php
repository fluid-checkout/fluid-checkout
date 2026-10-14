<?php
/**
 * Fluid Checkout Express Checkout Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_ExpressCheckout', false ) ) {
	FluidCheckout_Settings_ExpressCheckout::hooks();
	return;
}

/**
 * FluidCheckout_Settings_ExpressCheckout.
 */
class FluidCheckout_Settings_ExpressCheckout {

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
			'express_checkout' => __( 'Express checkout', 'fluid-checkout' ),
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
		if ( 'express_checkout' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_express_checkout_settings',
			array(
				array(
					'title' => __( 'Express checkout', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_express_checkout_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'express-checkout' ),
					'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-express-checkout/' ),
				),

				array(
					'title'                 => __( 'Express checkout', 'fluid-checkout' ),
					'desc'                  => __( 'Enable the express checkout section', 'fluid-checkout' ),
					'desc_tip'              => __( 'Displays the express checkout section at checkout when supported payment gateways have this feature enabled.', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_express_checkout',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_express_checkout' ),
					'type'                  => 'checkbox',
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'title'                 => __( 'Inline buttons', 'fluid-checkout' ),
					'desc'                  => __( 'Display express checkout buttons in one line for larger screens', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_express_checkout_inline_buttons',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_express_checkout_inline_buttons' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_enable_checkout_express_checkout',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'title'                 => __( 'Required fields', 'fluid-checkout' ),
					'desc'                  => __( 'Ignore additional checkout required fields when paying with a compatible express checkout payment gateway', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_express_checkout_ignore_required_fields',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_express_checkout_ignore_required_fields' ),
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_enable_checkout_express_checkout',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_express_checkout_options',
				),
			)
		);
	}

}

FluidCheckout_Settings_ExpressCheckout::hooks();
