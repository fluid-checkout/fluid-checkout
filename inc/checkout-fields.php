<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckout_CheckoutFields extends FluidCheckout {

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
		// Checkout field types enhancement for mobile
		if ( get_option( 'wfc_apply_checkout_field_types_for_mobile', true ) ) {
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_checkout_field_types' ), 5 );
		}

		// Checkout fields args
		if ( get_option( 'wfc_apply_checkout_fields_args', true ) ) {
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_billing_fields_args' ), 10 );
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_shipping_fields_args' ), 10 );
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_order_fields_args' ), 10 );
		}

		// Shipping Phone Field
		if ( get_option( 'wfc_add_shipping_phone_field', true ) ) {
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'add_shipping_phone_field_checkout' ), 5 );
			add_filter( 'woocommerce_shipping_fields' , array( $this, 'add_shipping_phone_field' ), 5 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_with_shipping_phone' ), 10, 1 );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'output_shipping_phone_field_admin_screen' ), 1, 1 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'output_order_formatted_shipping_address_with_phone' ), 1, 2 );
			add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_replacement_field_shipping_phone' ), 10, 2 );
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'add_shipping_phone_to_formats' ) );
		}
	}



	/**
	 * Change the types of checkout input fields
	 * to display a more appropriate keyboard on mobile devices.
	 */
	public function change_checkout_field_types( $fields ) {
		$fields['billing']['billing_email']['type'] = 'email';
		$fields['billing']['billing_phone']['type'] = 'tel';
		$fields['billing']['billing_postcode']['type'] = apply_filters( 'wfc_postcode_field_type', 'tel' );
		$fields['shipping']['shipping_postcode']['type'] = apply_filters( 'wfc_postcode_field_type', 'tel' );

		return $fields;
	}



	/**
	 * Get shipping phone field for address forms.
	 */
	public function get_shipping_phone_field() {
		return apply_filters( 'wfc_shipping_phone_field', array(
			'label'     => __( 'Shipping Phone', 'woocommerce-fluid-checkout' ),
			'required'  => false,
			'class'     => array( 'form-row-first' ),
			'clear'     => true
		) );
	}

	/**
	 * Add shipping phone field to edit address fields.
	 */
	public function add_shipping_phone_field( $fields ) {
		$fields['shipping_phone'] = $this->get_shipping_phone_field();
		
		$fields_args = $this->get_checkout_fields_args( 'shipping' );
		foreach( $fields_args as $field => $values ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ] = array_merge( $fields[ $field ], $values ); }
		}

		return $fields;
	}

	/**
	 * Add shipping phone field to checkout fields.
	 */
	public function add_shipping_phone_field_checkout( $fields ) {
		$fields['shipping'] = $this->add_shipping_phone_field( $fields['shipping'] );
		return $fields;
	}



	/**
	 * Update the order meta with gift fields value.
	 **/
	public function update_order_meta_with_shipping_phone( $order_id ) {
		$shipping_phone = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
		update_post_meta( $order_id, '_shipping_phone', $shipping_phone );
	}


	
	/**
	 * Output shipping phone field to admin screen.
	 */
	public function output_shipping_phone_field_admin_screen( $order ) {
		$shipping_phone = get_post_meta( $order->get_id(), '_shipping_phone', true );
		echo '<p><strong>'. __( 'Phone', 'woocommerce-fluid-checkout' ) .':</strong><br><a href="tel:' . $shipping_phone . '">'. $shipping_phone .'</a></p>';
	}



	/**
	 * Output shipping phone to the address details on order view.
	 */
	public function output_order_formatted_shipping_address_with_phone( $address, $order ) {
		$shipping_phone = get_post_meta( $order->get_id(), '_shipping_phone', true );
		if ( ! empty( $shipping_phone ) ) { $address['shipping_phone'] = $shipping_phone; }
		return $address;
	}



	/**
	 * Add replacement for shipping phone.
	 */
	public function add_replacement_field_shipping_phone( $replacements, $address ) {
		$replacements['{shipping_phone}'] = isset( $address['shipping_phone'] ) ? $address['shipping_phone'] : '';
		return $replacements;
	}



	/**
	 * Add replacement for shipping phone to address formats localisation.
	 */
	public function add_shipping_phone_to_formats( $formats ) {
		foreach ( $formats as $locale => $format ) {
			$formats[ $locale ] .= "\n{shipping_phone}";
		}
		return $formats;
	}




	/**
	 * Get the checkout fields args.
	 */
	public function get_checkout_fields_args( $field_group ) {
		// Add prefix separator to field group when needed
		if ( ! empty( $field_group ) ) { $field_group .= '_'; }

		return apply_filters( 'wfc_checkout_fields_args', array(
			$field_group . 'email'			=> array( 'priority' => 5 ),
			$field_group . 'first_name'		=> array( 'priority' => 10 ),
			$field_group . 'last_name'		=> array( 'priority' => 20 ),
			$field_group . 'phone'			=> array( 'priority' => 30, 'class' => array( 'form-row-first' ) ),
			$field_group . 'company'		=> array( 'priority' => 35, 'class' => array( 'form-row-last' ) ),
		) );
	}



	/**
	 * Change Address Fields for account address edit form.
	 */
	public function change_checkout_fields_args( $fields, $field_group ) {
		$fields_args = $this->get_checkout_fields_args( $field_group );

		foreach( $fields_args as $field => $values ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ] = array_merge( $fields[ $field ], $values ); }
		}

		return $fields;
	}



	/**
	 * Change billing fields args.
	 */
	public function change_billing_fields_args( $fields ) {
		$field_group = 'billing';
		$fields[ $field_group ] = $this->change_checkout_fields_args( $fields[ $field_group ], $field_group );
		return $fields;
	}



	/**
	 * Change shipping fields args.
	 */
	public function change_shipping_fields_args( $fields ) {
		$field_group = 'shipping';
		$fields[ $field_group ] = $this->change_checkout_fields_args( $fields[ $field_group ], $field_group );
		return $fields;
	}



	/**
	 * Change order fields args.
	 */
	public function change_order_fields_args( $fields ) {
		$field_group = 'order';
		$fields[ $field_group ] = $this->change_checkout_fields_args( $fields[ $field_group ], $field_group );
		return $fields;
	}

}

FluidCheckout_CheckoutFields::instance();
