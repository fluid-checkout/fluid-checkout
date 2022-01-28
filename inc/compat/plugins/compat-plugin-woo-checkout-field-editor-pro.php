<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Checkout Field Editor for WooCommerce (free version) (by ThemeHigh).
 */
class FluidCheckout_WooCheckoutFieldEditorPro extends FluidCheckout {

	private static $thwcfd_public = null;

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
		
		// Account edit address
		if ( 'yes' === apply_filters( 'fc_integration_woo_checkout_field_editor_pro_enable_edit_address_changes', 'yes' ) ) {
			// Get the plugin public class object
			self::$thwcfd_public = FluidCheckout::instance()->get_object_by_class_name_from_hooks( 'THWCFD_Public_Checkout' );

			if ( null !== self::$thwcfd_public ) {
				/**
				 * @see THWCFD_Public_Checkout::define_public_hooks()
				 */
				$hp_billing_fields  = apply_filters( 'thwcfd_billing_fields_priority', 1000 );
				$hp_shipping_fields = apply_filters( 'thwcfd_shipping_fields_priority', 1000 );

				add_filter( 'woocommerce_billing_fields', array( $this, 'apply_billing_fields_changes'), $hp_billing_fields, 2 );
				add_filter( 'woocommerce_shipping_fields', array( $this, 'apply_shipping_fields_changes'), $hp_shipping_fields, 2 );
			}
		}

	}



	/**
	 * Apply changes to the billing fields on edit address screen.
	 *
	 * @param   array  $fields    Checkout fields arguments.
	 * @param   string  $country  Country code.
	 */
	public function apply_billing_fields_changes( $fields, $country ) {
		// Bail if not on account edit address endpoint
		if( ! is_wc_endpoint_url('edit-address') ) { return $fields; }

		return self::$thwcfd_public->prepare_address_fields( get_option( 'wc_fields_billing' ), $country, $fields, 'billing' );
	}



	/**
	 * Apply changes to the shipping fields on edit address screen.
	 *
	 * @param   array  $fields    Checkout fields arguments.
	 * @param   string  $country  Country code.
	 */
	public function apply_shipping_fields_changes( $fields, $country ) {
		// Bail if not on account edit address endpoint
		if( ! is_wc_endpoint_url('edit-address') ) { return $fields; }

		return self::$thwcfd_public->prepare_address_fields( get_option( 'wc_fields_shipping' ), $country, $fields, 'shipping' );
	}

}

FluidCheckout_WooCheckoutFieldEditorPro::instance();
