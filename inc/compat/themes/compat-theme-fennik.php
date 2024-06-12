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

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
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

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
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

}

FluidCheckout_ThemeCompat_Fennik::instance();
