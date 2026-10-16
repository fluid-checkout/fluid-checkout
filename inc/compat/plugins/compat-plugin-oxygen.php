<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Oxygen (by Soflyy).
 */
class FluidCheckout_Oxygen extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->maybe_load_breakdance_compat_for_oxygen_six();
		$this->hooks();
	}



	/**
	 * Whether the active Oxygen plugin is version 6+ (Breakdance engine) with bundled WooCommerce integration.
	 *
	 * @return  boolean
	 */
	public static function is_oxygen_six_with_breakdance_woocommerce() {
		// Bail if not running in Oxygen 6+ mode
		if ( ! defined( 'BREAKDANCE_MODE' ) || 'oxygen' !== BREAKDANCE_MODE ) { return false; }

		// Bail if the standalone Breakdance plugin is active, its compat file is loaded separately
		if ( self::is_standalone_breakdance_plugin_active() ) { return false; }

		// Bail if the bundled Breakdance WooCommerce subplugin is not present
		if ( ! file_exists( WP_PLUGIN_DIR . '/oxygen/subplugins/breakdance-woocommerce/plugin.php' ) ) { return false; }

		return true;
	}

	/**
	 * Whether the standalone Breakdance builder plugin is active.
	 *
	 * @return  boolean
	 */
	/**
	 * Whether Breakdance checkout compat styles should load on the current request.
	 *
	 * @return  boolean
	 */
	public static function is_oxygen_six_breakdance_compat_styles_page() {
		// Bail if not applicable
		if ( ! self::is_oxygen_six_with_breakdance_woocommerce() ) { return false; }

		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page() ) { return true; }

		if ( is_account_page() && ( is_wc_endpoint_url( 'edit-address' ) || is_wc_endpoint_url( 'add-payment-method' ) ) ) { return true; }

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) { return true; }

		if ( class_exists( 'FluidCheckout_PRO_OrderPayPage' ) && FluidCheckout_PRO_OrderPayPage::instance()->is_order_pay_page_or_fragment() ) { return true; }

		if ( function_exists( 'is_view_order_page' ) && is_view_order_page() ) { return true; }

		return false;
	}

	/**
	 * Whether the standalone Breakdance builder plugin is active.
	 *
	 * @return  boolean
	 */
	public static function is_standalone_breakdance_plugin_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'breakdance/plugin.php' );
	}

	/**
	 * Load Breakdance compatibility when Oxygen 6 bundles the Breakdance WooCommerce integration.
	 */
	public function maybe_load_breakdance_compat_for_oxygen_six() {
		// Bail if not applicable
		if ( ! self::is_oxygen_six_with_breakdance_woocommerce() ) { return; }

		// Bail if already loaded by the Breakdance plugin compat file
		if ( class_exists( 'FluidCheckout_Breakdance' ) ) { return; }

		require_once self::$directory_path . 'inc/compat/plugins/compat-plugin-breakdance.php';
	}

	/**
	 * Whether the current request is from the Oxygen or Breakdance builder.
	 *
	 * @return  boolean
	 */
	public function is_oxygen_or_breakdance_builder_request() {
		// Legacy Oxygen builder query arguments
		if ( isset( $_GET[ 'ct_builder' ] ) || isset( $_GET[ 'action' ] ) ) { return true; }

		// Oxygen 6 / Breakdance builder requests
		if ( function_exists( 'Breakdance\isRequestFromBuilderIframe' ) && \Breakdance\isRequestFromBuilderIframe() ) { return true; }
		if ( function_exists( 'Breakdance\isRequestFromBuilderSsr' ) && \Breakdance\isRequestFromBuilderSsr() ) { return true; }

		return false;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Breakdance compat styles for Oxygen 6, the Breakdance plugin slug is not active
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_breakdance_compat_styles_for_oxygen_six' ), 10 );

		// Themeless Oxygen pages (shortcode only) need FC content wrappers and container class
		add_action( 'wp', array( $this, 'maybe_setup_themeless_page_wrappers_for_oxygen_six' ), 5 );
		add_filter( 'fc_add_container_class', array( $this, 'keep_fc_container_class_for_oxygen_six' ), 20 );
		add_filter( 'fc_wrapper_classes', array( $this, 'maybe_append_fc_container_class_to_wrapper' ), 20 );
	}

	/**
	 * Append the FC container class to the checkout wrapper on order pay pages.
	 *
	 * @param  string  $classes  Wrapper element classes.
	 */
	public function maybe_append_fc_container_class_to_wrapper( $classes ) {
		// Bail if not applicable
		if ( ! self::is_oxygen_six_with_breakdance_woocommerce() ) { return $classes; }

		// Bail if not on order pay page
		if ( ! class_exists( 'FluidCheckout_PRO_OrderPayPage' ) || ! FluidCheckout_PRO_OrderPayPage::instance()->is_order_pay_page_or_fragment() ) { return $classes; }

		return self::append_fc_container_class( $classes );
	}

	/**
	 * Append the FC container class string when missing.
	 *
	 * @param  string  $classes  Element classes.
	 */
	public static function append_fc_container_class( $classes ) {
		// Bail if class is already present
		if ( false !== strpos( ' ' . $classes . ' ', ' fc-container ' ) ) { return $classes; }

		return trim( $classes . ' fc-container' );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Maybe remove checkout page template
		// - when editing the checkout page
		// - when using theme header and footer
		if ( $this->is_oxygen_or_breakdance_builder_request() || ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) {
			remove_filter( 'template_include', array( FluidCheckout_CheckoutPageTemplate::instance(), 'checkout_page_template' ), 100 );
		}
	}

	/**
	 * Keep the FC container class on Oxygen 6 themeless pages.
	 *
	 * @param  boolean  $add_container_class  Whether to add the container class.
	 */
	public function keep_fc_container_class_for_oxygen_six( $add_container_class ) {
		// Bail if not applicable
		if ( ! self::is_oxygen_six_with_breakdance_woocommerce() ) { return $add_container_class; }

		return true;
	}

	/**
	 * Add FC content wrappers around WooCommerce shortcodes on Oxygen 6 themeless pages.
	 */
	public function maybe_setup_themeless_page_wrappers_for_oxygen_six() {
		// Bail if not applicable
		if ( ! self::is_oxygen_six_with_breakdance_woocommerce() ) { return; }

		// Checkout shortcode wrapper when using the theme template
		if (
			FluidCheckout_Steps::instance()->is_checkout_page_or_fragment()
			&& ! is_order_received_page()
			&& ! is_checkout_pay_page()
			&& ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout()
		) {
			$checkout_shortcode_tag = apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' );
			remove_shortcode( $checkout_shortcode_tag );
			add_shortcode( $checkout_shortcode_tag, array( FluidCheckout_CheckoutPageTemplate::instance(), 'output_checkout_shortcode_wrapper' ) );
		}
	}

	/**
	 * Enqueue Breakdance plugin compat styles when only Oxygen 6 is active.
	 */
	public function maybe_enqueue_breakdance_compat_styles_for_oxygen_six() {
		// Bail if not applicable
		if ( ! self::is_oxygen_six_with_breakdance_woocommerce() ) { return; }

		// Bail if not on FC frontend pages that use Breakdance checkout styles
		if ( is_admin() || ! self::is_oxygen_six_breakdance_compat_styles_page() ) { return; }

		// Get file path
		$compat_file_path = FluidCheckout_Enqueue::instance()->get_style_url( 'css/compat/plugins/compat-breakdance', false );

		// Maybe load compatibility file
		if ( file_exists( self::$directory_path . $compat_file_path ) ) {
			wp_enqueue_style( 'fc-plugin-compat-breakdance', self::$directory_url . $compat_file_path, array(), null );
		}
	}



	/**
	 * Add CSS variables.
	 *
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if Breakdance compat is handling design tokens for Oxygen 6
		if ( class_exists( 'FluidCheckout_Breakdance' ) && self::is_oxygen_six_with_breakdance_woocommerce() ) { return $css_variables; }

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '43px',
				'--fluidcheckout--field--border-radius' => '4px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_Oxygen::instance();
