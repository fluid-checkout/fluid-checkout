<?php
defined( 'ABSPATH' ) || exit;

/**
 * Customizations to the checkout page.
 */
class FluidCheckout_Validation extends FluidCheckout {

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
		// Bail if not on front end
		if ( is_admin() ) { return; }

		// Body class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_js_settings', array( $this, 'add_checkout_validation_js_settings' ), 10 );

		// Mailcheck validation
		add_filter( 'fc_checkout_field_args' , array( $this, 'change_checkout_email_field_args' ), 10 );

		// Add validation status classes to checkout fields
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_checkout_field_validation_status_classes' ), 100, 3 );
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_checkout_field_validation_icon_hide_class' ), 100, 3 );

		// Fix required marker accessibility
		add_filter( 'woocommerce_form_field', array( $this, 'change_required_field_attributes' ), 100, 4 );
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
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() ){ return; }

		// Styles
		if ( is_rtl() ) {
			wp_enqueue_style( 'fc-checkout-validation', self::$directory_url . 'css/checkout-validation-rtl'. self::$asset_version . '.css', NULL, NULL );
		}
		else {
			wp_enqueue_style( 'fc-checkout-validation', self::$directory_url . 'css/checkout-validation'. self::$asset_version . '.css', NULL, NULL );
		}

		// Checkout steps scripts
		wp_enqueue_script( 'fc-checkout-validation', self::$directory_url . 'js/checkout-validation'. self::$asset_version . '.js', array( 'jquery', 'wc-checkout' ), NULL, true );
		wp_add_inline_script( 'fc-checkout-validation', 'window.addEventListener("load",function(){CheckoutValidation.init(fcSettings.checkoutValidation);})' );
	}




	/**
	 * Add Checkout Validation settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_checkout_validation_js_settings( $settings ) {

		$settings[ 'checkoutValidation' ] = apply_filters( 'fc_checkout_validation_script_settings', array(
			'formRowSelector'                    => '.form-row, .shipping-method__package',
			'validateFieldsSelector'             => '.input-text, select, .shipping-method__options',
			'alwaysValidateFieldsSelector'       => '',
			'mailcheckSuggestions'               => array(
				/* translators: %s: html for the email address typo correction suggestion link */
				'suggestedElementTemplate'       => '<div class="fc-mailcheck-suggestion" data-mailcheck-suggestion>' . sprintf( __( 'Did you mean %s?', 'fluid-checkout' ), '<a class="mailcheck-suggestion" href="#apply-suggestion" role="button" aria-label="'.esc_attr( __( 'Change email address to: {suggestion-value}', 'fluid-checkout' ) ).'" data-mailcheck-apply data-suggestion-value="{suggestion-value}">{suggestion}</a>' ) . '</div>',
			),
			'validationMessages'                 => array(
				'required'                       => __( 'This is a required field.', 'fluid-checkout' ),
				'email'                          => __( 'This is not a valid email address.', 'fluid-checkout' ),
				'confirmation'                   => __( 'This does not match the related field value.', 'fluid-checkout' ),
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

	/**
	 * Add extra class to checkout fields to hide the validation icon.
	 *
	 * @param   array   $args   Checkout field args.
	 * @param   string  $key    Field key.
	 * @param   mixed   $value  Field value.
	 *
	 * @return  array           Modified checkout field args.
	 */
	public function add_checkout_field_validation_icon_hide_class( $args, $key, $value ) {
		$no_validation_icon_field_types = apply_filters( 'fc_no_validation_icon_field_types', array( 'hidden', 'checkbox', 'radio' ) );
		$no_validation_icon_field_keys = apply_filters( 'fc_no_validation_icon_field_keys', array() );

		// Bail if field type
		if ( ! in_array( $args[ 'type' ], $no_validation_icon_field_types ) && ! in_array( $args[ 'id' ], $no_validation_icon_field_keys ) ) { return $args; }

		// Initialize class argument if not existing yet
		if ( ! array_key_exists( 'class', $args ) ) { $args['class'] = array(); }

		// Add extra class
		$args['class'] = array_merge( $args['class'], array( 'fc-no-validation-icon' ) );

		return $args;
	}



	/**
	 * Change the fields args for required fields.
	 *
	 * @param   string  $field  Field html markup to be changed.
	 * @param   string  $key    Field key.
	 * @param   arrray  $args   Field args.
	 * @param   mixed   $value  Value of the field. Defaults to `null`.
	 */
	public function change_required_field_attributes( $field, $key, $args, $value ) {
		
		// Bail if field is not required
		if ( ! array_key_exists( 'required', $args ) || $args['required'] != true ) { return $field; }
		
		// Add `aria-label` to required field labels
		$field = str_replace( '<abbr class="required"', '<abbr class="required" aria-label="' . __( '(Required)', 'fluid-checkout' ) . '" ', $field );
		
		// Add `required` attribute to required fields
		$search_str = null;
		switch ( $args['type'] ) {
			case 'country':
				$countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();
				if ( 1 !== count( $countries ) ) {
					$search_str = '<select';
				}
				break;
			case 'state':
				/* Get country this state field is representing */
				$for_country = isset( $args['country'] ) ? $args['country'] : WC()->checkout->get_value( 'billing_state' === $key ? 'billing_country' : 'shipping_country' );
				$states      = WC()->countries->get_states( $for_country );
				if ( ! is_null( $for_country ) && is_array( $states ) ) {
					$search_str = '<select';
				} else {
					$search_str = '<input';
				}
				break;
			case 'textarea':
				$search_str = '<textarea';
				break;
			case 'text':
			case 'password':
			case 'datetime':
			case 'datetime-local':
			case 'date':
			case 'month':
			case 'time':
			case 'week':
			case 'number':
			case 'email':
			case 'url':
			case 'tel':
			case 'radio':
			case 'checkbox':
				$search_str = '<input';
				break;
			case 'select':
				$search_str = '<select';
				break;
		}

		if ( ! empty( $search_str ) ) {
			$field = str_replace( $search_str, $search_str . ' required aria-required="true" ', $field );
		}

		return $field;
	}

}

FluidCheckout_Validation::instance();
