<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: YITH WooCommerce Checkout Manager (by YITH).
 */
class FluidCheckout_YithWooCommerceCheckoutManager extends FluidCheckout {

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
		// Checkout field args
		add_filter( 'fc_checkout_address_i18n_override_locale_required_attribute', '__return_true', 10 );

		// Skip optional fields
		add_filter( 'fc_hide_optional_fields_skip_field', array( $this, 'maybe_skip_hiding_condition_required_fields' ), 10, 4 );
	}



	/**
	 * Maybe skip hiding fields that are "conditionally required".
	 * "Conditionally required" fields set as required only if a certain condition is fulfilled, otherwise they are optional.
	 * 
	 * @param  bool   $skip  Whether to skip hiding the field or not.
	 * @param  string $key   The field key.
	 * @param  array  $args  The field arguments.
	 * @param  mixed  $value The field value.
	 */
	public function maybe_skip_hiding_condition_required_fields( $skip, $key, $args, $value ) {
		// Check if the field is conditionally required
		if ( isset( $args[ 'condition_required' ] ) && $args[ 'condition_required' ] && '0|' !== $args[ 'condition_required' ] ) {
			$skip = true;
		}

		return $skip;
	}

}

FluidCheckout_YithWooCommerceCheckoutManager::instance();