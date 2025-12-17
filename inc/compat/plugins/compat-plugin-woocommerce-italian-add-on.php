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

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields' ), 10 );
	}

	/**
	 * Register assets.
	 */
	public function register_assets() {
		wp_register_script( 'fc-woocommerce-italian-add-on', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-italian-add-on/checkout' ), array( 'jquery', 'wc_italian_add_on' ), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-woocommerce-italian-add-on', 'window.addEventListener("load",function(){WooCommerceItalianAddOnCheckout.init();});' );
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
	 * Prevent hiding some optional fields behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields( $skip_list ) {
		$skip_list[] = 'billing_cf';
		$skip_list[] = 'billing_cf2';
		$skip_list[] = 'billing_PEC';
		return $skip_list;
	}

}

FluidCheckout_WooCommerceItalianAddOn::instance();

