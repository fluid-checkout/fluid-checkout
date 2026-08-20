<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartFlows (by CartFlows Inc).
 */
class FluidCheckout_Cartflows extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Frontend hooks
		add_action( 'wp', array( $this, 'frontend_hooks' ), 56 ); // Set priority to 56 to run after the CartFlows `wp_actions` method at priority 55

		// Checkout page hooks
		add_action( 'wp', array( $this, 'instant_checkout_layout_hooks' ), 998 ); // Set priority to 998 to run before the CartFlows `instant_checkout_actions` method at priority 999
		add_action( 'wp', array( $this, 'checkout_form_hooks' ), 998 ); // Set priority to 998 to run before the CartFlows `shortcode_load_data` method at priority 999

		// Assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_cartflows_normalize_styles' ), 10000 );

		// Store Checkout thank you page
		add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'maybe_use_woocommerce_thankyou_for_store_checkout' ), 20, 2 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Checkout markup
		$this->checkout_markup_hooks();

		// Modern layout
		$this->modern_checkout_layout_hooks();

		// Checkout fields
		$this->checkout_fields_hooks();
	}

	/**
	 * Add or remove CartFlows checkout markup hooks.
	 */
	public function checkout_markup_hooks() {
		// Bail if CartFlows checkout markup class is unavailable
		if ( ! class_exists( 'Cartflows_Checkout_Markup' ) ) { return; }

		// Get the CartFlows checkout markup object
		$checkout_markup = Cartflows_Checkout_Markup::get_instance();

		// Remove the order summary image and markup changes, which conflict with the Fluid Checkout thumbnails
		remove_filter( 'woocommerce_cart_item_name', array( $checkout_markup, 'modify_order_review_item_summary' ), 10 );

		// Remove the fragment replacements that swap the Fluid Checkout order review HTML for the CartFlows markup
		remove_filter( 'woocommerce_update_order_review_fragments', array( $checkout_markup, 'add_updated_cart_price' ), 10 );

		// Remove the shipping notice HTML wrappers, which break the Fluid Checkout shipping notices
		remove_filter( 'woocommerce_shipping_may_be_available_html', array( $checkout_markup, 'change_shipping_message_html' ), 10 );
		remove_filter( 'woocommerce_no_shipping_available_html', array( $checkout_markup, 'change_shipping_message_html' ), 10 );

		// Note: the `custom_price_to_cart_item` hook is intentionally kept, as it is needed for the funnel product custom and discounted prices
	}

	/**
	 * Add or remove CartFlows Modern checkout layout hooks.
	 */
	public function modern_checkout_layout_hooks() {
		// Bail if CartFlows Modern checkout class is unavailable
		if ( ! class_exists( 'Cartflows_Modern_Checkout' ) ) { return; }

		// Get the CartFlows Modern checkout object
		$modern_checkout = Cartflows_Modern_Checkout::get_instance();

		// Remove the Modern layout changes, which unset the `billing_email` and related fields before Fluid Checkout can output the contact step
		remove_action( 'cartflows_checkout_form_before', array( $modern_checkout, 'modern_checkout_layout_actions' ), 10 );
		remove_filter( 'woocommerce_checkout_fields', array( $modern_checkout, 'unset_fields_for_modern_checkout' ), 10 );
	}

	/**
	 * Add or remove CartFlows checkout fields hooks.
	 */
	public function checkout_fields_hooks() {
		// Bail if CartFlows checkout fields class is unavailable
		if ( ! class_exists( 'Cartflows_Checkout_Fields' ) ) { return; }

		// Get the CartFlows checkout fields object
		$checkout_fields = Cartflows_Checkout_Fields::get_instance();

		// Remove the field layout and skin changes, which conflict with the Fluid Checkout fields
		remove_filter( 'woocommerce_checkout_fields', array( $checkout_fields, 'add_three_column_layout_fields' ), 10 );
		remove_filter( 'woocommerce_checkout_fields', array( $checkout_fields, 'label_skins_fields_customization' ), 1000 );

		// Note: the `billing_fields_customization`, `shipping_fields_customization`, `additional_fields_customization`,
		// `prepare_country_locale` and `woo_default_address_fields` hooks are intentionally kept,
		// so the CartFlows field editor settings still apply.
	}

	/**
	 * Add or remove CartFlows frontend hooks.
	 */
	public function frontend_hooks() {
		// Bail if CartFlows frontend class is unavailable
		if ( ! class_exists( 'Cartflows_Frontend' ) ) { return; }

		// Bail if not on a CartFlows checkout or Fluid Checkout PRO thank you page context
		if ( ! $this->is_cartflows_checkout_context() && ! $this->is_fc_pro_thankyou_context() ) { return; }

		// Get the CartFlows frontend object
		$frontend = Cartflows_Frontend::get_instance();

		// Restore the theme styles and scripts, which CartFlows removes to force the default WooCommerce styles
		remove_action( 'wp_enqueue_scripts', array( $frontend, 'remove_theme_styles' ), 9999 );
		remove_filter( 'woocommerce_enqueue_styles', array( $frontend, 'woo_default_css' ), 9999 );

		// Remove the WooCommerce template overrides, so the Fluid Checkout templates are used
		remove_filter( 'woocommerce_locate_template', array( $frontend, 'override_woo_template' ), 20 );
	}

	/**
	 * Add or remove CartFlows Instant Checkout layout hooks.
	 */
	public function instant_checkout_layout_hooks() {
		// Bail if CartFlows Instant Checkout class is unavailable
		if ( ! class_exists( 'Cartflows_Instant_Checkout' ) ) { return; }

		// Bail if not on a CartFlows checkout context
		if ( ! $this->is_cartflows_checkout_context() ) { return; }

		// Remove the Instant Checkout layout, which is not supported and conflicts with Fluid Checkout
		remove_action( 'wp', array( Cartflows_Instant_Checkout::get_instance(), 'instant_checkout_actions' ), 999 );
	}

	/**
	 * Add or remove CartFlows checkout form hooks.
	 */
	public function checkout_form_hooks() {
		// Bail if CartFlows checkout markup class is unavailable
		if ( ! class_exists( 'Cartflows_Checkout_Markup' ) ) { return; }

		// Bail if not on a CartFlows checkout context
		if ( ! $this->is_cartflows_checkout_context() ) { return; }

		// Get the CartFlows checkout markup object
		$checkout_markup = Cartflows_Checkout_Markup::get_instance();

		// Remove the checkout shortcode setup, which moves the coupon field, re-binds the classic billing and shipping sections, and loads the CartFlows checkout assets
		remove_action( 'wp', array( $checkout_markup, 'shortcode_load_data' ), 999 );

		// Add the identity fields inside the checkout form and login form, which are needed for the thank you page redirect, order meta, AJAX endpoints and post login funnel redirect
		add_action( 'fc_checkout_before', array( $checkout_markup, 'checkout_shortcode_post_id' ), 5 );
		add_action( 'woocommerce_login_form_end', array( $checkout_markup, 'checkout_shortcode_post_id' ), 99 );

		// Add the URL query string field prefill
		add_filter( 'woocommerce_checkout_fields', array( $checkout_markup, 'prefill_checkout_fields' ), 10 );

		// Add the place order button text
		add_filter( 'woocommerce_order_button_text', array( $checkout_markup, 'place_order_button_text' ), 99 ); // Only needed for the initial page load, as the AJAX path is already registered by the CartFlows `update_woo_actions_ajax` method

		// Add the automatic check for "Ship to a different address?" when the shipping URL parameters are present
		add_filter( 'woocommerce_ship_to_different_address_checked', array( $checkout_markup, 'maybe_check_ship_to_different_address' ), 10 );

		// Add the file field type support for the CartFlows custom checkout fields
		add_filter( 'woocommerce_form_field_file', array( $checkout_markup, 'render_file_field' ), 10, 4 );

		// Get the checkout page ID
		$checkout_id = absint( get_the_ID() );

		// Bail if checkout page ID is not available
		if ( ! $checkout_id ) { return; }

		// Run the third party hooks that expect this CartFlows action
		do_action( 'cartflows_checkout_before_shortcode', $checkout_id );
	}



	/**
	 * Whether the current request is a CartFlows checkout page or checkout AJAX.
	 */
	public function is_cartflows_checkout_context() {
		// Return `true` if on a CartFlows checkout step
		if ( function_exists( '_is_wcf_checkout_type' ) && _is_wcf_checkout_type() ) { return true; }

		// Return `true` if doing a CartFlows checkout AJAX request
		if ( function_exists( '_is_wcf_doing_checkout_ajax' ) && _is_wcf_doing_checkout_ajax() ) { return true; }

		return false;
	}

	/**
	 * Whether the current request is a CartFlows thank you step handled by the Fluid Checkout PRO order received page.
	 */
	public function is_fc_pro_thankyou_context() {
		// Bail if not on a CartFlows thank you step
		if ( ! function_exists( '_is_wcf_thankyou_type' ) || ! _is_wcf_thankyou_type() ) { return false; }

		// Bail if Fluid Checkout PRO order received page is not available
		if ( ! class_exists( 'FluidCheckout_PRO_OrderReceivedPage' ) ) { return false; }

		return FluidCheckout_PRO_OrderReceivedPage::instance()->is_feature_enabled();
	}

	/**
	 * Whether an order was placed through the Store / Global Checkout flow.
	 *
	 * @param   WC_Order|mixed  $order  Order object.
	 */
	public function is_store_checkout_order( $order ) {
		// Bail if order is not available
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) { return false; }

		// Bail if CartFlows utils / helper are not available
		if ( ! function_exists( 'wcf' ) || ! wcf()->utils || ! class_exists( 'Cartflows_Helper' ) ) { return false; }

		// Get Store Checkout flow ID
		$store_flow_id = absint( Cartflows_Helper::get_global_setting( '_cartflows_store_checkout' ) );

		// Bail if Store Checkout is not configured
		if ( ! $store_flow_id ) { return false; }

		// Get flow ID from order meta
		$flow_id = absint( wcf()->utils->get_flow_id_from_order( $order ) );

		return $store_flow_id === $flow_id;
	}



	/**
	 * Dequeue CartFlows normalize/frontend styles that conflict with Fluid Checkout.
	 */
	public function maybe_dequeue_cartflows_normalize_styles() {
		// Bail if not on a CartFlows checkout or Fluid Checkout PRO thank you page context
		if ( ! $this->is_cartflows_checkout_context() && ! $this->is_fc_pro_thankyou_context() ) { return; }

		// Dequeue styles
		wp_dequeue_style( 'wcf-normalize-frontend-global' );
		wp_dequeue_style( 'wcf-frontend-global' );
	}



	/**
	 * Use the WooCommerce order received URL for Store / Global Checkout orders.
	 *
	 * Store / Global Checkout only. Sales funnels keep the CartFlows thank you page URL
	 * applied by `Cartflows_Frontend::redirect_to_thankyou_page` (priority 10).
	 * Upsell / downsell redirects from `cartflows_checkout_next_step_id` are preserved.
	 *
	 * @param   string    $order_receive_url  Order received URL.
	 * @param   WC_Order  $order              Order object.
	 */
	public function maybe_use_woocommerce_thankyou_for_store_checkout( $order_receive_url, $order ) {
		// Bail if not a Store / Global Checkout order
		if ( ! $this->is_store_checkout_order( $order ) ) { return $order_receive_url; }

		// Bail if CartFlows utils / flow are not available
		if ( ! function_exists( 'wcf' ) || ! wcf()->flow ) { return $order_receive_url; }

		// Get thank you step permalink
		$thankyou_page_id = absint( wcf()->flow->get_thankyou_page_id( $order ) );
		$thankyou_page_url = $thankyou_page_id ? get_permalink( $thankyou_page_id ) : '';

		// Bail if redirected to a step other than the thank you step (upsell/downsell)
		if ( ! empty( $thankyou_page_url ) && false === strpos( $order_receive_url, $thankyou_page_url ) ) { return $order_receive_url; }

		// Build the native WooCommerce order received URL (FC PRO can style this page)
		$woocommerce_order_received_url = wc_get_endpoint_url( 'order-received', $order->get_id(), wc_get_checkout_url() );

		return add_query_arg( 'key', $order->get_order_key(), $woocommerce_order_received_url );
	}

}

FluidCheckout_Cartflows::instance();
