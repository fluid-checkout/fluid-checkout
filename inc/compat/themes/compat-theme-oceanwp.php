<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: OceanWP (by OceanWP).
 */
class FluidCheckout_ThemeCompat_OceanWP extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Multistep checkout
		$this->maybe_undo_multistep_checkout_hooks();
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_multistep_assets' ), 300 );
	}

	/**
	 * Maybe undo multistep checkout hooks from the theme.
	 */
	public function maybe_undo_multistep_checkout_hooks() {
		// Bail if multistep checkout option from theme is not enabled.
		if ( true != get_theme_mod( 'ocean_woo_multi_step_checkout', false ) ) { return; }
	
		// Checkout validation.
		remove_action( 'wp_ajax_oceanwp_validate_checkout', array( OceanWP_WooCommerce_Config::instance(), 'validate_checkout_callback' ) );
		remove_action( 'wp_ajax_nopriv_oceanwp_validate_checkout', array( OceanWP_WooCommerce_Config::instance(), 'validate_checkout_callback' ) );

		// Add checkout timeline template.
		remove_action( 'woocommerce_before_checkout_form', array( OceanWP_WooCommerce_Config::instance(), 'checkout_timeline' ), 10 );

		// Change checkout template.
		remove_filter( 'woocommerce_locate_template', array( OceanWP_WooCommerce_Config::instance(), 'multistep_checkout' ), 10, 3 );

		// Coupon form.
		// Maybe re-add the coupon form if integrated coupon code from the plugin is not enabled.
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_enable_checkout_coupon_codes' ) ) {
			add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}

		// Checkout hack.
		// Do not re-add all actions that were removed by the theme. But remove actions added by the theme.
		add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		remove_action( 'ocean_woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		remove_action( 'ocean_woocommerce_checkout_payment', 'woocommerce_checkout_payment', 20 );
		remove_action( 'ocean_checkout_login_form', array( OceanWP_WooCommerce_Config::instance(), 'checkout_login_form' ), 10 );
		remove_action( 'ocean_woocommerce_checkout_coupon', 'woocommerce_checkout_coupon_form', 10 );

		// Prevent empty shipping tab.
		remove_filter( 'woocommerce_enable_order_notes_field', '__return_true' );

		// Support to WooCommerce secure submit gateway.
		if ( class_exists( 'WC_Gateway_SecureSubmit' ) ) {
			$secure_submit_options = get_option( 'woocommerce_securesubmit_settings' );
			if ( ! empty( $secure_submit_options['use_iframes'] ) && 'yes' == $secure_submit_options['use_iframes'] ) {
				remove_filter( 'option_woocommerce_securesubmit_settings', array( OceanWP_WooCommerce_Config::instance(), 'woocommerce_securesubmit_support' ), 10, 2 );
			}
		}
	}

	/**
	 * Maybe dequeue multistep checkout assets.
	 */
	public function maybe_dequeue_multistep_assets() {
		// Bail if multistep checkout option from theme is not enabled.
		if ( true != get_theme_mod( 'ocean_woo_multi_step_checkout', false ) ) { return; }

		// Dequeue multistep checkout assets.
		wp_dequeue_style( 'oceanwp-woo-multistep-checkout' );
		wp_dequeue_script( 'oceanwp-woo-multistep-checkout' );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Add `fc-container` when using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) {
			return $class . ' fc-container';
		}
		// Add `container` when using theme header and footer
		else {
			return $class . ' container';
		}
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '3px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_OceanWP::instance();
