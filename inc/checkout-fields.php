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
		if ( get_option( 'wfc_apply_checkout_field_types_for_mobile', 'true' ) === 'true' ) {
			add_filter( 'woocommerce_billing_fields' , array( $this, 'change_checkout_field_types' ), 5 );
			add_filter( 'woocommerce_shipping_fields' , array( $this, 'change_checkout_field_types' ), 5 );
		}

		// Checkout fields args
		if ( get_option( 'wfc_apply_checkout_field_args', 'true' ) === 'true' ) {
			add_filter( 'woocommerce_billing_fields' , array( $this, 'change_checkout_field_args' ), 10 );
			add_filter( 'woocommerce_shipping_fields' , array( $this, 'change_checkout_field_args' ), 10 );
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_order_field_args' ), 10 );
		}

		// Shipping Phone Field
		if ( get_option( 'wfc_add_shipping_phone_field', 'true' ) === 'true' ) {
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
		if ( array_key_exists( 'billing_email', $fields ) ) { $fields['billing_email']['type'] = 'email'; }
		if ( array_key_exists( 'billing_phone', $fields ) ) { $fields['billing_phone']['type'] = 'tel'; }
		if ( array_key_exists( 'shipping_phone', $fields ) ) { $fields['shipping_phone']['type'] = 'tel'; }
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

		$field_args = $this->get_checkout_field_args();
		foreach( $field_args as $field => $values ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ] = array_merge( $fields[ $field ], $values ); }
		}

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
	public function get_checkout_field_args() {
		return apply_filters( 'wfc_checkout_field_args', array(
			'billing_email'			=> array( 'priority' => 5 ),
			'billing_first_name'	=> array( 'priority' => 10 ),
			'billing_last_name'		=> array( 'priority' => 20 ),
			'billing_phone'			=> array( 'priority' => 30, 'class' => array( 'form-row-first' ) ),
			'billing_company'		=> array( 'priority' => 35, 'class' => array( 'form-row-last' ) ),

			'billing_postcode' 		=> array( 'autocomplete' => 'billing postal-code' ),
			'billing_country' 		=> array( 'autocomplete' => 'billing country' ),
			'billing_city' 			=> array( 'autocomplete' => 'billing address-level2' ),
			'billing_state' 		=> array( 'autocomplete' => 'billing address-level1' ),
			
			'shipping_first_name'	=> array( 'priority' => 10 ),
			'shipping_last_name'	=> array( 'priority' => 20 ),
			'shipping_phone'		=> array( 'priority' => 30, 'class' => array( 'form-row-first' ) ),
			'shipping_company'		=> array( 'priority' => 35, 'class' => array( 'form-row-last' ) ),
			
			'shipping_postcode' 	=> array( 'autocomplete' => 'shipping postal-code' ),
			'shipping_country' 		=> array( 'autocomplete' => 'shipping country' ),
			'shipping_city' 		=> array( 'autocomplete' => 'shipping address-level2' ),
			'shipping_state' 		=> array( 'autocomplete' => 'shipping address-level1' ),
		) );
	}



	/**
	 * Remove `form-row-XX` classes from field classes to avoid conflicts the merge the new classes into it
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
	 * Merge checkout field args
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



	/**
	 * Change Address Fields for account address edit form.
	 */
	public function change_checkout_field_args( $fields ) {
		$new_field_args = $this->get_checkout_field_args();

		foreach( $fields as $field_key => $original_args ) {
			$new_args = array_key_exists( $field_key, $new_field_args ) ? $new_field_args[ $field_key ] : array();

			// Merge class args and remove it from $new_args to avoid conflicts when merging all field args below
			if ( array_key_exists( 'class', $new_args ) && array_key_exists( 'class', $original_args ) ) {
				$original_args[ 'class' ] = $this->merge_form_field_class_args( $original_args[ 'class' ], $new_args[ 'class' ] );
				unset( $new_args[ 'class' ] );
			}

			$fields[ $field_key ] = array_merge( $original_args, $new_args );
		}

		return $fields;
	}



	/**
	 * Change order fields args.
	 */
	public function change_order_field_args( $fields ) {
		$field_group = 'order';
		$fields[ $field_group ] = $this->change_checkout_field_args( $fields[ $field_group ] );
		return $fields;
	}

}

FluidCheckout_CheckoutFields::instance();
