<?php
/**
 * Fluid Checkout PRO Order Pay Settings
 *
 * @package fluid-checkout-pro
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_OrderPay_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_OrderPay_Settings();
}

/**
 * WC_Settings_FluidCheckout_OrderPay_Settings.
 */
class WC_Settings_FluidCheckout_OrderPay_Settings extends WC_Settings_Page {

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
			'order_pay' => _x( 'Order pay', 'Settings section', 'fluid-checkout' ),
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
		if ( 'order_pay' === $current_section ) {

			$settings = apply_filters(
				'fc_pro_order_pay_settings',
				array(

					array(
						'title' => __( 'Order pay page', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_experimental_feature_html(),
						'type'  => 'title',
						'desc'  => __( 'Allows customers to make payments for orders that are either created manually by the store admin or not completed during checkout.' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-order-pay/' ) . FluidCheckout_Admin::instance()->get_experimental_feature_explanation_html( true ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'    => 'fc_pro_order_pay_layout_options',
					),

					array(
						'title'             => __( 'Order pay', 'fluid-checkout' ),
						'desc'              => __( 'Enable order pay page optimizations', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_experimental_feature_html(),
						'desc_tip'          => __( 'Changes the layout of order pay page for existing orders pending payment from the customer.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_enable_order_pay',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_pay' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Trust symbols &amp; badges', 'fluid-checkout' ),
						'desc'              => __( 'Add widget areas to the order pay page', 'fluid-checkout' ),
						'desc_tip'          => __( 'These widget areas are used to add trust symbols and trust badges on the order pay page.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
						'id'                => 'fc_pro_enable_order_pay_widget_areas',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_pay_widget_areas' ),
						'type'              => 'checkbox',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_order_pay_layout_options',
					),

				)
			);
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_OrderPay_Settings();
