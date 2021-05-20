<?php

/**
 * Customizations to the checkout optional fields.
 */
class FluidCheckout_CheckoutHideOptionalFields extends FluidCheckout {

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
		// WooCommerce fields output
		add_filter( 'woocommerce_form_field', array( $this, 'add_optional_form_field_link_button' ), 100, 4 );
	}



	/**
	 * Return Checkout Steps class instance.
	 */
	public function checkout_steps() {
		return FluidCheckout_Steps::instance();
	}



	/**
	 * Get the checkout fields args.
	 */
	public function add_optional_form_field_link_button( $field, $key, $args, $value ) {
		// Bail if field is required
		if ( array_key_exists( 'required', $args ) && $args['required'] === true ) { return $field; }

		// Bail if optional field by its type
		if ( in_array( $args['type'], apply_filters( 'wfc_hide_optional_fields_skip_types', array( 'checkbox' ) ) ) ) { return $field; }

		// Always skip these fields
		$skip_list = array();

		// Maybe skip "address line 2" fields
		if ( get_option( 'wfc_hide_optional_fields_skip_address_2', 'no' ) === 'yes' ) {
			$skip_list[] = 'shipping_address_2';
			$skip_list[] = 'billing_address_2';
		}
	
		// Check if should skip current field
		if ( in_array( $key, apply_filters( 'wfc_hide_optional_fields_skip_list', $skip_list ) ) ) { return $field; }

		// Set attribute `data-autofocus` to focus on the optional field when expanding the section
		$field = str_replace( 'name="'. $key .'"', 'name="'. $key .'" data-autofocus', $field );
		
		// Move container classes to expansible block
		$container_class = esc_attr( implode( ' ', $args['class'] ) );
		$section_attributes = array( 'class' => 'form-row ' . $container_class );
		$field = str_replace( 'form-row '. $container_class, 'form-row ', $field );
		
		ob_start();
		
		// Add extensible block markup for the field
		/* translators: %s: Form field label */
		$this->checkout_steps()->output_expansible_form_section_start_tag( $key, apply_filters( 'wfc_expansible_section_toggle_label_'.$key, sprintf( __( 'Add %s', 'woocommerce-fluid-checkout' ), strtolower( $args['label'] ) ) ), $section_attributes );
		echo $field;
		$this->checkout_steps()->output_expansible_form_section_end_tag();

		$field = ob_get_clean();

		return $field;
	}

}

FluidCheckout_CheckoutHideOptionalFields::instance();
