<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Helcim Commerce for WooCommerce (by Helcim).
 */
class FluidCheckout_HelcimCommerceForWooCommerce extends FluidCheckout {

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
		wp_register_script( 'fc-compat-helcim-commerce-for-woocommerce-checkout', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/helcim-commerce-for-woocommerce/helcim-checkout' ), array( 'jquery', 'wc-checkout' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-helcim-commerce-for-woocommerce-checkout', 'window.addEventListener("load",function(){HelcimCheckout.init();});' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-helcim-commerce-for-woocommerce-checkout' );
	}

	/**
	 * Enqueue scripts when on the checkout page or fragment.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}

}

FluidCheckout_HelcimCommerceForWooCommerce::instance();
