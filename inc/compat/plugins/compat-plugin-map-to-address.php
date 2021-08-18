<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: SG Map to Address (by Sevengits).
 */
class FluidCheckout_MapToAddress extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		if ( class_exists( 'Sgitsdlmp_Public' ) ) {
			// Remove hooks
			$this->remove_action_for_class( 'woocommerce_after_checkout_billing_form', array( 'Sgitsdlmp_Public', 'sgitsdlmp_woocommerce_after_checkout_billing_form' ), 10 );
			$this->remove_action_for_class( 'woocommerce_after_checkout_shipping_form', array( 'Sgitsdlmp_Public', 'sgitsdlmp_woocommerce_after_checkout_shipping_form' ), 10 );
			remove_action( 'woocommerce_before_checkout_billing_form', array( FluidCheckout_Steps::instance(), 'output_billing_same_as_shipping_field' ), 100 );

			// Re-add hooks in different position
			$plugin_public = new Sgitsdlmp_Public( 'map-to-address', SGITSDLMP_VERSION );
			add_action( 'fc_after_substep_billing_address', array( $plugin_public, 'sgitsdlmp_woocommerce_after_checkout_billing_form' ), 10 );
			add_action( 'fc_after_substep_shipping_address', array( $plugin_public, 'sgitsdlmp_woocommerce_after_checkout_shipping_form' ), 10 );

			// Replace billing same as shipping field
			add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'output_billing_same_as_shipping_field_always_false' ), 100 );

			// Remove substep text
			add_filter( 'fc_substep_billing_address_text', '__return_empty_string' );
			add_filter( 'fc_substep_shipping_address_text', '__return_empty_string' );
		}
	}



	/**
	 * Output field for billing address same as shipping.
	 */
	public function output_billing_same_as_shipping_field_always_false() {
		?>
		<input type="hidden" name="billing_same_as_shipping" id="billing_same_as_shipping" value="0">
		<?php
	}

}

FluidCheckout_MapToAddress::instance();
