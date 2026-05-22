<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Nextend Social Login (by Nextend).
 */
class FluidCheckout_NextendSocialLogin extends FluidCheckout {

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
		// Maybe add the action to output the Nextend Social Login buttons at the checkout contact step.
		$this->maybe_add_action_to_output_checkout_social_login_buttons();

		// Remove billing form hooks from Nextend Social Login.
		$this->remove_billing_form_hooks_from_nextend_social_login();
	}



	/**
	 * Maybe adds the action to output the Nextend Social Login buttons at the checkout contact step.
	 */
	public function maybe_add_action_to_output_checkout_social_login_buttons() {
		// Bail if Nextend Social Login Pro isn't active as the hooks originate from the add-on.
		if ( ! class_exists( 'NextendSocialLoginPRO' ) ) { return; }

		// Bail if option "No Connect button in billing form" is enabled.
		if ( NextendSocialLogin::$settings->get( 'woocommerce_billing' ) === '' ) { return; }

		add_action( 'fc_checkout_social_login', array( $this, 'output_checkout_social_login_buttons' ), 20 );
	}



	/**
	 * Outputs the Nextend Social Login buttons at the checkout contact step.
	 */
	public function output_checkout_social_login_buttons() {
		// Output the Nextend Social Login buttons using the plugin's settings.
		$style     = NextendSocialLogin::$settings->get( 'woocoommerce_form_button_style' );
		$align     = NextendSocialLogin::$settings->get( 'woocoommerce_form_button_align' );
		$labelType = 'login';
		$buttons   = NextendSocialLogin::renderButtonsWithContainer( $style, false, false, false, $align, $labelType );

		// Output the Nextend Social Login buttons.
		echo $buttons;
	}



	/**
	 * Removes the billing form hooks from Nextend Social Login.
	 */
	public function remove_billing_form_hooks_from_nextend_social_login() {
		// Bail if Nextend Social Login Pro isn't active as the hooks originate from the add-on.
		if ( ! class_exists( 'NextendSocialLoginPRO' ) ) {
			return;
		}

		// List of hooks to remove
		$hooks = array(
			'woocommerce_before_checkout_billing_form',
			'woocommerce_after_checkout_billing_form',
			'woocommerce_before_checkout_registration_form',
			'woocommerce_after_checkout_registration_form',
			'woocommerce_before_checkout_shipping_form',
			'woocommerce_after_checkout_shipping_form',
		);

		// List of callbacks to remove
		$callbacks = array(
			'NextendSocialLoginPRO::woocommerce_before_checkout_billing_form',
			'NextendSocialLoginPRO::woocommerce_after_checkout_billing_form',
		);

		// Remove the hooks and callbacks
		foreach ( $hooks as $hook ) {
			foreach ( $callbacks as $callback ) {
				remove_action( $hook, $callback );
			}
		}
	}

}

FluidCheckout_NextendSocialLogin::instance();
