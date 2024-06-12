<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Fennik (by LA Studio).
 */
class FluidCheckout_ThemeCompat_Fennik extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}



	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Quantity fields
		remove_action( 'woocommerce_after_quantity_input_field', 'fennik_wc_add_qty_control_plus', 10 );
		remove_action( 'woocommerce_before_quantity_input_field', 'fennik_wc_add_qty_control_minus', 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-fennik-image-lazy-load', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/themes/fennik/lazy-load' ), array( 'jquery', 'fennik-theme' ), NULL, true );
		wp_add_inline_script( 'fc-compat-fennik-image-lazy-load', 'window.addEventListener("load",function(){FennikLazyLoad.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Bail if lazy load is not enabled in the theme
		if ( ! fennik_get_option( 'activate_lazyload' ) ) { return; }

		// Scripts
		// Lazy load
		wp_enqueue_script( 'fc-compat-fennik-image-lazy-load' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if theme function isn't available
		if ( ! function_exists( 'fennik_get_option' ) ) { return; }
	
		$this->enqueue_assets();
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' container';
	}

}

FluidCheckout_ThemeCompat_Fennik::instance();
