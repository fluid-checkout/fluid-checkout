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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets_phone_gdpr' ), 10 );

		// Phone GDPR
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'output_wcar_gdpr_phone_message_placeholder' ), 200 );
	}



	/**
	 * Check whether WCAR Pro phone GDPR is enabled.
	 *
	 * @return bool
	 */
	public function is_wcar_phone_gdpr_enabled() {
		// Bail if WCAR PRO license is not active
		if ( ! function_exists( 'wcar_pro_is_active_license' ) || ! wcar_pro_is_active_license() ) { return false; }

		// Bail if WCAR plugin main class is unavailable
		if ( ! function_exists( 'wcf_ca' ) ) { return false; }

		// Bail if phone GDPR is disabled in plugin settings
		if ( 'on' !== wcf_ca()->utils->wcar_get_option( 'wcf_ca_phone_gdpr_status' ) ) { return false; }

		return true;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		wp_register_script( 'fc-compat-checkout-wcar', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-cart-abandonment-recovery-pro/checkout-wcar' ), array( 'jquery', 'cartflows-cart-abandonment-tracking' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-checkout-wcar', 'window.addEventListener("load",function(){FCWCARCheckout.init();});' );
	}

	/**
	 * Enqueue assets for phone GDPR.
	 */
	public function enqueue_assets_phone_gdpr() {
		wp_enqueue_script( 'fc-compat-checkout-wcar' );
	}

	/**
	 * Maybe enqueue assets for phone GDPR.
	 */
	public function maybe_enqueue_assets_phone_gdpr() {
		// Bail if not on checkout page or fragment.
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if WCAR Pro phone GDPR is not enabled
		if ( ! $this->is_wcar_phone_gdpr_enabled() ) { return; }

		$this->enqueue_assets_phone_gdpr();
	}



	/**
	 * Output empty placeholder for WCAR PRO phone GDPR message placement.
	 */
	public function output_wcar_gdpr_phone_message_placeholder() {
		// Bail if WCAR Pro phone GDPR is not enabled
		if ( ! $this->is_wcar_phone_gdpr_enabled() ) { return; }

		// Output placeholder
		echo '<div id="fc-wcar-gdpr-phone-message-placeholder" class="fc-wcar-gdpr-phone-message-placeholder"></div>';
	}
}

FluidCheckout_WooCartAbandonmentRecoveryPro::instance();
