<?php
defined( 'ABSPATH' ) || exit;

/**
 * Customizations to the checkout fields.
 */
class FluidCheckout_CheckoutFields extends FluidCheckout {

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
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_apply_checkout_field_args' ) ) { return false; }

		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Bail if feature is not enabled
		if( ! $this->is_feature_enabled() ) { return; }

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Checkout fields args
		add_filter( 'woocommerce_billing_fields', array( $this, 'change_checkout_field_args' ), 100 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'change_checkout_field_args' ), 100 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'maybe_change_shipping_company_field_args' ), 100 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'change_order_field_args' ), 100 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_field_args' ), 100 );

		// Remove `screen-reader-text` from some fields
		add_filter( 'woocommerce_default_address_fields', array( $this, 'remove_screen_reader_class_default_locale_field_args' ), 100 );

		// Add class for fields with description
		add_filter( 'woocommerce_default_address_fields', array( $this, 'add_field_has_description_class_default_locale_field_args' ), 100 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );

		// Extra field classes
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_field_type_class' ), 100, 3 );
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_select2_field_class' ), 100, 3 );

		// Checkbox label wrapper
		add_filter( 'woocommerce_form_field_checkbox', array( $this, 'add_checkbox_label_text_wrapper' ), 100, 4 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Checkout fields args
		remove_filter( 'woocommerce_billing_fields', array( $this, 'change_checkout_field_args' ), 100 );
		remove_filter( 'woocommerce_shipping_fields', array( $this, 'change_checkout_field_args' ), 100 );
		remove_filter( 'woocommerce_shipping_fields', array( $this, 'maybe_change_shipping_company_field_args' ), 100 );
		remove_filter( 'woocommerce_checkout_fields', array( $this, 'change_order_field_args' ), 100 );
		remove_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_field_args' ), 100 );

		// Remove `screen-reader-text` from some fields
		remove_filter( 'woocommerce_default_address_fields', array( $this, 'remove_screen_reader_class_default_locale_field_args' ), 100 );

		// Add class for fields with description
		remove_filter( 'woocommerce_default_address_fields', array( $this, 'add_field_has_description_class_default_locale_field_args' ), 100 );
		remove_filter( 'woocommerce_billing_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );
		remove_filter( 'woocommerce_shipping_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );
		remove_filter( 'woocommerce_checkout_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );

		// Extra field classes
		remove_filter( 'woocommerce_form_field_args', array( $this, 'add_field_type_class' ), 100, 3 );
		remove_filter( 'woocommerce_form_field_args', array( $this, 'add_select2_field_class' ), 100, 3 );

		// Checkbox label wrapper
		remove_filter( 'woocommerce_form_field_checkbox', array( $this, 'add_checkbox_label_text_wrapper' ), 100, 4 );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {

		// Checkout Field Attributes
		$settings[ 'checkoutFields' ] = WC()->checkout()->get_checkout_fields();

		// Override locale attributes
		$override_attributes = array();
		if ( true === apply_filters( 'fc_checkout_address_i18n_override_locale_required_attribute', false ) ) {
			$override_attributes[] = 'required';
		}

		// Address i18n
		$settings[ 'addressI18n' ] = array(
			'overrideLocaleAttributes'  => apply_filters( 'fc_checkout_address_i18n_override_locale_attributes', $override_attributes ),
		);
		
		return $settings;
	}



	/**
	 * Get the checkout fields args.
	 */
	public function get_checkout_field_args() {
		$billing_email_description = apply_filters( 'fc_checkout_email_field_description', __( 'Order number and receipt will be sent to this email address.', 'fluid-checkout' ) );
		$billing_company_class = 'required' === FluidCheckout_Settings::instance()->get_option( 'woocommerce_checkout_phone_field' ) ? 'form-row-last' : 'form-row-wide';

		$fields_args = array(
			'billing_email'         => array( 'priority' => 5, 'description' => $billing_email_description, 'type' => 'email', 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'contact email', 'data-leadin-email' => true ) ),

			'billing_first_name'    => array( 'priority' => 10, 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'contact given-name', 'data-leadin-fname' => true ) ),
			'billing_last_name'     => array( 'priority' => 20, 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'contact family-name', 'data-leadin-lname' => true ) ),
			'billing_phone'         => array( 'priority' => 30, 'class' => array( 'form-row-first' ), 'type' => 'tel', 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'contact tel', 'data-leadin-telephone' => true ) ),
			'billing_company'       => array( 'priority' => 40, 'class' => array( $billing_company_class ), 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'billing organization' ) ),
			'billing_address_1'     => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'billing address-line1' ) ),
			'billing_address_2'     => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'billing address-line2' ) ),
			'billing_city'          => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'billing address-level2' ) ),
			'billing_state'         => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'billing address-level1' ) ),
			'billing_country'       => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'billing country' ) ),
			'billing_postcode'      => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'billing postal-code' ) ),

			'shipping_first_name'   => array( 'priority' => 10, 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping given-name' ) ),
			'shipping_last_name'    => array( 'priority' => 20, 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping family-name' ) ),
			'shipping_company'      => array( 'priority' => 30, 'class' => array( 'form-row-wide' ), 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping organization' ) ),
			'shipping_address_1'    => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping address-line1' ) ),
			'shipping_address_2'    => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping address-line2' ) ),
			'shipping_city'         => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping address-level2' ) ),
			'shipping_state'        => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping address-level1' ) ),
			'shipping_country'      => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping country' ) ),
			'shipping_postcode'     => array( 'autocomplete' => 'off', 'custom_attributes' => array( 'data-autocomplete' => 'shipping postal-code' ) ),
		);

		// Only apply class changes on checkout and account pages
		if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_account_page() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ) {
			$fields_args[ 'shipping_city' ][ 'class' ] = array( 'form-row-first' );
			$fields_args[ 'shipping_state' ][ 'class' ] = array( 'form-row-last' );
			$fields_args[ 'shipping_postcode' ][ 'class' ] = array( 'form-row-first' );
		}

		return apply_filters( 'fc_checkout_field_args', $fields_args );
	}



	/**
	 * Change default locale fields args.
	 *
	 * @param   array  $fields  Default address fields args.
	 */
	public function change_default_locale_field_args( $fields ) {
		$new_field_args = array();

		// Maybe change address 1 field description
		if ( true === apply_filters( 'fc_apply_address_1_field_description', true ) ) {
			$new_field_args[ 'address_1' ] = array( 'class' => array( 'form-row-wide' ), 'description' => __( 'House number and street name', 'woocommerce' ), 'placeholder' => '' );
		}

		// Maybe change address 2 field description and place holder
		if ( true === apply_filters( 'fc_apply_address_2_field_description', true ) ) {
			$address_2_field_description = __( 'Apartment, unit, building, floor, etc.', 'fluid-checkout' );
			$new_field_args[ 'address_2' ] = array( 'class' => array( 'form-row-wide' ), 'description' => $address_2_field_description, 'placeholder' => $address_2_field_description );
		}

		// Only apply class changes on checkout and account pages
		if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_account_page() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ) {
			$new_field_args[ 'city' ][ 'class' ] = array( 'form-row-first' );
			$new_field_args[ 'state' ][ 'class' ] = array( 'form-row-last' );
			$new_field_args[ 'postcode' ][ 'class' ] = array( 'form-row-first' );
		}

		$new_field_args = apply_filters( 'fc_default_locale_field_args', $new_field_args );

		foreach( $fields as $field_key => $original_args ) {
			$new_args = array_key_exists( $field_key, $new_field_args ) ? $new_field_args[ $field_key ] : array();
			$fields[ $field_key ] = $this->merge_form_field_args( $original_args, $new_args );
		}

		return $fields;
	}



	/**
	 * Maybe change shipping company field arguments to make it required, optional or remove the field.
	 *
	 * @param   array  $fields  Fields used in checkout.
	 */
	public function maybe_change_shipping_company_field_args( $fields ) {
		// Bail if shipping company field is not available
		if ( ! array_key_exists( 'shipping_company', $fields ) ) { return $fields; }

		// Get field visibility option value
		$field_visibility = FluidCheckout_Settings::instance()->get_option( 'fc_shipping_company_field_visibility' );

		// Maybe remove the field
		if ( 'no' === $field_visibility ) {
			unset( $fields[ 'shipping_company' ] );
		}
		// Maybe set as required
		else if( 'required' === $field_visibility ) {
			$fields[ 'shipping_company' ][ 'required' ] = true;
		}

		return $fields;
	}



	/**
	 * Remove the class `screen-reader-text` from the label of some fields.
	 *
	 * @param   array  $fields  Default address fields args.
	 */
	public function remove_screen_reader_class_default_locale_field_args( $fields ) {
		$target_field_ids = array( 'address_2' );

		foreach( $fields as $field_key => $field_args ) {
			// Bail if field is not in the target list
			if ( ! in_array( $field_key, $target_field_ids ) ) { continue; }

			// Remove `screen-reader-text` class from the field label
			if ( array_key_exists( 'label_class', $fields[ $field_key ] ) && in_array( 'screen-reader-text', $fields[ $field_key ]['label_class'] ) ) {
				$class_key = array_search( 'screen-reader-text', $fields[ $field_key ]['label_class'] );
				unset( $fields[ $field_key ]['label_class'][ $class_key ] );
			}
		}

		return $fields;
	}



	/**
	 * Add a class to fields with description for default locale fields.
	 *
	 * @param   array  $fields  Default address fields args.
	 */
	public function add_field_has_description_class_default_locale_field_args( $fields ) {
		foreach( $fields as $field_key => $field_args ) {
			// Bail if field does not have description
			if ( ! array_key_exists( 'description', $fields[ $field_key ] ) ) { continue; }

			// Maybe initialize `class` array
			if ( ! array_key_exists( 'class', $fields[ $field_key ] ) || ! is_array( $fields[ $field_key ]['class'] ) ) {
				$fields[ $field_key ]['class'] = array();
			}

			// Maybe add class for field with description
			if ( ! in_array( 'has-description', $fields[ $field_key ]['class'] ) ) {
				array_push( $fields[ $field_key ]['class'], 'has-description' );
			}
		}

		return $fields;
	}



	/**
	 * Add a class to fields with description for WooCommerce fields.
	 *
	 * @param   array  $fields  Default address fields args.
	 */
	public function add_field_has_description_class_checkout_fields_args( $fields ) {
		// Bail if fields are not available
		if ( ! is_array( $fields ) ) { return $fields; }

		foreach( $fields as $field_key => $field_args ) {
			// Bail if field does not have description
			if ( ! array_key_exists( 'description', $fields[ $field_key ] ) ) { continue; }

			// Maybe initialize `class` array
			if ( ! array_key_exists( 'class', $fields[ $field_key ] ) || ! is_array( $fields[ $field_key ]['class'] ) ) {
				$fields[ $field_key ]['class'] = array();
			}

			// Maybe add class for field with description
			if ( ! in_array( 'has-description', $fields[ $field_key ]['class'] ) ) {
				array_push( $fields[ $field_key ]['class'], 'has-description' );
			}
		}

		return $fields;
	}



	/**
	 * Remove `form-row-XX` classes from field classes to avoid conflicts the merge the new classes into it.
	 *
	 * @param   array  $field_classes  Contains field classes.
	 * @param   array  $new_classes   New classes to merge into $field_classes.
	 *
	 * @return  array  $field_classes  Changed field classes.
	 */
	public function merge_form_field_class_args( $field_classes, $new_classes ) {
		// Set default value
		$field_classes = is_array( $field_classes ) ? $field_classes : array();
		$new_classes = is_array( $new_classes ) ? $new_classes : array();

		// Maybe convert the class argument to an array
		if ( is_string( $field_classes ) ) {
			$field_classes = explode( ' ', $field_classes );
		}

		// Maybe remove form-row-XX classes
		$form_row_classes = array( 'form-row-first', 'form-row-last', 'form-row-wide', 'form-row-middle' );

		if ( array_intersect( $new_classes, $form_row_classes ) ) {
			$field_classes = array_diff( $field_classes, $form_row_classes );
		}

		$field_classes = array_merge( $field_classes, $new_classes );

		return $field_classes;
	}



	/**
	 * Merge args for one checkout field.
	 *
	 * @param   array  $field_args      The original field args.
	 * @param   array  $new_field_args  The new field args to be merged.
	 *
	 * @return  array                   Changed field arguments.
	 */
	public function merge_form_field_args( $field_args, $new_field_args ) {
		// Bail if parameters are invalid
		if ( ! is_array( $field_args ) || ! is_array( $new_field_args ) ) { return $field_args; }

		// Merge class args and remove it from $new_field_args to avoid conflicts when merging all field args below
		if ( array_key_exists( 'class', $new_field_args ) && array_key_exists( 'class', $field_args ) ) {
			$field_args[ 'class' ] = $this->merge_form_field_class_args( $field_args[ 'class' ], $new_field_args[ 'class' ] );
			unset( $new_field_args[ 'class' ] );
		}

		$field_args = array_merge( $field_args, $new_field_args );

		return $field_args;
	}



	/**
	 * Change Address Fields arguments for account address edit form.
	 *
	 * @param   array  $fields  Fields used in checkout.
	 */
	public function change_checkout_field_args( $fields ) {
		// Bail if fields are not available
		if ( ! is_array( $fields ) ) { return $fields; }

		// Get new field args
		$new_field_args = $this->get_checkout_field_args();

		// Merge new field args into the original field args
		foreach( $fields as $field_key => $original_args ) {
			$new_args = array_key_exists( $field_key, $new_field_args ) ? $new_field_args[ $field_key ] : array();
			$fields[ $field_key ] = $this->merge_form_field_args( $original_args, $new_args );
		}

		return $fields;
	}



	/**
	 * Change order fields args.
	 *
	 * @param   array  $fields  Fields used in checkout.
	 */
	public function change_order_field_args( $fields ) {
		$field_group = 'order';
		$fields[ $field_group ] = $this->change_checkout_field_args( $fields[ $field_group ] );
		return $fields;
	}



	/**
	 * Add extra class field types.
	 *
	 * @param   array   $args   Checkout field args.
	 * @param   string  $key    Field key.
	 * @param   mixed   $value  Field value.
	 *
	 * @return  array           Modified checkout field args.
	 */
	public function add_field_type_class( $args, $key, $value ) {
		// Initialize class argument if not existing yet
		if ( ! array_key_exists( 'class', $args ) ) { $args[ 'class' ] = array(); }

		// Add extra class
		$args[ 'class' ] = $this->merge_form_field_class_args( $args[ 'class' ], array( 'fc-' . $args[ 'type' ] . '-field' ) );

		return $args;
	}
	
	
	
	/**
	 * Add extra class for `select2` fields.
	 *
	 * @param   array   $args   Checkout field args.
	 * @param   string  $key    Field key.
	 * @param   mixed   $value  Field value.
	 *
	 * @return  array           Modified checkout field args.
	 */
	public function add_select2_field_class( $args, $key, $value ) {
		// Define field types which render as a `select2` field
		$select2_field_types = apply_filters( 'fc_select2_field_types', array( 'country', 'state', 'select' ) );

		// Bail if field type is not a `select2` field
		if ( ! in_array( $args[ 'type' ], $select2_field_types ) ) { return $args; }

		// Initialize class argument if not existing yet
		if ( ! array_key_exists( 'class', $args ) ) { $args[ 'class' ] = array(); }

		// Define extra classes to add
		$extra_classes = array( 'fc-select2-field' );

		// Add extra class for type of `select` fields
		// which is needed when fields change type dynamically.
		// Treat `state` fields differently
		if ( 'state' === $args[ 'type' ] ) {
			/* Get country this state field is representing */
			$for_country = isset( $args['country'] ) ? $args['country'] : WC()->checkout->get_value( 'billing_state' === $key ? 'billing_country' : 'shipping_country' );
			$states      = WC()->countries->get_states( $for_country );

			// Text field
			if ( false === $states ) {
				$extra_classes[] = 'fc-select-field--text';
			}
			// Hidden field
			else if ( is_array( $states ) && empty( $states ) ) {
				$extra_classes[] = 'fc-select-field--hidden';
			}
			// Select field
			else {
				$extra_classes[] = 'fc-select-field--select';
			}
		}
		// Add `select` type class otherwise
		else {
			$extra_classes[] = 'fc-select-field--select';
		}

		// Add extra classes
		$args[ 'class' ] = $this->merge_form_field_class_args( $args[ 'class' ], $extra_classes );

		return $args;
	}



	/**
	 * Add a wrapper element to checkbox fields label text.
	 * 
	 * @param   string  $field  The HTML for the field.
	 * @param   string  $key    The field key.
	 * @param   array   $args   The field args.
	 * @param   string  $value  The field value.
	 */
	public function add_checkbox_label_text_wrapper( $field, $key, $args, $value ) {
		// Bail if field is not a checkbox field
		if ( ! is_array( $args ) || ! array_key_exists( 'type', $args ) || 'checkbox' !== $args[ 'type' ] ) { return $field; }

		//
		// COPIED FROM `woocommerce_form_field` function
		//
		if ( $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
		} else {
			$required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
		}

		if ( is_string( $args['label_class'] ) ) {
			$args['label_class'] = array( $args['label_class'] );
		}

		if ( is_null( $value ) ) {
			$value = $args['default'];
		}

		// Custom attribute handling.
		$custom_attributes         = array();
		$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

		if ( $args['maxlength'] ) {
			$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
		}

		if ( $args['minlength'] ) {
			$args['custom_attributes']['minlength'] = absint( $args['minlength'] );
		}

		if ( ! empty( $args['autocomplete'] ) ) {
			$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
		}

		if ( true === $args['autofocus'] ) {
			$args['custom_attributes']['autofocus'] = 'autofocus';
		}

		if ( $args['description'] ) {
			$args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
		}

		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
		//
		// END - COPIED FROM `woocommerce_form_field` function
		//

		// Replace the original label text adding a wrapper element to it
		$field = str_replace( $args['label'] . $required . '</label>', '<span class="fc-checkbox-label-text">' . $args['label'] . $required . '</span>' . '</label>', $field );

		return $field;
	}

}

FluidCheckout_CheckoutFields::instance();
