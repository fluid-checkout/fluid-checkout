<?php
/**
 * Fluid Checkout Cart Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_Cart', false ) ) {
	FluidCheckout_Settings_Cart::hooks();
	return;
}

/**
 * FluidCheckout_Settings_Cart.
 */
class FluidCheckout_Settings_Cart {

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
	public static function add_settings( $settings, $current_section ) {
		if ( 'cart' === $current_section ) {

			/**
			 * Filters the cart settings.
			 *
			 * @since 3.0.0
			 *
			 * @param array $settings Settings to output.
			 */
			$settings = apply_filters(
				'fc_pro_cart_settings',
				array_merge(
					self::get_cart_promo_settings(),
					array(

					array(
						'title' => __( 'Cart layout', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_pro_cart_layout_options',
						'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'cart-layout' ),
					),

					array(
						'title'             => __( 'Cart optimizations', 'fluid-checkout' ),
						'desc'              => __( 'Enable cart page optimizations', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_cart_page',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_cart_page' ),
						'type'              => 'checkbox',
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
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
						'id'   => 'fc_pro_cart_layout_options',
					),



					array(
						'title' => __( 'Cart elements', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_pro_cart_elements_options',
						'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'cart-elements' ),
					),

					array(
						'type'              => 'fc_paragraph',
						'desc'              => __( 'Enable cart page optimizations to see more options', 'fluid-checkout' ),
						'id'                => 'fc_pro_cart_elements_enable_notice',
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_cart_page',
							'data-conditional-value' => 'no',
						),
						'requires'          => 'pro',
						'unlocked_only'     => true,
					),

					array(
						'title'             => __( 'Order summary', 'fluid-checkout' ),
						'desc'              => __( 'Sticky order summary', 'fluid-checkout' ),
						'desc_tip'          => __( 'Make the order summary stay visible on the cart page while scrolling', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_cart_sticky_order_summary',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_cart_sticky_order_summary' ),
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
						'title'             => __( 'Coupon codes', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Select position where to display the coupon codes section on the cart page. Only applicable when AJAX cart is enabled, and if use of coupon codes are enabled in the WooCommerce settings.', 'fluid-checkout' ),
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
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_cart_page',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'title'             => __( 'Shipping', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Select position where to display the shipping costs and calculator on the cart page.', 'fluid-checkout' ),
						'id'                => 'fc_pro_cart_section_position_shipping',
						'type'              => 'fc_select',
						'options'           => array(
							'none'                     => __( 'Hidden', 'fluid-checkout' ),
							'cart_section'             => __( 'Display as a cart section', 'fluid-checkout' ),
							'inside_order_summary'     => __( 'Inside the order summary', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_cart_section_position_shipping' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_cart_page',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'title'             => __( 'Cross-sells', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Select position where to display the cross-sells section on the cart page.', 'fluid-checkout' ),
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
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_cart_page',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'type'              => 'fc_select',
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'id'                => 'fc_pro_enable_cart_cross_sells',
						'title'             => __( 'Layout of cross-sell', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Layout of cross-sell items on the cart page.', 'fluid-checkout' ),
						'type'              => 'fc_select',
						'options'           => array(
							'yes'           => __( 'Optimized horizontal cross-sells layout', 'fluid-checkout' ),
							'no'            => __( 'Theme\'s cross-sells layout', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_cart_cross_sells' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_cart_page',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'title'             => __( 'Cross-sells displayed', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Number of cross-sell items to display on the cart page', 'fluid-checkout' ),
						'id'                => 'fc_pro_cart_cross_sells_display_items_limit',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_cart_cross_sells_display_items_limit' ),
						'type'              => 'fc_number',
						'suffix_label'      => __( 'Items', 'fluid-checkout' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_cart_cross_sells',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_cart_elements_options',
					),



					array(
						'title' => __( 'Side cart', 'fluid-checkout' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'fc_pro_side_cart_options',
						'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'side-cart' ),
					),

					array(
						'title'             => __( 'Side cart', 'fluid-checkout' ),
						'desc'              => __( 'Enable the side cart drawer', 'fluid-checkout' ),
						'desc_tip'          => __( 'Replaces the mini cart from WooCommerce and from the currently active theme with a drawer that slides in from the side of the screen. Does not apply to the cart and checkout pages.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_side_cart',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_side_cart' ),
						'type'              => 'checkbox',
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'type'              => 'fc_paragraph',
						'desc'              => __( 'Enable the side cart drawer to see more options', 'fluid-checkout' ),
						'id'                => 'fc_pro_side_cart_enable_notice',
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_side_cart',
							'data-conditional-value' => 'no',
						),
						'requires'          => 'pro',
						'unlocked_only'     => true,
					),

					array(
						'title'             => __( 'Position', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Select which side of the screen the side cart drawer slides in from. Automatically reversed when using a right-to-left language.', 'fluid-checkout' ),
						'id'                => 'fc_pro_side_cart_position',
						'type'              => 'fc_select',
						'options'           => array(
							'right'                    => __( 'Right', 'fluid-checkout' ),
							'left'                     => __( 'Left', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_side_cart_position' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_side_cart',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'title'             => __( 'Add to cart', 'fluid-checkout' ),
						'desc'              => __( 'Open the side cart after adding a product to the cart on desktop devices', 'fluid-checkout' ),
						'desc_tip'          => __( 'Does not open automatically on mobile devices, where the drawer covers the full screen and would interrupt shopping.', 'fluid-checkout' ),
						'id'                => 'fc_pro_side_cart_open_after_add_to_cart',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_side_cart_open_after_add_to_cart' ),
						'type'              => 'checkbox',
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_side_cart',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'title'             => __( 'Floating button', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Select where to display a floating button that opens the side cart. Useful when the currently active theme does not display a cart button in its header.', 'fluid-checkout' ),
						'id'                => 'fc_pro_side_cart_floating_button_position',
						'type'              => 'fc_select',
						'options'           => array(
							'none'                     => __( 'Hidden', 'fluid-checkout' ),
							'bottom_right'             => __( 'Bottom right', 'fluid-checkout' ),
							'bottom_left'              => __( 'Bottom left', 'fluid-checkout' ),
							'top_right'                => __( 'Top right', 'fluid-checkout' ),
							'top_left'                 => __( 'Top left', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_side_cart_floating_button_position' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_side_cart',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'title'             => __( 'Coupon codes', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Select position where to display the coupon codes section in the side cart. Only applicable if use of coupon codes are enabled in the WooCommerce settings.', 'fluid-checkout' ),
						'id'                => 'fc_pro_side_cart_section_position_coupon_code',
						'type'              => 'fc_select',
						'options'           => array(
							'none'                     => __( 'Hidden', 'fluid-checkout' ),
							'after_cart_items'         => __( 'After the cart items', 'fluid-checkout' ),
							'footer'                   => __( 'In the side cart footer', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_side_cart_section_position_coupon_code' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_side_cart',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'title'             => __( 'Cross-sells', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Select position where to display the cross-sells section in the side cart.', 'fluid-checkout' ),
						'id'                => 'fc_pro_side_cart_section_position_cross_sells',
						'type'              => 'fc_select',
						'options'           => array(
							'none'                     => __( 'Hidden', 'fluid-checkout' ),
							'after_cart_items'         => __( 'After the cart items', 'fluid-checkout' ),
							'footer'                   => __( 'In the side cart footer', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_side_cart_section_position_cross_sells' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_side_cart',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => 'pro',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'fc_pro_side_cart_options',
					),

					)
				)
			);
		}

		return $settings;
	}

	/**
	 * Get the promotional settings card shown at the top of the Cart tab when PRO is not active.
	 */
	public static function get_cart_promo_settings() {
		// Bail if PRO is already activated
		if ( FluidCheckout::instance()->is_pro_activated() ) { return array(); }

		return array(
			array(
				'title'            => __( 'Cart page optimization', 'fluid-checkout' ),
				'type'             => 'fc_promo',
				'id'               => 'fc_pro_cart_promo',
				'is_card'          => true,
				'promo'            => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'cart-promo' ),
				'tagline'          => __( 'Give your cart the same clear, conversion-focused layout as your checkout.', 'fluid-checkout' ),
				'features'         => array(
					__( 'Match the cart layout to the checkout page for a consistent purchase journey.', 'fluid-checkout' ),
					__( 'Keep the cart always updated in the background, no full page reloads when quantities change.', 'fluid-checkout' ),
					__( 'Place coupon codes, shipping, and cross-sells exactly where they convert best.', 'fluid-checkout' ),
					__( 'Keep the order summary visible while scrolling, and add trust symbols where shoppers need reassurance.', 'fluid-checkout' ),
				),
				'learn_more_url'   => 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=cart-promo-learn-more&mtm_source=lite-plugin',
				'learn_more_label' => __( 'Learn more', 'fluid-checkout' ),
				'plugin_file'      => 'fluid-checkout-pro/fluid-checkout-pro.php',
				'plugin_slug'      => 'fluid-checkout-pro',
				'purchase_url'     => 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=cart-promo-purchase&mtm_source=lite-plugin',
				'purchase_price'   => FluidCheckout_Admin::instance()->get_pro_purchase_price(),
			),
		);
	}

}

FluidCheckout_Settings_Cart::hooks();
