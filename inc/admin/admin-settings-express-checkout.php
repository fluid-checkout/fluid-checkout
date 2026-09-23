<?php
/**
 * Fluid Checkout Express Checkout Settings
 *
 * @package fluid-checkout
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_ExpressCheckout_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_ExpressCheckout_Settings();
}

/**
 * WC_Settings_FluidCheckout_ExpressCheckout_Settings.
 */
class WC_Settings_FluidCheckout_ExpressCheckout_Settings extends WC_Settings_Page {

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
			'express_checkout' => __( 'Express Checkout', 'fluid-checkout' ),
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
		if ( 'express_checkout' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_express_checkout_settings',
			array(
				array(
					'title' => __( 'Express Checkout', 'fluid-checkout' ),
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
					'checkboxgroup'         => 'start',
					'show_if_checked'       => 'option',
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Display express checkout buttons in one line for larger screens', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_express_checkout_inline_buttons',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_express_checkout_inline_buttons' ),
					'checkboxgroup'         => '',
					'show_if_checked'       => 'yes',
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Ignore additional checkout required fields when paying with a compatible express checkout payment gateway', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_express_checkout_ignore_required_fields',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_express_checkout_ignore_required_fields' ),
					'checkboxgroup'         => 'end',
					'show_if_checked'       => 'yes',
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

return new WC_Settings_FluidCheckout_ExpressCheckout_Settings();
