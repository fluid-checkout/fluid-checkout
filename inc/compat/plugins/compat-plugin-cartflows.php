<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartFlows (by CartFlows Inc).
 *
 * Shared: Fluid Checkout owns checkout UI; CartFlows keeps routing, cart products, and `_wcf_*` identity.
 * Store / Global Checkout: redirect to WooCommerce order-received (FC PRO thank you); keep upsell/downsell redirects.
 * Sales funnels: keep CartFlows thank-you redirects; Instant TY disables FC PRO; non-Instant TY allows FC PRO.
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

		// Theme assets (CF strips them at `wp` priority 55)
		add_action( 'wp', array( $this, 'maybe_restore_theme_assets' ), 56 );

		// Checkout UI (must run before CF `shortcode_load_data` at priority 999)
		add_action( 'wp', array( $this, 'maybe_take_over_cartflows_checkout_ui' ), 998 );

		// Assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_cartflows_normalize_styles' ), 10000 );

		// Store Checkout thank you
		add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'maybe_use_woocommerce_thankyou_for_store_checkout' ), 20, 2 );

		// Funnel thank you
		add_filter( 'fc_pro_enable_order_received', array( $this, 'maybe_disable_fc_pro_on_instant_thankyou' ), 10 );
		add_action( 'wp', array( $this, 'maybe_prepare_cartflows_thankyou_for_fc_pro' ), 56 );
	}



	/**
	 * Late hooks that must run after CartFlows registers field filters.
	 *
	 * Shared: Store Checkout + sales funnel checkouts.
	 */
	public function late_hooks() {
		// Prevent CartFlows order-summary image / markup surgery (conflicts with Fluid Checkout thumbnails)
		// and fragment replacements that swap FC order review HTML for CartFlows markup
		if ( class_exists( 'Cartflows_Checkout_Markup' ) ) {
			$checkout_markup = Cartflows_Checkout_Markup::get_instance();
			remove_filter( 'woocommerce_cart_item_name', array( $checkout_markup, 'modify_order_review_item_summary' ), 10 );
			remove_filter( 'woocommerce_update_order_review_fragments', array( $checkout_markup, 'add_updated_cart_price' ), 10 );
			// Keep `custom_price_to_cart_item` — needed for funnel product custom/discounted prices
		}

		// Prevent CartFlows Modern layout from unsetting billing_email (and related fields)
		// before Fluid Checkout can render the contact step
		if ( class_exists( 'Cartflows_Modern_Checkout' ) ) {
			$modern_checkout = Cartflows_Modern_Checkout::get_instance();
			remove_action( 'cartflows_checkout_form_before', array( $modern_checkout, 'modern_checkout_layout_actions' ), 10 );
			remove_filter( 'woocommerce_checkout_fields', array( $modern_checkout, 'unset_fields_for_modern_checkout' ), 10 );
		}

		// Prevent CartFlows field layout / skin customizations that conflict with Fluid Checkout fields
		if ( class_exists( 'Cartflows_Checkout_Fields' ) ) {
			$checkout_fields = Cartflows_Checkout_Fields::get_instance();
			remove_filter( 'woocommerce_checkout_fields', array( $checkout_fields, 'add_three_column_layout_fields' ) );
			remove_filter( 'woocommerce_checkout_fields', array( $checkout_fields, 'label_skins_fields_customization' ), 1000 );
			remove_filter( 'woocommerce_checkout_fields', array( $checkout_fields, 'additional_fields_customization' ), 1000 );
			remove_filter( 'woocommerce_billing_fields', array( $checkout_fields, 'billing_fields_customization' ), 1000 );
			remove_filter( 'woocommerce_shipping_fields', array( $checkout_fields, 'shipping_fields_customization' ), 1000 );
			remove_filter( 'woocommerce_get_country_locale_default', array( $checkout_fields, 'prepare_country_locale' ) );
			remove_filter( 'woocommerce_default_address_fields', array( $checkout_fields, 'woo_default_address_fields' ), 1000 );
		}
	}



	/**
	 * Whether the current request is a CartFlows checkout page or checkout AJAX.
	 *
	 * Shared: Store Checkout + sales funnel checkouts.
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
	 */
	public function is_cartflows_thankyou_context() {
		return function_exists( '_is_wcf_thankyou_type' ) && _is_wcf_thankyou_type();
	}

	/**
	 * Whether Instant Layout is enabled for a flow.
	 *
	 * @param   int  $flow_id  Optional flow ID. Defaults to the current flow.
	 */
	public function is_instant_layout_flow( $flow_id = 0 ) {
		// Bail if CartFlows helper is not available
		if ( ! class_exists( 'Cartflows_Helper' ) ) { return false; }

		// Maybe use the current flow ID
		if ( ! $flow_id ) {
			// Bail if CartFlows utils are not available
			if ( ! function_exists( 'wcf' ) || ! wcf()->utils ) { return false; }

			$flow_id = absint( wcf()->utils->get_flow_id() );
		}

		// Bail if flow ID is not available
		if ( ! $flow_id ) { return false; }

		return Cartflows_Helper::is_instant_layout_enabled( (int) $flow_id );
	}

	/**
	 * Whether the current request is a CartFlows thank-you step without Instant Layout.
	 *
	 * Sales funnels: non-Instant thank-you pages embed WooCommerce `thankyou.php`,
	 * which Fluid Checkout PRO can optimize.
	 */
	public function is_non_instant_cartflows_thankyou_context() {
		return $this->is_cartflows_thankyou_context() && ! $this->is_instant_layout_flow();
	}

	/**
	 * Whether Fluid Checkout PRO order-received is available and enabled.
	 *
	 * Reads the option directly (does not re-apply `fc_pro_enable_order_received`)
	 * to avoid recursion with `maybe_disable_fc_pro_on_instant_thankyou`.
	 */
	public function is_fc_pro_order_received_enabled() {
		// Bail if Fluid Checkout PRO order-received is not available
		if ( ! class_exists( 'FluidCheckout_PRO_OrderReceivedPage' ) ) { return false; }

		return 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_pro_enable_order_received' );
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
	 * Stop CartFlows from removing theme styles/scripts and forcing default WooCommerce CSS.
	 *
	 * Shared: Store Checkout + sales funnel checkouts.
	 * Funnels: also on non-Instant thank-you steps so FC PRO order-received can use theme styles.
	 */
	public function maybe_restore_theme_assets() {
		// Bail if not on a CartFlows checkout or eligible thank-you context
		if ( ! $this->is_cartflows_checkout_context() && ! $this->is_non_instant_cartflows_thankyou_context() ) { return; }

		// Bail if CartFlows frontend is not available
		if ( ! class_exists( 'Cartflows_Frontend' ) ) { return; }

		// Get CartFlows frontend instance
		$frontend = Cartflows_Frontend::get_instance();

		// CartFlows registers these in `wp_actions` (priority 55) on step post types
		remove_action( 'wp_enqueue_scripts', array( $frontend, 'remove_theme_styles' ), 9999 );
		remove_filter( 'woocommerce_enqueue_styles', array( $frontend, 'woo_default_css' ), 9999 );
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
	 * Prevent CartFlows checkout UI bootstrap and restore identity fields.
	 *
	 * Shared: Store Checkout + sales funnel checkouts.
	 * Runs before CF `shortcode_load_data` / Instant actions (priority 999).
	 * Template override is already registered by CF `wp_actions` (priority 55).
	 */
	public function maybe_take_over_cartflows_checkout_ui() {
		// Bail if not on a CartFlows checkout context
		if ( ! $this->is_cartflows_checkout_context() ) { return; }

		// Prevent CartFlows shortcode UI bootstrap; restore identity fields and URL field prefill
		// (needed for thank-you redirect, order meta, AJAX endpoints, and funnel query-string prefill)
		if ( class_exists( 'Cartflows_Checkout_Markup' ) ) {
			$checkout_markup = Cartflows_Checkout_Markup::get_instance();
			remove_action( 'wp', array( $checkout_markup, 'shortcode_load_data' ), 999 );
			add_action( 'fc_checkout_before', array( $checkout_markup, 'checkout_shortcode_post_id' ), 5 );
			add_filter( 'woocommerce_checkout_fields', array( $checkout_markup, 'prefill_checkout_fields' ), 10 );
		}

		// Prevent Instant Checkout layout actions and page template override
		if ( class_exists( 'Cartflows_Instant_Checkout' ) ) {
			$instant_checkout = Cartflows_Instant_Checkout::get_instance();
			remove_action( 'wp', array( $instant_checkout, 'instant_checkout_actions' ), 999 );
			remove_filter( 'cartflows_page_template_file', array( $instant_checkout, 'cartflows_instant_checkout_page_template_file' ), 10 );
			remove_filter( 'cartflows_checkout_meta_wcf-checkout-layout', array( $instant_checkout, 'stop_other_checkout_layout_implementations' ), 10 );
		}

		// Remove CartFlows WooCommerce template overrides so Fluid Checkout templates are used
		$this->maybe_remove_cartflows_template_overrides();
	}



	/**
	 * Dequeue CartFlows normalize/frontend styles that conflict with Fluid Checkout.
	 *
	 * Shared checkout: CF normalize replaces theme form-field styles.
	 * Sales funnels (non-Instant thank you): CF normalize breaks FC PRO order-received layout.
	 */
	public function maybe_dequeue_cartflows_normalize_styles() {
		// Dequeue on CartFlows checkout
		$should_dequeue = $this->is_cartflows_checkout_context();

		// Or on non-Instant CartFlows thank-you steps when FC PRO order-received is enabled
		if ( ! $should_dequeue && $this->is_non_instant_cartflows_thankyou_context() && $this->is_fc_pro_order_received_enabled() ) {
			$should_dequeue = true;
		}

		// Bail if not on a context that needs normalize/frontend removed
		if ( ! $should_dequeue ) { return; }

		wp_dequeue_style( 'wcf-normalize-frontend-global' );
		wp_dequeue_style( 'wcf-frontend-global' );
	}



	/**
	 * Prepare non-Instant CartFlows thank-you steps for Fluid Checkout PRO.
	 *
	 * Sales funnels: remove CartFlows WooCommerce template overrides so FC PRO
	 * `thankyou.php` / order-details templates are used instead of CartFlows copies.
	 */
	public function maybe_prepare_cartflows_thankyou_for_fc_pro() {
		// Bail if not on a non-Instant CartFlows thank-you step
		if ( ! $this->is_non_instant_cartflows_thankyou_context() ) { return; }

		// Bail if Fluid Checkout PRO order-received is not enabled
		if ( ! $this->is_fc_pro_order_received_enabled() ) { return; }

		$this->maybe_remove_cartflows_template_overrides();
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



	/**
	 * Disable Fluid Checkout PRO order-received on CartFlows Instant Thank You pages.
	 *
	 * Sales funnels with Instant Layout: CartFlows Instant Thank You owns the UI.
	 * Without Instant Layout: CartFlows embeds `thankyou.php`, so FC PRO stays enabled.
	 *
	 * @param   bool  $enabled  Whether FC PRO order-received is enabled.
	 */
	public function maybe_disable_fc_pro_on_instant_thankyou( $enabled ) {
		// Bail if not on a CartFlows thank-you step
		if ( ! $this->is_cartflows_thankyou_context() ) { return $enabled; }

		// Bail if Instant Layout is not enabled
		if ( ! $this->is_instant_layout_flow() ) { return $enabled; }

		return false;
	}

}

FluidCheckout_Cartflows::instance();
