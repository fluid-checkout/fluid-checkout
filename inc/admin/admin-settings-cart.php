<?php
/**
 * Fluid Checkout PRO Cart Settings
 *
 * @package fluid-checkout
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Cart_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_Cart_Settings();
}

/**
 * WC_Settings_FluidCheckout_Cart_Settings.
 */
class WC_Settings_FluidCheckout_Cart_Settings extends WC_Settings_Page {

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
			'cart' => _x( 'Cart', 'Settings section', 'fluid-checkout' ),
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
		if ( 'cart' === $current_section ) {

			$settings = apply_filters(
				'fc_pro_cart_settings',
				array(

					array(
						'title' => __( 'Cart layout', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => FluidCheckout_Admin::instance()->get_upgrade_pro_html( false ),
						'id'    => 'fc_pro_cart_layout_options',
					),

					array(
						'title'             => __( 'Cart optimizations', 'fluid-checkout' ),
						'desc'              => __( 'Enable cart page optimizations', 'fluid-checkout' ),
						'desc_tip'          => __( 'Display the cart page with a layout similar to the checkout page.', 'fluid-checkout' ) . FluidCheckout_Admin::instance()->get_upgrade_pro_html(),
						'id'                => 'fc_pro_enable_cart_page',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_cart_page' ),
						'type'              => 'checkbox',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_cart_layout_options',
					),



					array(
						'title' => __( 'Header and footer', 'fluid-checkout' ), // Intentionally use text domain from Lite plugin to avoid duplicating this text in translation files.
						'type'  => 'title',
						'desc'  => FluidCheckout_Admin::instance()->get_upgrade_pro_html( false ),
						'id'    => 'fc_pro_cart_header_options',
					),

					array(
						'title'             => __( 'Header and footer template', 'fluid-checkout' ), // Intentionally use text domain from Lite plugin to avoid duplicating this text in translation files.
						'desc_tip'          => __( 'Controls whether to use the distraction free page header and footer or keep the currently active theme\'s header and footer.', 'fluid-checkout' ), // Intentionally use text domain from Lite plugin to avoid duplicating this text in translation files.
						'id'                => 'fc_pro_hide_site_header_footer_at_cart',
						'type'              => 'fc_select',
						'options'           => array(
							'yes'           => __( 'Distraction free header and footer', 'fluid-checkout' ), // Intentionally use text domain from Lite plugin to avoid duplicating this text in translation files.
							'no'            => __( 'Theme\'s header and footer', 'fluid-checkout' ), // Intentionally use text domain from Lite plugin to avoid duplicating this text in translation files.
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_hide_site_header_footer_at_cart' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_cart_header_options',
					),					



					array(
						'title' => __( 'Cart elements', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => FluidCheckout_Admin::instance()->get_upgrade_pro_html( false ),
						'id'    => 'fc_pro_cart_elements_options',
					),

					array(
						'title'             => __( 'Order summary', 'fluid-checkout' ),
						'desc'              => __( 'Make the order summary stay visible on the cart page while scrolling', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_cart_sticky_order_summary',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_cart_sticky_order_summary' ),
						'type'              => 'checkbox',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Coupon codes', 'fluid-checkout' ),
						'desc'              => __( 'Select position where to display the coupon codes section on the cart page. Only applicable when AJAX cart and the integrated coupon codes in the checkout page are enabled.', 'fluid-checkout' ),
						'id'                => 'fc_pro_cart_section_position_coupon_code',
						'type'              => 'fc_select',
						'options'           => array(
							'none'                     => __( 'Hidden', 'fluid-checkout' ),
							'before_cart_items'        => __( 'Before the cart items section', 'fluid-checkout' ),
							'inside_cart_items'        => __( 'Inside the cart items section', 'fluid-checkout' ),
							'after_cart_items'         => __( 'After the cart items section', 'fluid-checkout' ),
							'before_shipping'          => __( 'Before the shipping section', 'fluid-checkout' ),
							'after_shipping'           => __( 'After the shipping section', 'fluid-checkout' ),
							'before_order_summary'     => __( 'Before the order summary', 'fluid-checkout' ),
							'inside_order_summary'     => __( 'Inside the order summary', 'fluid-checkout' ),
							'before_order_totals'      => __( 'Before the order totals', 'fluid-checkout' ),
							'after_order_totals'       => __( 'After the order totals', 'fluid-checkout' ),
							'after_order_summary'      => __( 'After the order summary', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_cart_section_position_coupon_code' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Shipping', 'fluid-checkout' ),
						'desc'              => __( 'Select position where to display the shipping costs and calculator on the cart page.', 'fluid-checkout' ),
						'id'                => 'fc_pro_cart_section_position_shipping',
						'type'              => 'fc_select',
						'options'           => array(
							'none'                     => __( 'Hidden', 'fluid-checkout' ),
							'cart_section'             => __( 'Display as a cart section', 'fluid-checkout' ),
							'inside_order_summary'     => __( 'Inside the order summary', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_cart_section_position_shipping' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Cross-sells', 'fluid-checkout' ),
						'desc'              => __( 'Select position where to display the cross-sells section on the cart page.', 'fluid-checkout' ),
						'id'                => 'fc_pro_cart_section_position_cross_sells',
						'type'              => 'fc_select',
						'options'           => array(
							'none'                     => __( 'Hidden', 'fluid-checkout' ),
							'after_cart_items'         => __( 'After the cart items section', 'fluid-checkout' ),
							'before_cart_actions'      => __( 'Before the cart actions', 'fluid-checkout' ),
							'after_cart_actions'       => __( 'After the cart actions', 'fluid-checkout' ),
							'after_order_summary'      => __( 'After the order summary', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_cart_section_position_cross_sells' ),
						'autoload'          => false,
						'type'              => 'fc_select',
						'disabled'          => true,
					),

					array(
						'id'                => 'fc_pro_enable_cart_cross_sells',
						'desc'              => __( 'Layout of cross-sell items on the cart page.', 'fluid-checkout' ),
						'type'              => 'fc_select',
						'options'           => array(
							'yes'           => __( 'Optimized horizontal cross-sells layout', 'fluid-checkout' ),
							'no'            => __( 'Theme\'s cross-sells layout', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_cart_cross_sells' ),
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'desc'              => __( 'Number of cross-sell items to display on the cart page', 'fluid-checkout' ),
						'id'                => 'fc_pro_cart_cross_sells_display_items_limit',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_cart_cross_sells_display_items_limit' ),
						'type'              => 'fc_number',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'title'             => __( 'Trust symbols &amp; badges', 'fluid-checkout' ),
						'desc'              => __( 'Add widget areas to the cart page', 'fluid-checkout' ),
						'desc_tip'          => __( 'These widget areas are used to add trust symbols and trust badges on the cart page.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/feature-trust-symbols-badges/' ),
						'id'                => 'fc_pro_enable_cart_widget_areas',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_cart_widget_areas' ),
						'type'              => 'checkbox',
						'autoload'          => false,
						'disabled'          => true,
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_cart_elements_options',
					),

				)
			);
		}

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_Cart_Settings();
