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
		add_filter( 'fc_substep_shipping_address_text', array( $this, 'add_shipping_phone_to_substep_text_format' ), 10 );

		// Change checkout field args
		add_filter( 'fc_checkout_field_args' , array( $this, 'change_shipping_company_field_args' ), 10 );

		// Persist shipping phone to the user's session
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'set_shipping_phone_session' ), 10 );
		add_filter( 'default_checkout_shipping_phone', array( $this, 'change_default_shipping_phone_value' ), 10, 2 );
	}



	/**
	 * Return Checkout Fields class instance.
	 */
	public function checkout_fields() {
		return FluidCheckout_CheckoutFields::instance();
	}



	/**
	 * Get shipping phone field for address forms.
	 *
	 * @return  array $args Arguments for adding shipping phone field.
	 */
	public function get_shipping_phone_field() {
		return apply_filters( 'fc_shipping_phone_field_args', array(
			'label'        => __( 'Shipping phone', 'fluid-checkout' ),
			'description'  => __( 'Only used for shipping-related questions.', 'fluid-checkout' ),
			'required'     => get_option( 'fc_shipping_phone_field_visibility', 'no' ) === 'required',
			'validate'     => array( 'phone' ),
			'class'        => array( 'form-row-first' ),
			'priority'     => 25,
			'autocomplete' => 'shipping tel',
			'clear'        => true
		) );
	}



	/**
	 * Change shipping company field arguments to accomodate the shipping phone field.
	 *
	 * @param   array  $field_args  Contains checkout field arguments.
	 */
	public function change_shipping_company_field_args( $field_args ) {
		// Bail if hidding optional fields behind a link button
		if ( get_option( 'fc_enable_checkout_hide_optional_fields', 'yes' ) === 'yes' && get_option( 'fc_shipping_phone_field_visibility', 'no' ) !== 'required' ) { return $field_args; }

		if ( array_key_exists( 'shipping_company', $field_args ) ) {
			$field_args['shipping_company']['class'] = array( 'form-row-last' );
		}

		return $field_args;
	}



	/**
	 * Add shipping phone field to edit address fields.
	 *
	 * @param   array  $fields  Fields used in checkout.
	 */
	public function add_shipping_phone_field( $fields ) {
		$fields['shipping_phone'] = $this->get_shipping_phone_field();

		$field_args = $this->checkout_fields()->get_checkout_field_args();
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
		$shipping_phone = null;

		// Try get the shipping phone from the session
		$shipping_phone_session = $this->get_shipping_phone_session();
		if ( $shipping_phone_session !== null ) {
			$shipping_phone = $shipping_phone_session;
		}

		// Try to get shipping phone from the saved customer shipping address
		if ( $shipping_phone === null ) {
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
		return $this->get_current_shipping_phone_value();
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
			$shipping_phone_text = '<span class="fc-step__substep-text-line">' . $shipping_phone . '</span></div>';
			$html = str_replace( '</div>', $shipping_phone_text, $html );
		}

		return $html;
	}

}

FluidCheckout_CheckoutShippingPhoneField::instance();
