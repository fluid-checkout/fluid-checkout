<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Social Login (by SkyVerge).
 */
class FluidCheckout_WooCommerceSocialLogin extends FluidCheckout {

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
		// Add social login buttons below the login CTA at the checkout contact step.
		add_action( 'fc_checkout_below_contact_login_cta', array( $this, 'add_checkout_social_login_buttons' ), 20 );
		
		// ! this is probably not needed its already present is just hidden with a display: none; style, probably shows it in the plugin options?
		// add_action( 'woocommerce_login_form_end', array( $this, 'add_checkout_social_login_buttons' ), 20 );
	}



	/**
	 * Adds the WooCommerce Social Login buttons to the checkout contact step.
	 */
	public function add_checkout_social_login_buttons() {
		// Bail if WooCommerce Social Login is not available.
		if ( ! function_exists( 'wc_social_login' ) ) { return; }

		$plugin = wc_social_login();
		$frontend = $plugin ? $plugin->get_frontend_instance() : null;

		// Bail if frontend instance not available or checkout display disabled.
		if ( ! $frontend || ! $frontend->is_displayed_on( 'checkout' ) ) { return; }

		ob_start();
		woocommerce_social_login_buttons( wc_get_checkout_url() );
		$buttons_html = trim( ob_get_clean() );

		if ( '' === $buttons_html ) { return; }

		echo '<div class="fc-social-login fc-social-login--woocommerce-social-login">';
		echo $buttons_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

}

FluidCheckout_WooCommerceSocialLogin::instance();

