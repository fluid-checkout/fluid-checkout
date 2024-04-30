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
						'title' => __( 'Order pay page', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => FluidCheckout_Admin::instance()->get_upgrade_pro_html( false ),
						'id'    => 'fc_pro_order_pay_layout_options',
					),

					array(
						'title'             => __( 'Order pay', 'fluid-checkout' ),
						'desc'              => __( 'Enable order pay page optimizations', 'fluid-checkout' ),
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
						'desc'              => __( 'Enable order details customizations on email notifications', 'fluid-checkout' ),
						'desc_tip'          => __( 'Display new sections and change order of sections on email notifications. Styles of order details on email notifications remain unchanged.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_order_details_email_customizations',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_details_email_customizations' ),
						'checkboxgroup'     => 'end',
						'show_if_checked'   => 'yes',
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
