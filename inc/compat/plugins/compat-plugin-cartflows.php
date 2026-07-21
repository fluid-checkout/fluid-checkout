<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartFlows (by CartFlows Inc).
 *
 * Shared (Store Checkout + sales funnels):
 * - Fluid Checkout owns the checkout form UI on CartFlows checkout steps.
 * - CartFlows keeps funnel routing, Store Checkout post-swap, cart products,
 *   and `_wcf_*` order meta / identity fields.
 *
 * Store / Global Checkout:
 * - After place order, use the WooCommerce order-received URL so Fluid Checkout
 *   PRO thank you can render (do not send shoppers to the Store Checkout thank-you step).
 *
 * Sales funnels:
 * - Keep CartFlows thank-you redirects to the funnel thank-you step.
 * - Instant Layout thank you: CartFlows Instant Thank You owns the UI (disable FC PRO).
 * - Non-Instant thank you: allow FC PRO to optimize the embedded WooCommerce thankyou.php,
 *   keep theme assets, and dequeue CartFlows normalize/frontend styles that break layout.
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
		// Shared — checkout field filters must be removed before WooCommerce caches them
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Shared — undo CartFlows theme-asset stripping (CF `wp_actions` at priority 55)
		add_action( 'wp', array( $this, 'maybe_restore_theme_assets' ), 56 );

		// Shared — neutralize CartFlows checkout UI before it bootstraps (CF runs at priority 999)
		add_action( 'wp', array( $this, 'before_cartflows_checkout_ui' ), 998 );

		// Shared — finish neutralization and restore CartFlows identity fields
		add_action( 'wp', array( $this, 'after_cartflows_checkout_ui' ), 1000 );

		// Shared — dequeue CartFlows checkout assets that conflict with Fluid Checkout
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_cartflows_checkout_assets' ), 10000 );

		// Shared — keep theme assets available for Fluid Checkout on CartFlows templates
		add_filter( 'cartflows_remove_theme_styles', array( $this, 'maybe_keep_theme_assets' ), 100 );
		add_filter( 'cartflows_remove_theme_scripts', array( $this, 'maybe_keep_theme_assets' ), 100 );

		// Store / Global Checkout — prefer WooCommerce / FC PRO thank you over Store Checkout thank-you step
		add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'maybe_use_woocommerce_thankyou_for_store_checkout' ), 20, 2 );

		// Sales funnels — disable FC PRO only on Instant Thank You (non-Instant can use FC PRO)
		add_filter( 'fc_pro_enable_order_received', array( $this, 'maybe_disable_fc_pro_order_received_on_cartflows_thankyou' ), 10 );

		// Sales funnels — prepare non-Instant thank you for FC PRO (templates + assets)
		add_action( 'wp', array( $this, 'maybe_prepare_cartflows_thankyou_for_fc_pro' ), 56 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_cartflows_thankyou_assets' ), 10000 );
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
	 * Get the Store / Global Checkout flow ID.
	 */
	public function get_store_checkout_flow_id() {
		// Bail if CartFlows helper is not available
		if ( ! class_exists( 'Cartflows_Helper' ) ) { return 0; }

		return absint( Cartflows_Helper::get_global_setting( '_cartflows_store_checkout' ) );
	}

	/**
	 * Get the current CartFlows flow ID when available.
	 */
	public function get_current_flow_id() {
		// Bail if CartFlows utils are not available
		if ( ! function_exists( 'wcf' ) || ! wcf()->utils ) { return 0; }

		return absint( wcf()->utils->get_flow_id() );
	}

	/**
	 * Whether a flow ID is the Store / Global Checkout flow.
	 *
	 * @param   int  $flow_id  Optional flow ID. Defaults to the current flow.
	 */
	public function is_store_checkout_flow( $flow_id = 0 ) {
		// Get Store Checkout flow ID
		$store_flow_id = $this->get_store_checkout_flow_id();

		// Bail if Store Checkout is not configured
		if ( ! $store_flow_id ) { return false; }

		// Maybe use the current flow ID
		if ( ! $flow_id ) {
			$flow_id = $this->get_current_flow_id();
		}

		return $store_flow_id === absint( $flow_id );
	}

	/**
	 * Whether an order was placed through the Store / Global Checkout flow.
	 *
	 * @param   WC_Order|mixed  $order  Order object.
	 */
	public function is_store_checkout_order( $order ) {
		// Bail if order is not available
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) { return false; }

		// Bail if CartFlows utils are not available
		if ( ! function_exists( 'wcf' ) || ! wcf()->utils ) { return false; }

		// Get flow ID from order meta
		$flow_id = wcf()->utils->get_flow_id_from_order( $order );

		return $this->is_store_checkout_flow( $flow_id );
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
	 *
	 * Shared: Store Checkout thank-you step and sales funnel thank-you steps.
	 */
	public function is_cartflows_thankyou_context() {
		return function_exists( '_is_wcf_thankyou_type' ) && _is_wcf_thankyou_type();
	}

	/**
	 * Whether the current request is the Store / Global Checkout step.
	 */
	public function is_store_checkout_context() {
		return $this->is_cartflows_checkout_context() && $this->is_store_checkout_flow();
	}

	/**
	 * Whether the current request is a sales-funnel checkout step (not Store Checkout).
	 */
	public function is_funnel_checkout_context() {
		return $this->is_cartflows_checkout_context() && ! $this->is_store_checkout_flow();
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
			$flow_id = $this->get_current_flow_id();
		}

		// Bail if flow ID is not available
		if ( ! $flow_id ) { return false; }

		return Cartflows_Helper::is_instant_layout_enabled( (int) $flow_id );
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
	 * Whether the current request is a CartFlows thank-you step without Instant Layout.
	 *
	 * Sales funnels: non-Instant thank-you pages embed WooCommerce `thankyou.php`,
	 * which Fluid Checkout PRO can optimize.
	 */
	public function is_non_instant_cartflows_thankyou_context() {
		return $this->is_cartflows_thankyou_context() && ! $this->is_instant_layout_flow();
	}



	/**
	 * Prevent CartFlows checkout UI bootstrap (layouts, hook surgery, assets).
	 *
	 * Shared: Store Checkout + sales funnel checkouts.
	 */
	public function before_cartflows_checkout_ui() {
		// Bail if not on a CartFlows checkout context
		if ( ! $this->is_cartflows_checkout_context() ) { return; }

		// Bail if CartFlows checkout markup is not available
		if ( ! class_exists( 'Cartflows_Checkout_Markup' ) ) { return; }

		// Get CartFlows checkout markup instance
		$checkout_markup = Cartflows_Checkout_Markup::get_instance();

		// Prevent CartFlows shortcode UI bootstrap (coupon move, classic billing/shipping re-bind, checkout assets)
		remove_action( 'wp', array( $checkout_markup, 'shortcode_load_data' ), 999 );

		// Prevent Instant Checkout layout actions and page template override
		if ( class_exists( 'Cartflows_Instant_Checkout' ) ) {
			$instant_checkout = Cartflows_Instant_Checkout::get_instance();
			remove_action( 'wp', array( $instant_checkout, 'instant_checkout_actions' ), 999 );
			remove_filter( 'cartflows_page_template_file', array( $instant_checkout, 'cartflows_instant_checkout_page_template_file' ), 10 );
			remove_filter( 'cartflows_checkout_meta_wcf-checkout-layout', array( $instant_checkout, 'stop_other_checkout_layout_implementations' ), 10 );
		}

		// Re-apply field filter neutralization in case anything re-registered them
		$this->late_hooks();
	}

	/**
	 * Finish neutralization after CartFlows hooks and restore identity fields.
	 *
	 * Shared: Store Checkout + sales funnel checkouts.
	 */
	public function after_cartflows_checkout_ui() {
		// Bail if not on a CartFlows checkout context
		if ( ! $this->is_cartflows_checkout_context() ) { return; }

		// Remove CartFlows WooCommerce template overrides so Fluid Checkout templates are used
		if ( class_exists( 'Cartflows_Frontend' ) ) {
			$frontend = Cartflows_Frontend::get_instance();
			remove_filter( 'woocommerce_locate_template', array( $frontend, 'override_woo_template' ), 20 );

			// Re-apply theme-asset restoration in case anything re-registered the strippers
			$this->maybe_restore_theme_assets();
		}

		// Restore CartFlows flow / checkout identity fields inside the Fluid Checkout form
		// (needed for thank-you redirect, order meta, and AJAX endpoints)
		if ( class_exists( 'Cartflows_Checkout_Markup' ) ) {
			add_action( 'fc_checkout_before', array( Cartflows_Checkout_Markup::get_instance(), 'checkout_shortcode_post_id' ), 5 );
		}
	}



	/**
	 * Dequeue CartFlows checkout assets that conflict with Fluid Checkout.
	 *
	 * Shared: Store Checkout + sales funnel checkouts.
	 */
	public function maybe_dequeue_cartflows_checkout_assets() {
		// Bail if not on a CartFlows checkout context
		if ( ! $this->is_cartflows_checkout_context() ) { return; }

		// Dequeue CartFlows checkout template assets
		wp_dequeue_style( 'wcf-checkout-template' );
		wp_dequeue_script( 'wcf-checkout-template' );

		// Dequeue CartFlows global styles that replace / override theme form-field styles
		// (`cartflows-normalize` resets inputs/selects; `frontend` is CartFlows UI chrome)
		wp_dequeue_style( 'wcf-normalize-frontend-global' );
		wp_dequeue_style( 'wcf-frontend-global' );

		// Dequeue Google Places scripts loaded for CartFlows checkout (Fluid Checkout has its own address tools)
		wp_dequeue_script( 'wcf-google-places-api' );
		wp_dequeue_script( 'wcf-google-places' );

		// Dequeue theme-compat CSS that targets CartFlows checkout markup
		wp_dequeue_style( 'wcf-checkout-template-divi' );
		wp_dequeue_style( 'wcf-checkout-template-flatsome' );
		wp_dequeue_style( 'wcf-checkout-template-the-seven' );
		wp_dequeue_style( 'wcf-checkout-astra-compatibility' );
		wp_dequeue_style( 'wcf-checkout-bricks-compatibility' );
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

		// Bail if Fluid Checkout PRO order-received is not available / enabled
		if ( ! class_exists( 'FluidCheckout_PRO_OrderReceivedPage' ) ) { return; }
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_pro_enable_order_received' ) ) { return; }

		// Bail if CartFlows frontend is not available
		if ( ! class_exists( 'Cartflows_Frontend' ) ) { return; }

		// Get CartFlows frontend instance
		$frontend = Cartflows_Frontend::get_instance();

		// Prevent CartFlows from forcing its WooCommerce template copies (thankyou, order-details, etc.)
		remove_filter( 'woocommerce_locate_template', array( $frontend, 'override_woo_template' ), 20 );

		// Re-apply theme-asset restoration in case anything re-registered the strippers
		$this->maybe_restore_theme_assets();
	}

	/**
	 * Dequeue CartFlows assets that conflict with Fluid Checkout PRO on thank-you steps.
	 *
	 * Sales funnels (non-Instant): `cartflows-normalize` resets tables/box-sizing and breaks
	 * FC PRO order-summary zebra rows and column widths.
	 */
	public function maybe_dequeue_cartflows_thankyou_assets() {
		// Bail if not on a non-Instant CartFlows thank-you step
		if ( ! $this->is_non_instant_cartflows_thankyou_context() ) { return; }

		// Bail if Fluid Checkout PRO order-received is not available / enabled
		if ( ! class_exists( 'FluidCheckout_PRO_OrderReceivedPage' ) ) { return; }
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_pro_enable_order_received' ) ) { return; }

		// Dequeue CartFlows global styles that fight FC PRO order-received layout
		wp_dequeue_style( 'wcf-normalize-frontend-global' );
		wp_dequeue_style( 'wcf-frontend-global' );
	}



	/**
	 * Keep theme styles and scripts so Fluid Checkout styling can rely on the theme when needed.
	 *
	 * Shared: Store Checkout + sales funnel checkouts.
	 * Funnels: also on non-Instant thank-you steps for FC PRO order-received.
	 *
	 * @param   bool  $remove  Whether CartFlows should remove theme assets.
	 */
	public function maybe_keep_theme_assets( $remove ) {
		// Keep theme assets on CartFlows checkout (Fluid Checkout form UI)
		if ( $this->is_cartflows_checkout_context() ) {
			return false;
		}

		// Keep theme assets on non-Instant CartFlows thank-you steps (FC PRO order-received)
		if ( $this->is_non_instant_cartflows_thankyou_context() ) {
			return false;
		}

		return $remove;
	}



	/**
	 * Use the WooCommerce order-received URL for Store / Global Checkout orders.
	 *
	 * Store / Global Checkout only. Sales funnels keep the CartFlows thank-you URL
	 * applied by `Cartflows_Frontend::redirect_to_thankyou_page` (priority 10).
	 *
	 * @param   string    $order_receive_url  Order received URL.
	 * @param   WC_Order  $order              Order object.
	 */
	public function maybe_use_woocommerce_thankyou_for_store_checkout( $order_receive_url, $order ) {
		// Bail if not a Store / Global Checkout order
		if ( ! $this->is_store_checkout_order( $order ) ) { return $order_receive_url; }

		// Build the native WooCommerce order-received URL (FC PRO can style this page)
		$woocommerce_order_received_url = wc_get_endpoint_url( 'order-received', $order->get_id(), wc_get_checkout_url() );

		return add_query_arg( 'key', $order->get_order_key(), $woocommerce_order_received_url );
	}



	/**
	 * Disable Fluid Checkout PRO order-received on CartFlows Instant Thank You pages.
	 *
	 * Sales funnels with Instant Layout: CartFlows Instant Thank You owns the UI —
	 * FC PRO layout CSS conflicts with it.
	 *
	 * Sales funnels without Instant Layout: CartFlows embeds WooCommerce
	 * `checkout/thankyou.php`, so FC PRO order-received should stay enabled.
	 *
	 * @param   bool  $enabled  Whether FC PRO order-received is enabled.
	 */
	public function maybe_disable_fc_pro_order_received_on_cartflows_thankyou( $enabled ) {
		// Bail if not on a CartFlows thank-you step
		if ( ! $this->is_cartflows_thankyou_context() ) { return $enabled; }

		// Bail if Instant Layout is not enabled — allow FC PRO to optimize thankyou.php
		if ( ! $this->is_instant_layout_flow() ) { return $enabled; }

		return false;
	}

}

FluidCheckout_Cartflows::instance();
