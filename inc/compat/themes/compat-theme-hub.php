<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Hub (by Liquid Themes).
 */
class FluidCheckout_ThemeCompat_Hub extends FluidCheckout {

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Theme's "Payment" section in order summary
		remove_action( 'woocommerce_checkout_order_review', 'liquid_heading_payment_method', 15 );

		// Dequeue scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_scripts_jquery_ui_checkout_page' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_scripts_jquery_ui_edit_address_page' ), 100 );

		// Lazy load hooks
		add_action( 'wp', array( $this, 'disable_image_lazy_load' ), 10 );
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
				'--fluidcheckout--field--height' => '45px',
				'--fluidcheckout--field--padding-left' => '25px',
				'--fluidcheckout--field--background-color--accent' => 'var(--color-primary)',
				'--fluidcheckout--field--border-color' => '#e1e1e1',
				'--fluidcheckout--field--font-size' => '18px',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing' => '20px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '40px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Dequeue theme scripts that break the layout on cart page.
	 */
	public function dequeue_scripts_jquery_ui() {
		// jQuery UI from the theme
		wp_deregister_script( 'jquery-ui' );
		wp_dequeue_script( 'jquery-ui' );
	}

	/**
	 * Dequeue theme scripts that break the layout on checkout page.
	 */
	public function maybe_dequeue_scripts_jquery_ui_checkout_page() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Dequeue jQuery UI scripts
		$this->dequeue_scripts_jquery_ui();
	}

	/**
	 * Dequeue theme scripts that break the layout on edit address page.
	 */
	public function maybe_dequeue_scripts_jquery_ui_edit_address_page() {
		// Bail if not on account edit address page
		if ( ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return; }

		// Dequeue jQuery UI scripts
		$this->dequeue_scripts_jquery_ui();
	}



	/**
	 * Disable Hub theme's lazy loading for images that doesn't work without the jQuery UI script.
	 */
	public function disable_image_lazy_load() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $options; }

		remove_filter( 'wp_get_attachment_image_attributes', 'liquid_filter_gallery_img_atts', 10 );
		remove_filter( 'wp_lazy_loading_enabled', '__return_false' );
	}

}

FluidCheckout_ThemeCompat_Hub::instance();
