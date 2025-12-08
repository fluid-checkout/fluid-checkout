<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Italian Add-on Plus.
 */
class FluidCheckout_WooCommerceItalianAddOn extends FluidCheckout {

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
		// Register assets for fixing dinamic validation of vat fields
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Skip optional fields - Fixes issue with optional fields being hidden behind a link button.
		add_filter( 'fc_hide_optional_fields_skip_field', array( $this, 'skip_optional_fields' ), 10, 4 );
	}

	/**
	 * Register assets.
	 */
	public function register_assets() {
		wp_register_script(
			'fc-woocommerce-italian-add-on',
			FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-italian-add-on/checkout' ),
			array( 'jquery', 'wc_italian_add_on' ),
			null,
			array( 'in_footer' => true, 'strategy' => 'defer' )
		);
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'fc-woocommerce-italian-add-on' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}

	/**
	 * Prevent Fluid Checkout from hiding these fields.
	 */
	public function skip_optional_fields( $skip, $key, $args, $value ) {
		if ( in_array( $key, array( 'billing_cf', 'billing_cf2', 'billing_PEC' ), true ) ) {
			return true;
		}

		return $skip;
	}

}

FluidCheckout_WooCommerceItalianAddOn::instance();

