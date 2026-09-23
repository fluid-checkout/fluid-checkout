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
	 * Get the promotional settings card shown at the top of the Order Pay tab when PRO is not active.
	 */
	public function get_promo_settings() {
		// Bail if PRO is already activated
		if ( FluidCheckout::instance()->is_pro_activated() ) { return array(); }

		return array(
			array(
				'title'            => __( 'Order Pay Page Optimization', 'fluid-checkout' ),
				'type'             => 'fc_promo',
				'id'               => 'fc_pro_order_pay_promo',
				'is_card'          => true,
				'promo'            => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'order-pay-promo' ),
				'tagline'          => __( 'Give unpaid orders the same clear, conversion-focused layout as checkout.', 'fluid-checkout' ),
				'features'         => array(
					__( 'Optimize the layout of the order pay page for orders pending payment.', 'fluid-checkout' ),
					__( 'Match the order pay experience to your checkout for a consistent purchase journey.', 'fluid-checkout' ),
					__( 'Add trust symbols and badges where shoppers need reassurance before paying.', 'fluid-checkout' ),
				),
				'learn_more_url'   => 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=order-pay-promo-learn-more&mtm_source=lite-plugin',
				'learn_more_label' => __( 'Learn more', 'fluid-checkout' ),
			),
		);
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
				array_merge(
					$this->get_promo_settings(),
					array(

					array(
						'title' => __( 'Order Pay Page', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => __( 'Allows customers to make payments for orders that are either created manually by the store admin or not completed during checkout.', 'fluid-checkout' ),
						'id'    => 'fc_pro_order_pay_layout_options',
						'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'order-pay' ),
						'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-order-pay/' ),
					),

					array(
						'title'             => __( 'Order pay', 'fluid-checkout' ),
						'desc'              => __( 'Enable order pay page optimizations', 'fluid-checkout' ),
						'desc_tip'          => __( 'Changes the layout of order pay page for existing orders pending payment from the customer.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_order_pay',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_pay' ),
						'checkboxgroup'     => 'start',
						'show_if_checked'   => 'option',
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_order_pay_layout_options',
					),



					array(
						'title' => __( 'Trust Symbols & Badges', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_pro_order_pay_trust_symbols_options',
						'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'order-pay-trust-symbols' ),
						'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
					),

					array(
						'title'             => __( 'Widget areas', 'fluid-checkout' ),
						'desc'              => __( 'Add widget areas to the order pay page', 'fluid-checkout' ),
						'desc_tip'          => __( 'These widget areas are used to add trust symbols and trust badges on the order pay page.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_order_pay_widget_areas',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_pay_widget_areas' ),
						'type'              => 'checkbox',
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_order_pay_trust_symbols_options',
					),

					)
				)
			);
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_OrderPay_Settings();
