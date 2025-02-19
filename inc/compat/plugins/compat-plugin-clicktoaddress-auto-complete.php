<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Fetchify (by ClearCourse Business Services Limited t/a Fetchify).
 */
class FluidCheckout_ClickToAddressAutoComplete extends FluidCheckout {

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

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		wp_register_script( 'fc-checkout-clicktoaddress-auto-complete', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/clicktoaddress-auto-complete/checkout-clicktoaddress-auto-complete' ), array( 'jquery' ), NULL, true );
		wp_add_inline_script( 'fc-checkout-clicktoaddress-auto-complete', 'window.addEventListener("load",function(){CheckoutClickToAddressAutoComplete.init(fcSettings.checkoutClickToAddressAutoComplete);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-clicktoaddress-auto-complete' );
	}

}

FluidCheckout_ClickToAddressAutoComplete::instance();
