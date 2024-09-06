<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: VerifyPass (by VerifyPass).
 */
class FluidCheckout_VerifyPass extends FluidCheckout {

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
		// Discount buttons
		$this->remove_action_for_class( 'woocommerce_cart_coupon', array( 'verifypass', 'hook_after_coupon' ), 10 );
		add_action( 'fc_coupon_code_section_before', array( $this, 'output_discount_buttons' ), 10 );
	}



	/**
	 * Output discount buttons from the plugin.
	 */
	public function output_discount_buttons() {
		global $verifypass;

		// Bail if plugin instance or its method is not avaialble
		if ( ! is_object( $verifypass ) || ! method_exists( $verifypass, 'hook_after_coupon' ) ) { return $field; }

		// Output buttons with custom container
		echo '<div class="verifypass-discount-buttons">';
		$verifypass->hook_after_coupon();
		echo '</div>';
	}

}

FluidCheckout_VerifyPass::instance();
