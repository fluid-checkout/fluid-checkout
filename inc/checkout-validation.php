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
	 * Check whether the feature is enabled or not.
	 */
	public function is_feature_enabled() {
		// Bail if feature is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_enable_checkout_validation' ) ) { return false; }

		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Bail if not on front end
		if ( is_admin() ) { return; }

		// Bail if feature is not enabled
		if( ! $this->is_feature_enabled() ) { return; }

		// Body class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_mailcheck_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Mailcheck validation
		add_filter( 'fc_checkout_field_args' , array( $this, 'maybe_enable_mailcheck_on_email_field_args' ), 10 );

		// Add validation status classes to checkout fields
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_checkout_field_validation_status_classes' ), 100, 3 );
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_checkout_field_validation_icon_hide_class' ), 100, 3 );

		// Fix required marker accessibility
		add_filter( 'woocommerce_form_field', array( $this, 'change_required_field_attributes' ), 100, 4 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Bail if not on front end
		if ( is_admin() ) { return; }

		// Body class
		remove_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Enqueue assets
		remove_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_mailcheck_assets' ), 10 );

		// JS settings object
		remove_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Mailcheck validation
		remove_filter( 'fc_checkout_field_args' , array( $this, 'maybe_enable_mailcheck_on_email_field_args' ), 10 );

		// Add validation status classes to checkout fields
		remove_filter( 'woocommerce_form_field_args', array( $this, 'add_checkout_field_validation_status_classes' ), 100, 3 );
		remove_filter( 'woocommerce_form_field_args', array( $this, 'add_checkout_field_validation_icon_hide_class' ), 100, 3 );

		// Fix required marker accessibility
		remove_filter( 'woocommerce_form_field', array( $this, 'change_required_field_attributes' ), 100, 4 );
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array  $classes  Current classes.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $classes; }

		return array_merge( $classes, array( 'has-fc-checkout-validation' ) );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Styles
		wp_register_style( 'fc-checkout-validation', FluidCheckout_Enqueue::instance()->get_style_url( 'css/checkout-validation' ), NULL, NULL );

		// Scripts
		wp_register_script( 'fc-checkout-validation', FluidCheckout_Enqueue::instance()->get_script_url( 'js/checkout-validation' ), array( 'jquery', 'wc-checkout', 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation', 'window.addEventListener("load",function(){CheckoutValidation.init(fcSettings.checkoutValidation);})' );
		wp_register_script( 'fc-mailcheck', FluidCheckout_Enqueue::instance()->get_script_url( 'js/lib/mailcheck' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'fc-mailcheck-init', FluidCheckout_Enqueue::instance()->get_script_url( 'js/mailcheck-init' ), array( 'jquery', 'fc-utils', 'fc-mailcheck' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-mailcheck-init', 'window.addEventListener("load",function(){MailcheckInit.init(fcSettings.checkoutValidation.mailcheckSuggestions);})' );

		// Brazilian documents validation script
		wp_register_script( 'fc-checkout-validation-brazilian-documents', FluidCheckout_Enqueue::instance()->get_script_url( 'js/checkout-validation-brazilian-documents' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-brazilian-documents', 'window.addEventListener("load",function(){CheckoutValidationBrazilianDocuments.init(fcSettings.checkoutValidationBrazilianDocuments);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Styles
		wp_enqueue_style( 'fc-checkout-validation' );

		// Scripts
		wp_enqueue_script( 'fc-checkout-validation' );
	}

	/**
	 * Enqueue scripts for Brazilian documents validation.
	 */
	public function enqueue_mailcheck_assets() {
		// Scripts
		wp_enqueue_script( 'fc-mailcheck' );
		wp_enqueue_script( 'fc-mailcheck-init' );
	}

	/**
	 * Enqueue scripts for Brazilian documents validation.
	 */
	public function enqueue_scripts_brazilian_documents_validation() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-brazilian-documents' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_mailcheck_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Bail if feature is not enabled
		if ( ! $this->is_email_typo_suggestions_enabled() ) { return; }

		// Enqueue Mailcheck assets
		$this->enqueue_mailcheck_assets();
	}




	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {

		$settings[ 'checkoutValidation' ] = apply_filters( 'fc_checkout_validation_script_settings', array(
			'formRowSelector'                    => '.form-row, .shipping-method__package',
			'validateFieldsSelector'             => '.input-text, .input-checkbox, input[type="date"], select, .shipping-method__options',
			'referenceNodeSelector'              => '.input-text, .input-checkbox, input[type="date"], select, .shipping-method__options',
			'alwaysValidateFieldsSelector'       => '',
			'mailcheckSuggestions'               => array(
				/* translators: %s: html for the email address typo correction suggestion link */
				'suggestedElementTemplate'       => '<div class="fc-mailcheck-suggestion" data-mailcheck-suggestion>' . sprintf( __( 'Did you mean %s?', 'fluid-checkout' ), '<a class="mailcheck-suggestion" href="#apply-suggestion" role="button" aria-label="'.esc_attr( __( 'Change email address to: {suggestion-value}', 'fluid-checkout' ) ).'" data-mailcheck-apply data-suggestion-value="{suggestion-value}">{suggestion}</a>' ) . '</div>',
			),
			'validationMessages'                 => array(
				'required'                       => __( 'This is a required field.', 'fluid-checkout' ),
				'email'                          => __( 'This is not a valid email address.', 'fluid-checkout' ),
				'confirmation'                   => __( 'This field does not match the related field value.', 'fluid-checkout' ),
			),
		) );

		// Add validation settings
		$settings[ 'checkoutValidationBrazilianDocuments' ] = apply_filters( 'fc_checkout_validation_brazilian_documents_script_settings', array(
			'validateCPF'         => 'yes',
			'validateCNPJ'        => 'yes',
			'validationMessages'  => array(
				'cpf_invalid'          => __( 'The CPF number "{cpf_number}" is invalid.', 'fluid-checkout' ),
				'cnpj_invalid'         => __( 'The CNPJ number "{cnpj_number}" is invalid.', 'fluid-checkout' ),
			),
		) );

		return $settings;
	}



	/**
	 * Check whether the Mailcheck email typo suggestions feature is enabled.
	 */
	public function is_email_typo_suggestions_enabled() {
		return true === apply_filters( 'fc_enable_checkout_email_mailcheck', true );
	}



	/**
	 * Checks whether a phone number is valid.
	 *
	 * @param   string  $phone_number  The phone number to validate.
	 */
	public function is_valid_phone_number( $phone_number ) {
		$is_valid = WC_Validation::is_phone( $phone_number );
		return apply_filters( 'fc_checkout_is_valid_phone_number', $is_valid, $phone_number );
	}



	/**
	 * Change email fields to include custom attribute for Mailcheck selector.
	 *
	 * @param   array  $field_args  Contains checkout field arguments.
	 */
	public function maybe_enable_mailcheck_on_email_field_args( $field_args ) {
		// Bail if feature is not enabled
		if ( ! $this->is_email_typo_suggestions_enabled() ) { return $field_args; }

		// Define custom attributes to enable the Mailcheck feature on email fields
		$email_field_custom_attributes = array( 'data-mailcheck' => 1 );

		// Get list of email fields to apply the custom attributes
		$checkout_email_fields = apply_filters( 'fc_checkout_email_fields_for_mailcheck', array( 'billing_email' ) );

		// Apply custom attributes to each email field
		foreach( $field_args as $field => $values ) {
			// Bail if field is not an email field
			if ( ! in_array( $field, $checkout_email_fields ) ) { continue; }

			// Maybe create array of custom attributes for the field
			if ( ! array_key_exists( 'custom_attributes', $field_args[ $field ] ) ) {
				$field_args[ $field ][ 'custom_attributes' ] = array();
			}

			// Add custom attributes to the field
			$field_args[ $field ][ 'custom_attributes' ] = array_merge( $field_args[ $field ][ 'custom_attributes' ] ?: array(), $email_field_custom_attributes );
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
		if ( $field_valid && in_array( 'phone', $format, true ) && '' !== $value && ! $this->is_valid_phone_number( $value ) ) {
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
		// Use loose comparison for `required` attribute to allow type casting as some plugins use `1` instead of `true` to set fields as required.
		if ( ! array_key_exists( 'required', $args ) || true != $args['required'] ) { return $field; }
		
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

		// Maybe add required attribute for accessibility
		if ( ! empty( $search_str ) ) {
			$field = str_replace( $search_str, $search_str . ' aria-required="true" ', $field );
		}

		return $field;
	}

}

FluidCheckout_Validation::instance();
