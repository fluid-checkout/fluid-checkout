<?php
defined( 'ABSPATH' ) || exit;

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
		// Body class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// TODO: Enqueue validation styles and scripts from WP instead of RequireBundle

		// Checkout validation settings
		add_filter( 'fc_js_settings', array( $this, 'add_checkout_validation_js_settings' ), 10 );

		// Mailcheck validation
		add_filter( 'fc_checkout_field_args' , array( $this, 'change_checkout_email_field_args' ), 10 );

		// Add validation status classes to checkout fields
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_checkout_field_validation_status_classes' ), 100, 3 );
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array  $classes  Current classes.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		return array_merge( $classes, array( 'has-fc-checkout-validation' ) );
	}




	/**
	 * Add Checkout Validation settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_checkout_validation_js_settings( $settings ) {

		$settings[ 'checkoutValidation' ] = apply_filters( 'fc_checkout_validation_script_settings', array(
			'mailcheckSuggestions' => array(
				/* translators: %s: html for the email address typo correction suggestion link */
				'suggestedElementTemplate'    => '<div class="fc-mailcheck-suggestion" data-mailcheck-suggestion>' . sprintf( __( 'Did you mean %s?', 'fluid-checkout' ), '<a class="mailcheck-suggestion" href="#apply-suggestion" data-mailcheck-apply data-suggestion-value="{suggestion-value}">{suggestion}</a>' ) . '</div>',
			),
			'validationMessages' => array(
				'required'                    => __( 'This is a required field.', 'fluid-checkout' ),
				'email'                       => __( 'This is not a valid email address.', 'fluid-checkout' ),
				'confirmation'                => __( 'This does not match the related field value.', 'fluid-checkout' ),
			),
		) );

		return $settings;
	}



	/**
	 * Change email fields to include custom attribute for Mailcheck selector.
	 *
	 * @param   array  $field_args  Contains checkout field arguments.
	 */
	public function change_checkout_email_field_args( $field_args ) {
		$email_field_custom_attributes = array( 'data-mailcheck' => 1 );

		$checkout_email_fields = apply_filters( 'fc_checkout_email_fields_for_mailcheck', array( 'billing_email' ) );
		foreach( $field_args as $field => $values ) {
			if ( in_array( $field, $checkout_email_fields ) ) {
				if ( ! array_key_exists( 'custom_attributes', $field_args[ $field ] ) ) { $field_args[ $field ][ 'custom_attributes' ] = array(); }
				$field_args[ $field ][ 'custom_attributes' ] = array_merge( $field_args[ $field ][ 'custom_attributes' ] ?: array(), $email_field_custom_attributes );
			}
		}

		return $field_args;
	}



	/**
	 * Add validation status classes to checkout fields args before outputting them to the page.
	 *
	 * @param   array   $args   Checkout field args.
	 * @param   string  $key    Field key.
	 * @param   mixed   $value  Field value.
	 *
	 * @return  array           Modified checkout field args.
	 */
	public function add_checkout_field_validation_status_classes( $args, $key, $value ) {
		// Bail, skip validation for the field
		if ( array_key_exists( 'fc_skip_server_validation', $args ) && true === $args[ 'fc_skip_server_validation' ] ) { return $args; }

		// Initialize class argument if not existing yet
		if ( ! array_key_exists( 'class', $args ) ) { $args['class'] = array(); }

		$format = array_filter( isset( $args['validate'] ) ? (array) $args['validate'] : array() );
		$field_valid = false;

		// Set field as `valid` when it has any value, applies for required or optional fields,
		// other validation rules are applied later that may invalidate the field
		if ( ! empty( $value ) ) {
			$field_valid = true;
		}

		// Validate email fields
		if ( $field_valid && in_array( 'email', $format, true ) && '' !== $value && ! is_email( $value ) ) {
			$field_valid = false;
			$args['class'] = array_merge( $args['class'], array( 'woocommerce-invalid', 'woocommerce-invalid-email' ) );
		}

		// Validate phone fields
		if ( $field_valid && in_array( 'phone', $format, true ) && '' !== $value && ! WC_Validation::is_phone( $value ) ) {
			$field_valid = false;
			$args['class'] = array_merge( $args['class'], array( 'woocommerce-invalid', 'woocommerce-invalid-phone' ) );
		}

		// Validate postcode fields
		if ( $field_valid && in_array( 'postcode', $format, true ) && '' !== $value ) {
			$fieldset = strpos( $key, 'shipping_' ) !== false ? 'shipping' : 'billing';
			$country = WC()->customer->{"get_{$fieldset}_country"}();
			$formatted_postcode = wc_format_postcode( $value, $country );

			if ( ! WC_Validation::is_postcode( $formatted_postcode, $country ) ) {
				$field_valid = false;
				$args['class'] = array_merge( $args['class'], array( 'woocommerce-invalid', 'woocommerce-invalid-postcode' ) );
			}
		}

		// Maybe add `valid` classes
		if ( true == $field_valid ) {
			$args['class'] = array_merge( $args['class'], array( 'woocommerce-validated' ) );
		}

		return $args;
	}

}

FluidCheckoutValidation::instance();
