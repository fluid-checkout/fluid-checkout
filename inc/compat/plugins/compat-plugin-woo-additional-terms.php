<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Woo Additional Terms (by MyPreview).
 */
class FluidCheckout_WooAdditionalTerms extends FluidCheckout {

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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-compat-woo-additional-terms-checkbox-states', self::$directory_url . 'js/compat/plugins/woo-additional-terms/checkbox-states' . self::$asset_version . '.js', array(), NULL );
		wp_add_inline_script( 'fc-compat-woo-additional-terms-checkbox-states', 'window.addEventListener("load",function(){WooAdditionalTermsCheckboxStates.init();})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-woo-additional-terms-checkbox-states' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
	}

}

FluidCheckout_WooAdditionalTerms::instance();
