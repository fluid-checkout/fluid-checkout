<?php
/**
 * Fluid Checkout Trust Symbols & Badges Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_TrustSymbols', false ) ) {
	FluidCheckout_Settings_TrustSymbols::hooks();
	return;
}

/**
 * FluidCheckout_Settings_TrustSymbols.
 */
class FluidCheckout_Settings_TrustSymbols {

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
			'trust_symbols' => __( 'Trust badges', 'fluid-checkout' ),
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
		// Bail if not the trust symbols section
		if ( 'trust_symbols' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_trust_symbols_settings',
			array(

				array(
					'title' => __( 'Checkout', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_checkout_trust_symbols_options',
					'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
				),

				array(
					'title'                 => __( 'Widget areas', 'fluid-checkout' ),
					'desc'                  => __( 'Add widget areas to the checkout page', 'fluid-checkout' ),
					'desc_tip'              => __( 'These widget areas are used to add trust symbols and trust badges on the checkout page.', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_widget_areas',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_widget_areas' ),
					'autoload'              => false,
				),
				array(
					'title'                 => __( 'Last step on mobile', 'fluid-checkout' ),
					'desc'                  => __( 'Display widgets only at last step on mobile', 'fluid-checkout' ),
					'desc_tip'              => __( 'Display checkout trust badge widgets only when viewing the last checkout step on mobile devices', 'fluid-checkout' ),
					'id'                    => 'fc_enable_checkout_widget_area_sidebar_last_step',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_enable_checkout_widget_area_sidebar_last_step' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'fc_enable_checkout_widget_areas,fc_checkout_layout',
						'data-conditional-value' => 'yes,multi-step',
					),
					'autoload'              => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_checkout_trust_symbols_options',
				),



				array(
					'title' => __( 'Cart', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_pro_cart_trust_symbols_options',
					'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'cart-trust-symbols' ),
				),

				array(
					'title'             => __( 'Widget areas', 'fluid-checkout' ),
					'desc'              => __( 'Add widget areas to the cart page', 'fluid-checkout' ),
					'desc_tip'          => __( 'These widget areas are used to add trust symbols and trust badges on the cart page.', 'fluid-checkout' ),
					'id'                => 'fc_pro_enable_cart_widget_areas',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_cart_widget_areas' ),
					'type'              => 'checkbox',
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_pro_enable_cart_page',
						'data-conditional-value' => 'yes',
					),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_pro_cart_trust_symbols_options',
				),



				array(
					'title' => __( 'Thank you', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_pro_order_received_trust_symbols_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'order-received-trust-symbols' ),
					'docs'  => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
				),

				array(
					'title'             => __( 'Widget areas', 'fluid-checkout' ),
					'desc'              => __( 'Add widget areas to the thank you page', 'fluid-checkout' ),
					'desc_tip'          => __( 'These widget areas are used to add trust symbols and trust badges on the thank you page.', 'fluid-checkout' ),
					'id'                => 'fc_pro_enable_order_received_widget_areas',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_order_received_widget_areas' ),
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_pro_order_received_trust_symbols_options',
				),



				array(
					'title' => __( 'Order pay', 'fluid-checkout' ),
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
		);
	}

}

FluidCheckout_Settings_TrustSymbols::hooks();
