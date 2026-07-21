<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartFlows (by CartFlows Inc).
 *
 * Fluid Checkout owns checkout UI; CartFlows keeps routing, cart products, and `_wcf_*` identity.
 * Instant Layout is not supported and should remain deactivated.
 * Store / Global Checkout: redirect to WooCommerce order-received (FC PRO thank you); keep upsell/downsell redirects.
 * Sales funnels: keep CartFlows thank-you redirects; FC PRO can optimize the embedded `thankyou.php`.
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

		// Page setup (after CF `wp_actions` at priority 55; before CF `shortcode_load_data` at 999)
		add_action( 'wp', array( $this, 'maybe_prepare_cartflows_page' ), 56 );
		add_action( 'wp', array( $this, 'maybe_take_over_cartflows_checkout_ui' ), 998 );

		// Assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_cartflows_normalize_styles' ), 10000 );

		// Store Checkout thank you
		add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'maybe_use_woocommerce_thankyou_for_store_checkout' ), 20, 2 );
	}



	/**
	 * Late hooks that must run after CartFlows registers field filters.
	 */
	public function late_hooks() {
		// Prevent CartFlows order-summary image / markup surgery (conflicts with Fluid Checkout thumbnails),
		// fragment replacements that swap FC order review HTML for CartFlows markup,
		// and shipping notice HTML wrappers that break FC shipping notices
		if ( class_exists( 'Cartflows_Checkout_Markup' ) ) {
			$checkout_markup = Cartflows_Checkout_Markup::get_instance();
			remove_filter( 'woocommerce_cart_item_name', array( $checkout_markup, 'modify_order_review_item_summary' ), 10 );
			remove_filter( 'woocommerce_update_order_review_fragments', array( $checkout_markup, 'add_updated_cart_price' ), 10 );
			remove_filter( 'woocommerce_shipping_may_be_available_html', array( $checkout_markup, 'change_shipping_message_html' ) );
			remove_filter( 'woocommerce_no_shipping_available_html', array( $checkout_markup, 'change_shipping_message_html' ) );
			// Keep `custom_price_to_cart_item` — needed for funnel product custom/discounted prices
		}

		// Prevent CartFlows Modern layout from unsetting billing_email (and related fields)
		// before Fluid Checkout can render the contact step
		if ( class_exists( 'Cartflows_Modern_Checkout' ) ) {
			$modern_checkout = Cartflows_Modern_Checkout::get_instance();
			remove_action( 'cartflows_checkout_form_before', array( $modern_checkout, 'modern_checkout_layout_actions' ), 10 );
			remove_filter( 'woocommerce_checkout_fields', array( $modern_checkout, 'unset_fields_for_modern_checkout' ), 10 );
		}

		// Prevent CartFlows field layout / skin customizations that conflict with Fluid Checkout fields.
		// Keep functional field filters (`billing_fields_customization`, `shipping_fields_customization`,
		// `additional_fields_customization`, `prepare_country_locale`, `woo_default_address_fields`)
		// so CartFlows field-editor config still applies.
		if ( class_exists( 'Cartflows_Checkout_Fields' ) ) {
			$checkout_fields = Cartflows_Checkout_Fields::get_instance();
			remove_filter( 'woocommerce_checkout_fields', array( $checkout_fields, 'add_three_column_layout_fields' ) );
			remove_filter( 'woocommerce_checkout_fields', array( $checkout_fields, 'label_skins_fields_customization' ), 1000 );
		}
	}



	/**
	 * Whether the current request is a CartFlows checkout page or checkout AJAX.
	 */
	public function is_cartflows_checkout_context() {
		if ( function_exists( '_is_wcf_checkout_type' ) && _is_wcf_checkout_type() ) {
			return true;
		}

		if ( function_exists( '_is_wcf_doing_checkout_ajax' ) && _is_wcf_doing_checkout_ajax() ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the current request is a CartFlows thank-you step.
	 *
	 * Assumes Instant Layout is deactivated (unsupported).
	 */
	public function is_cartflows_thankyou_context() {
		return function_exists( '_is_wcf_thankyou_type' ) && _is_wcf_thankyou_type();
	}

	/**
	 * Whether Fluid Checkout PRO order-received is available and enabled.
	 */
	public function is_fc_pro_order_received_enabled() {
		// Bail if Fluid Checkout PRO order-received is not available
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
	 * Prepare CartFlows checkout and thank-you pages for Fluid Checkout.
	 *
	 * Runs after CF `wp_actions` (priority 55): restore theme assets, and on thank-you
	 * steps remove CF template overrides so FC PRO can use its `thankyou.php`.
	 */
	public function maybe_prepare_cartflows_page() {
		$is_checkout = $this->is_cartflows_checkout_context();
		$is_thankyou_for_fc_pro = $this->is_cartflows_thankyou_context() && $this->is_fc_pro_order_received_enabled();

		// Bail if not on a CartFlows checkout or FC PRO thank-you context
		if ( ! $is_checkout && ! $is_thankyou_for_fc_pro ) { return; }

		// Restore theme styles/scripts (CF strips them and forces default WooCommerce CSS)
		if ( class_exists( 'Cartflows_Frontend' ) ) {
			$frontend = Cartflows_Frontend::get_instance();
			remove_action( 'wp_enqueue_scripts', array( $frontend, 'remove_theme_styles' ), 9999 );
			remove_filter( 'woocommerce_enqueue_styles', array( $frontend, 'woo_default_css' ), 9999 );
		}

		// Thank you: remove CF WooCommerce template overrides so FC PRO can use its templates
		if ( $is_thankyou_for_fc_pro ) {
			$this->maybe_remove_cartflows_template_overrides();
		}
	}

	/**
	 * Remove CartFlows WooCommerce template overrides so Fluid Checkout templates are used.
	 */
	public function maybe_remove_cartflows_template_overrides() {
		// Bail if CartFlows frontend is not available
		if ( ! class_exists( 'Cartflows_Frontend' ) ) { return; }

		remove_filter( 'woocommerce_locate_template', array( Cartflows_Frontend::get_instance(), 'override_woo_template' ), 20 );
	}



	/**
	 * Prevent CartFlows checkout UI bootstrap and restore needed CartFlows behaviors.
	 *
	 * Runs before CF `shortcode_load_data` (priority 999).
	 */
	public function maybe_take_over_cartflows_checkout_ui() {
		// Bail if not on a CartFlows checkout context
		if ( ! $this->is_cartflows_checkout_context() ) { return; }

		// Prevent CartFlows shortcode UI bootstrap (coupon move, classic billing/shipping re-bind, checkout assets)
		// then restore identity fields and other behaviors still needed with Fluid Checkout
		if ( class_exists( 'Cartflows_Checkout_Markup' ) ) {
			$checkout_markup = Cartflows_Checkout_Markup::get_instance();
			remove_action( 'wp', array( $checkout_markup, 'shortcode_load_data' ), 999 );

			// Identity fields inside the checkout form and login form
			// (needed for thank-you redirect, order meta, AJAX endpoints, and post-login funnel redirect)
			add_action( 'fc_checkout_before', array( $checkout_markup, 'checkout_shortcode_post_id' ), 5 );
			add_action( 'woocommerce_login_form_end', array( $checkout_markup, 'checkout_shortcode_post_id' ), 99 );

			// URL query-string field prefill
			add_filter( 'woocommerce_checkout_fields', array( $checkout_markup, 'prefill_checkout_fields' ), 10 );

			// Place order button text on initial page load
			// (AJAX path is already registered by CF `update_woo_actions_ajax`)
			add_filter( 'woocommerce_order_button_text', array( $checkout_markup, 'place_order_button_text' ), 99 );

			// Auto-check "Ship to a different address?" when shipping URL params are present
			add_filter( 'woocommerce_ship_to_different_address_checked', array( $checkout_markup, 'maybe_check_ship_to_different_address' ) );

			// File field type support for CartFlows custom checkout fields
			add_filter( 'woocommerce_form_field_file', array( $checkout_markup, 'render_file_field' ), 10, 4 );

			// Allow third-party CartFlows hooks that expect this action
			$checkout_id = absint( get_the_ID() );
			if ( $checkout_id ) {
				do_action( 'cartflows_checkout_before_shortcode', $checkout_id );
			}
		}

		// Remove CartFlows WooCommerce template overrides so Fluid Checkout templates are used
		$this->maybe_remove_cartflows_template_overrides();
	}



	/**
	 * Dequeue CartFlows normalize/frontend styles that conflict with Fluid Checkout.
	 */
	public function maybe_dequeue_cartflows_normalize_styles() {
		// Dequeue on CartFlows checkout
		$should_dequeue = $this->is_cartflows_checkout_context();

		// Or on CartFlows thank-you steps when FC PRO order-received is enabled
		if ( ! $should_dequeue && $this->is_cartflows_thankyou_context() && $this->is_fc_pro_order_received_enabled() ) {
			$should_dequeue = true;
		}

		// Bail if not on a context that needs normalize/frontend removed
		if ( ! $should_dequeue ) { return; }

		wp_dequeue_style( 'wcf-normalize-frontend-global' );
		wp_dequeue_style( 'wcf-frontend-global' );
	}



	/**
	 * Use the WooCommerce order-received URL for Store / Global Checkout orders.
	 *
	 * Store / Global Checkout only. Sales funnels keep the CartFlows thank-you URL
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

		// Bail if redirected to a non-thank-you step (upsell/downsell)
		$thankyou_page_id = absint( wcf()->flow->get_thankyou_page_id( $order ) );
		if ( $thankyou_page_id && false === strpos( $order_receive_url, (string) get_permalink( $thankyou_page_id ) ) ) { return $order_receive_url; }

		// Build the native WooCommerce order-received URL (FC PRO can style this page)
		$woocommerce_order_received_url = wc_get_endpoint_url( 'order-received', $order->get_id(), wc_get_checkout_url() );

		return add_query_arg( 'key', $order->get_order_key(), $woocommerce_order_received_url );
	}

}

FluidCheckout_Cartflows::instance();
