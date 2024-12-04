<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartBounty Pro - Save and recover abandoned carts for WooCommerce (by Streamline.lv).
 */
class FluidCheckout_WooSaveAbandonedCartsPro extends FluidCheckout {

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
		// Get plugin object instance
		$class_object = $this->get_object_by_class_name_from_hooks( 'CartBounty_Pro_Public' );

		// Bail if class object is not found in hooks
		if ( ! $class_object ) { return; }

		// Change the way input data is recovered from CartBounty to avoid conflicts with a similar feature from Fluid Checkout 
		remove_filter( 'wp', array( $class_object, 'restore_input_data' ), 10 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'maybe_restore_abandoned_cart_values_to_session' ), 10 );
	}



	/**
	 * Maybe restore abandoned cart values to session.
	 */
	public function maybe_restore_abandoned_cart_values_to_session() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Get plugin class objects
		$public_class_object = $this->get_object_by_class_name_from_hooks( 'CartBounty_Pro_Public' );
		$admin_class_object = $this->get_object_by_class_name_from_hooks( 'CartBounty_Pro_Admin' );

		// Bail if class objects are not found in hooks
		if ( ! $public_class_object && ! $admin_class_object ) { return; }

		// Bail if required methods are not found in the class objects
		if ( ! method_exists( $public_class_object, 'get_saved_cart' ) || ! method_exists( $admin_class_object, 'get_cart_location' ) ) { return; }

		// Get saved (abandoned) cart object
		$saved_cart = $public_class_object->get_saved_cart();

		// Bail if no saved cart found
		if ( ! $saved_cart ) { return; }

		// Get billing fields from the saved cart object
		$location_data = $admin_class_object->get_cart_location( $saved_cart->location );

		// Map the session data values to the saved cart values
		$billing_fields = array(
			'billing_first_name' => $saved_cart->name,
			'billing_last_name' => $saved_cart->surname,
			'billing_email' => $saved_cart->email,
			'billing_phone' => $saved_cart->phone,
			'billing_country' => $location_data[ 'country' ],
			'billing_city' => $location_data[ 'city' ],
			'billing_postcode' => $location_data[ 'postcode' ],
		);

		// Get the rest of the checkout fields
		$other_fields = maybe_unserialize( $saved_cart->other_fields );
		if ( ! is_array( $other_fields ) ) {
			$other_fields = array();
		}

		// Combine fields
		$checkout_fields = array_merge( $billing_fields, $other_fields );

		// Loop through the fields, and maybe set the session values
		foreach ( $checkout_fields as $key => $value ) {
			// Remove plugin prefix from the field keys
			$prefix = 'cartbounty_';
			$key = str_replace( $prefix, '', $key );

			// Get current session value
			$current_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( $key );

			// Set the session value if it's empty
			if ( empty( $current_value ) ) {
				FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( $key, $value );
			}
		}
	}

}

FluidCheckout_WooSaveAbandonedCartsPro::instance();
