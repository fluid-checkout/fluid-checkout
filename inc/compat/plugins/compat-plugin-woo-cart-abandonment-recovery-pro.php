<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Cart Abandonment Recovery Pro for WooCommerce (by CartFlows Inc).
 */
class FluidCheckout_WooCartAbandonmentRecoveryPro extends FluidCheckout {
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
		// Register assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Phone GDPR
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'output_wcar_gdpr_phone_message_placeholder' ), 200 );
	}

	/**
	 * Output empty wrapper for WCAR Pro phone GDPR checkbox placement (see `gdpr-phone-checkbox.js`).
	 *
	 * @return void
	 */
	public function output_wcar_gdpr_phone_message_placeholder() {
		echo '<div id="fc-wcar-gdpr-phone-message-placeholder" class="fc-wcar-gdpr-phone-message-placeholder"></div>';
	}

	/**
	 * Register assets.
	 */
	public function register_assets() {
		wp_register_script( 'fc-compat-checkout-wcar', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-cart-abandonment-recovery-pro/checkout-wcar' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-checkout-wcar', 'window.addEventListener("load",function(){FCWCARCheckout.init();});' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'fc-compat-checkout-wcar' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not on checkout page or fragment.
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}
}

FluidCheckout_WooCartAbandonmentRecoveryPro::instance();
