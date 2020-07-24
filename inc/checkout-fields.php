<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckout_Fields extends FluidCheckout {

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
		add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_checkout_field_types' ), 5 );

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

		// Checkout field display order
		if ( get_option( 'wfc_apply_checkout_fields_display_order', true ) ) {
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_billing_fields_display_order' ), 10 );
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_shipping_fields_display_order' ), 10 );
		}

		// TODO: Move this to Ziptastic support functions, and change field display order to (country, zip, address 1, address 2, state, city)
		// add_filter( 'woocommerce_default_address_fields' , array( $this, 'ziptastic_change_address_fields_priority' ), 10 );
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
		$fields['shipping_phone']['priority'] = apply_filters( 'wfc_shipping_phone_field_priority', 30 );

		// Also change company field class as phone field pushes it to the right
		if ( array_key_exists( 'shipping_company', $fields ) ) {
			$fields['shipping_company']['class'] = array('form-row-last');
			$fields['shipping_company']['priority'] = apply_filters( 'wfc_shipping_company_field_priority', 40 );
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
	 * Add replaced for shipping phone to address formats localisation.
	 */
	public function add_shipping_phone_to_formats( $formats ) {
		foreach ( $formats as $locale => $format ) {
			$formats[ $locale ] .= "\n{shipping_phone}";
		}
		return $formats;
	}






	/**
	 * Change Address Fields for account address edit form.
	 */
	public function change_checkout_fields_display_order( $fields, $type ) {
		// Add $type prefix separator when needed
		if ( ! empty( $type ) ) { $type .= '_'; }

		$fields_display_order = apply_filters( 'wfc_checkout_fields_display_order', array(
			$type . 'email' => 5,
			$type . 'first_name' => 10,
			$type . 'last_name' => 20,
			$type . 'phone' => 30,
			$type . 'company' => 40,
		) );

		// Set fields priority
		foreach( $fields_display_order as $field => $priority ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ]['priority'] = $priority; }
		}

		return $fields;
	}



	/**
	 * Change billing fields display order.
	 */
	public function change_billing_fields_display_order( $fields ) {
		$type = 'billing';
		$fields[ $type ] = $this->change_checkout_fields_display_order( $fields[ $type ], $type );
		return $fields;
	}



	/**
	 * Change shipping fields display order.
	 */
	public function change_shipping_fields_display_order( $fields ) {
		$type = 'shipping';
		$fields[ $type ] = $this->change_checkout_fields_display_order( $fields[ $type ], $type );
		return $fields;
	}



	/**
	 * Change address default locale fields priority order on the frontend.
	 */
	public function ziptastic_change_address_fields_priority( $fields ) {
		// $type . 'country' => 40,
		// $type . 'postcode' => 45,
		// $type . 'address_1' => 50, 
		// $type . 'address_2' => 60,
		// $type . 'city' => 70,
		// $type . 'state' => 80,
		
		// TODO: change field
		
		return $this->change_checkout_fields_display_order( $fields, null );
	}

}

FluidCheckout_Fields::instance();