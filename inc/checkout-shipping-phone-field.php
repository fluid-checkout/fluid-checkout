<?php

/**
 * Add shipping phone field to the checkout page.
 */
class FluidCheckout_CheckoutShippingPhoneField extends FluidCheckout {

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
		// Add shipping phone field
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_phone_field' ), 5 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_with_shipping_phone' ), 10 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'add_shipping_phone_to_admin_screen' ), 10 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'output_order_formatted_shipping_address_with_phone' ), 1, 2 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_replacement_field_shipping_phone' ), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'add_shipping_phone_to_formats' ), 10 );
		add_filter( 'wfc_substep_shipping_address_text', array( $this, 'add_shipping_phone_to_substep_text_format' ), 10 );

		// Persist shipping phone to the user's session
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'set_shipping_phone_session' ), 10 );
		add_filter( 'default_checkout_shipping_phone', array( $this, 'change_default_shipping_phone_value' ), 10, 2 );
	}



	/**
	 * Get shipping phone field for address forms.
	 *
	 * @return  array $args Arguments for adding shipping phone field.
	 */
	public function get_shipping_phone_field() {
		return apply_filters( 'wfc_shipping_phone_field_args', array(
			'label'     => __( 'Shipping Phone', 'woocommerce-fluid-checkout' ),
			'required'  => false,
			'class'     => array( 'form-row-first' ),
			'clear'     => true
		) );
	}

	/**
	 * Add shipping phone field to edit address fields.
	 *
	 * @param   array  $fields  Fields used in checkout.
	 */
	public function add_shipping_phone_field( $fields ) {
		$fields['shipping_phone'] = $this->get_shipping_phone_field();

		$field_args = $this->get_checkout_field_args();
		foreach( $field_args as $field => $values ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ] = array_merge( $fields[ $field ], $values ); }
		}

		return $fields;
	}



	/**
	 * Update the order meta with shipping phone.
	 *
	 * @param   int  $order_id  Order ID.
	 */
	public function update_order_meta_with_shipping_phone( $order_id ) {
		$shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
		update_post_meta( $order_id, '_shipping_phone', $shipping_phone );
	}

	/**
	 * Add the shipping phone field to admin screen.
	 */
	public function add_shipping_phone_to_admin_screen( $shipping_fields ) {
		$shipping_fields[ 'phone' ] = array(
			'label'         => __( 'Phone', 'woocommerce' ),
			'wrapper_class' => 'form-field-wide',
			'show'          => false,
		);

		return $shipping_fields;
	}



	/**
	 * Output shipping phone to the address details on order view.
	 *
	 * @param   array  $address Contains address fields.
	 * @param   WC_Order   $order   The Order object.
	 */
	public function output_order_formatted_shipping_address_with_phone( $address, $order ) {
		$shipping_phone = get_post_meta( $order->get_id(), '_shipping_phone', true );
		if ( ! empty( $shipping_phone ) ) { $address['shipping_phone'] = $shipping_phone; }
		return $address;
	}



	/**
	 * Add replacement for shipping phone.
	 *
	 * @param   array  $replacements Contains replacements values.
	 * @param   array  $address Contains address fields.
	 */
	public function add_replacement_field_shipping_phone( $replacements, $address ) {
		$replacements['{shipping_phone}'] = isset( $address['shipping_phone'] ) ? $address['shipping_phone'] : '';
		return $replacements;
	}



	/**
	 * Add replacement for shipping phone to address formats localisation.
	 *
	 * @param   array  $formats  Country address formats.
	 */
	public function add_shipping_phone_to_formats( $formats ) {
		foreach ( $formats as $locale => $format ) {
			$formats[ $locale ] .= "\n{shipping_phone}";
		}
		return $formats;
	}




	/**
	 * Get shipping phone values from session.
	 *
	 * @return  array  The shipping phone field values saved to session.
	 */
	public function get_shipping_phone_session() {
		$shipping_phone = WC()->session->get( '_shipping_phone' );
		return $shipping_phone;
	}

	/**
	 * Save the shipping phone fields values to the current user session.
	 * 
	 * @param array $posted_data Post data for all checkout fields.
	 */
	public function set_shipping_phone_session( $posted_data ) {
		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();

		// Get shipping phone values
		$shipping_phone = $parsed_posted_data['shipping_phone'];

		// Set session value
		WC()->session->set( '_shipping_phone', $shipping_phone );
		
		return $posted_data;
	}

	/**
	 * Unset shipping phone session.
	 **/
	public function unset_shipping_phone_session() {
		WC()->session->set( '_shipping_phone', null );
	}



	/**
	 * Get shipping phone values from session or database.
	 *
	 * @return  array  The current value for the shipping phone field.
	 */
	public function get_current_shipping_phone_value() {
		// Try get the shipping phone from the session
		$shipping_phone_session = $this->get_shipping_phone_session();
		if ( $shipping_phone_session != null ) {
			$shipping_phone = $shipping_phone_session;
		}

		// Try to get shipping phone from the saved customer shipping address
		if ( $shipping_phone == null ) {
			$user_id = $this->get_user_id();
			if ( $user_id > 0 ) {
				$shipping_phone = get_user_meta( $user_id, 'shipping_phone', true );
			}
		}

		return $shipping_phone;
	}



	/**
	 * Change default shipping phone value.
	 */
	public function change_default_shipping_phone_value( $value, $input ) {
		$shipping_phone = $this->get_current_shipping_phone_value();

		// If shipping phone value was not found return unchanged value
		if ( $shipping_phone == null ) {
			$shipping_phone = $value;
		}

		return $shipping_phone;
	}



	/**
	 * Add replacement for shipping phone to address formats localisation.
	 *
	 * @param   array  $formats  Country address formats.
	 */
	public function add_shipping_phone_to_substep_text_format( $html ) {
		$shipping_phone = $this->get_current_shipping_phone_value();

		// Insert the phone field at in the text
		if ( $shipping_phone != null && ! empty( $shipping_phone ) ) {
			$shipping_phone_text = '<span class="wfc-step__substep-text-line">' . $shipping_phone . '</span></div>';
			$html = str_replace( '</div>', $shipping_phone_text, $html );
		}

		return $html;
	}



	/**
	 * Get the checkout fields args.
	 */
	public function get_checkout_field_args() {
		$billing_email_description = WC()->cart->needs_shipping() ? __( 'Order and tracking number will be sent to this email address.', 'woocommerce-fluid-checkout' ) : __( 'Order number and receipt will be sent to this email address.', 'woocommerce-fluid-checkout' );
		$billing_address_2_label = __( 'Appartment, suite, unit, building, floor, etc.', 'woocommerce-fluid-checkout' );
		
		$shipping_phone_description = __( 'For shipping-related purposes only.', 'woocommerce-fluid-checkout' );
		$shipping_address_2_label = __( 'Appartment, suite, unit, building, floor, etc.', 'woocommerce-fluid-checkout' );

		return apply_filters( 'wfc_checkout_field_args', array(
			'billing_email'         => array( 'priority' => 5, 'autocomplete' => 'contact email', 'description' => $billing_email_description ),
			'billing_first_name'    => array( 'priority' => 10, 'autocomplete' => 'contact given-name' ),
			'billing_last_name'     => array( 'priority' => 20, 'autocomplete' => 'contact family-name' ),
			'billing_phone'         => array( 'priority' => 30, 'autocomplete' => 'contact tel', 'class' => array( 'form-row-first' ) ),

			'billing_company'       => array( 'priority' => 100, 'autocomplete' => 'billing organization', 'class' => array( 'form-wide' ) ),
			'billing_address_1'     => array( 'autocomplete' => 'billing address-line1' ),
			'billing_address_2'     => array( 'autocomplete' => 'billing address-line2', 'label' => $billing_address_2_label ),
			'billing_city'          => array( 'autocomplete' => 'billing address-level2' ),
			'billing_state'         => array( 'autocomplete' => 'billing address-level1' ),
			'billing_country'       => array( 'autocomplete' => 'billing country' ),
			'billing_postcode'      => array( 'autocomplete' => 'billing postal-code' ),

			'shipping_first_name'   => array( 'priority' => 10, 'autocomplete' => 'shipping given-name' ),
			'shipping_last_name'    => array( 'priority' => 20, 'autocomplete' => 'shipping family-name' ),
			'shipping_phone'        => array( 'priority' => 30, 'autocomplete' => 'shipping tel', 'class' => array( 'form-row-first' ), 'description' => $shipping_phone_description ),
			'shipping_company'      => array( 'priority' => 35, 'autocomplete' => 'shipping organization', 'class' => array( 'form-row-last' ) ),
			'shipping_address_1'    => array( 'autocomplete' => 'shipping address-line1' ),
			'shipping_address_2'    => array( 'autocomplete' => 'shipping address-line2', 'label' => $shipping_address_2_label ),
			'shipping_city'         => array( 'autocomplete' => 'shipping address-level2', 'class' => array( 'form-row-first' ) ),
			'shipping_state'        => array( 'autocomplete' => 'shipping address-level1', 'class' => array( 'form-row-last' ) ),
			'shipping_country'      => array( 'autocomplete' => 'shipping country' ),
			'shipping_postcode'     => array( 'autocomplete' => 'shipping postal-code', 'class' => array( 'form-row-first' ) ),
		) );
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
		
		// Maybe remove form-row-XX classes
		$form_row_classes = array( 'form-row-first', 'form-row-last', 'form-row-wide' );

		if ( array_intersect( $new_classes, $form_row_classes ) ) {
			$field_classes = array_diff( $field_classes, $form_row_classes );
		}

		$field_classes = array_merge( $field_classes, $new_classes );
		
		return $field_classes;
	}



	/**
	 * Merge checkout field args.
	 *
	 * @param   array  $field_args  Contains checkout field arguments.
	 * @param   array  $new_field_args  New field argument to be merged.
	 *
	 * @return  array  $field_args      Changed field arguments.
	 */
	public function merge_form_field_args( $field_args, $new_field_args ) {

		foreach( $new_field_args as $field_key => $args ) {
			$original_args = array_key_exists( $field_key, $field_args ) ? $field_args[ $field_key ] : array();

			// Merge class args and remove it from $args to avoid conflicts when merging all field args below
			if ( array_key_exists( 'class', $original_args ) && array_key_exists( 'class', $args ) ) {
				$original_args[ 'class' ] = $this->merge_form_field_class_args( $original_args[ 'class' ], $args[ 'class' ] );
				unset( $args[ 'class' ] );
			}

			$field_args[ $field_key ] = array_merge( $original_args, $args );
		}

		return $field_args;
	}

}

FluidCheckout_CheckoutShippingPhoneField::instance();
