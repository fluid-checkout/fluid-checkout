<?php
/**
 * Customizations to the checkout page.
 */
class FluidCheckoutValidation extends FluidCheckout {

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
		// Bail if checkout validation not enabled
		if ( get_option( 'wfc_enable_checkout_validation', 'true' ) !== 'true' ) { return; }
		
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		add_filter( 'wfc_checkout_field_args' , array( $this, 'change_checkout_email_field_args' ), 10 );
	}



	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		return array_merge( $classes, array( 'has-wfc-checkout-validation' ) );
	}



	/**
	 * Change email fields to include custom attribute for Mailcheck selector
	 */
	public function change_checkout_email_field_args( $field_args ) {
		$email_field_custom_attributes = array( 'data-mailcheck' => 1 );
		
		$checkout_email_fields = apply_filters( 'wfc_checkout_email_fields_for_mailcheck', array( 'billing_email' ) );
		foreach( $field_args as $field => $values ) {
			if ( in_array( $field, $checkout_email_fields ) ) {
				if ( ! array_key_exists( 'custom_attributes', $field_args[ $field ] ) ) { $field_args[ $field ][ 'custom_attributes' ] = array(); }
				$field_args[ $field ][ 'custom_attributes' ] = array_merge( $field_args[ $field ][ 'custom_attributes' ] ?: array(), $email_field_custom_attributes );
			}
		}

		return $field_args;
	}



	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue() {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return; }
		
		wp_localize_script(
			'wfc-bundles',
			'wfcValidationVars', 
			apply_filters( 'wfc_checkout_validation_script_settings', array(
				'validationMessages' => array( 
					'required'  => __( 'This is a required field.', 'woocommerce-fluid-checkout' ),
					'email'  => __( 'This is not a valid email address.', 'woocommerce-fluid-checkout' ),
					'confirmation'  => __( 'This does not match the related field value.', 'woocommerce-fluid-checkout' ),
				),
			) )
		);

	}

}

FluidCheckoutValidation::instance();
