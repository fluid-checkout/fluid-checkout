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
	}

	/**
	 * Maybe adds the action to output the Nextend Social Login buttons at the checkout contact step.
	 */
	public function maybe_add_action_to_output_checkout_social_login_buttons() {
		// Bail if Nextend Social Login add on pro 
		if ( ! class_exists( 'NextendSocialLoginPRO' ) ) { return; }

		// Bail if option "No Connect button in billing form" is enabled.
		if ( NextendSocialLogin::$settings->get( 'woocommerce_billing' ) === '' ) { return; }

		add_action( 'fc_checkout_social_login', array( $this, 'output_checkout_social_login_buttons' ), 20 );
	}

	/**
	 * Outputs the Nextend Social Login buttons at the checkout contact step.
	 */
	public function output_checkout_social_login_buttons() {
		// Output the Nextend Social Login buttons with the alignment
		$align = NextendSocialLogin::$settings->get( 'woocoommerce_form_button_align' );
		// ! we should get all settings from the plugin and pass them to the renderButtonsWithContainer function
		$buttons = NextendSocialLogin::renderButtonsWithContainer( 'default', false, false, false, $align, 'login' );

		// Output the Nextend Social Login buttons.
		echo $buttons;
	}



}

FluidCheckout_NextendSocialLogin::instance();
