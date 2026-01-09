<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Sober (by Uixthemes).
 */
class FluidCheckout_ThemeCompat_Sober extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}


	// TODO: Review the whole file for now its a copy of the Motta file.
	
	// TODO: A lot of the hook are here /themes/sober/inc/class-sober-wc.php
	// TODO: add_filter( 'woocommerce_enqueue_styles', array( $this, 'wc_styles' ) );
	// TODO: add_filter( 'loop_shop_columns', array( $this, 'shop_columns' ), 20 );
	// TODO: add_filter( 'loop_shop_per_page', array( $this, 'products_per_page' ), 20 );
	// TODO: ....

	
	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Set class name from the theme
		$class_name = 'Sober_WooCommerce';

		// Bail if class is not available.
		if ( ! class_exists( $class_name ) ) {
			return;
		}

		// Get class object
		$class_object = call_user_func( array( $class_name, 'instance' ) );

		// Remove hooks
		remove_filter( 'woocommerce_checkout_before_customer_details', array( $class_object, 'billing_title' ) );

		// hooks
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

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
				// // Form field styles
				// '--fluidcheckout--field--height' => '60px',
				// '--fluidcheckout--field--padding-left' => '22px',
				// '--fluidcheckout--field--font-size' => 'var(--mt-input__font-size)',
				// '--fluidcheckout--field--border-color' => '#dadfe3',
				// '--fluidcheckout--field--border-width' => 'var(--mt-input__border-width)',
				// '--fluidcheckout--field--border-radius' => 'var(--mt-border__radius)',
				// '--fluidcheckout--field--background-color--accent' => '#1d2128',

				// // Checkout validation styles
				// '--fluidcheckout--validation-check--horizontal-spacing' => '20px',
				// '--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '45px',
				// '--fluidcheckout--validation-check--horizontal-spacing--password' => '20px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

	/**
	 * Add CSS variables to the edit address page.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables_edit_address( $css_variables ) {
		// Bail if not on account address edit page
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return $css_variables; }

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '44px', 
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Sober::instance();
