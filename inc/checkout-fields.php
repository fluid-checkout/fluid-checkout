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
		// Checkout fields args
		if ( get_option( 'wfc_apply_checkout_field_args', 'true' ) === 'true' ) {
			add_filter( 'woocommerce_billing_fields', array( $this, 'change_checkout_field_args' ), 10 );
			add_filter( 'woocommerce_shipping_fields', array( $this, 'change_checkout_field_args' ), 10 );
			add_filter( 'woocommerce_checkout_fields', array( $this, 'change_order_field_args' ), 10 );
			add_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_field_args' ), 10 );
		}

		// Shipping Phone Field
		if ( get_option( 'wfc_add_shipping_phone_field', 'true' ) === 'true' ) {
			add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_phone_field' ), 5 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta_with_shipping_phone' ), 10, 1 );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'output_shipping_phone_field_admin_screen' ), 1, 1 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'output_order_formatted_shipping_address_with_phone' ), 1, 2 );
			add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_replacement_field_shipping_phone' ), 10, 2 );
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'add_shipping_phone_to_formats' ) );
		}
	}



	/**
	 * Get shipping phone field for address forms.
	 *
	 * @return  array $args Arguments for adding shipping phone field.
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
	 * Output shipping phone field to admin screen.
	 * 
	 * @param   WC_Order  $order  The order object.
	 */
	public function output_shipping_phone_field_admin_screen( $order ) {
		$shipping_phone = get_post_meta( $order->get_id(), '_shipping_phone', true );
		echo '<p><strong>'. __( 'Phone', 'woocommerce-fluid-checkout' ) .':</strong><br><a href="tel:' . $shipping_phone . '">'. $shipping_phone .'</a></p>';
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
	 * Get the checkout fields args.
	 */
	public function get_checkout_field_args() {
		return apply_filters( 'wfc_checkout_field_args', array(
			'billing_email'         => array( 'priority' => 5, 'autocomplete' => 'contact email' ),
			'billing_first_name'    => array( 'priority' => 10, 'autocomplete' => 'contact given-name' ),
			'billing_last_name'     => array( 'priority' => 20, 'autocomplete' => 'contact family-name' ),
			'billing_phone'         => array( 'priority' => 30, 'autocomplete' => 'contact tel', 'class' => array( 'form-row-first' ) ),

			'billing_company'       => array( 'priority' => 100, 'autocomplete' => 'billing organization', 'class' => array( 'form-wide' ) ),
			'billing_address_1'     => array( 'autocomplete' => 'billing address-line1' ),
			'billing_address_2'     => array( 'autocomplete' => 'billing address-line2' ),
			'billing_city'          => array( 'autocomplete' => 'billing address-level2' ),
			'billing_state'         => array( 'autocomplete' => 'billing address-level1' ),
			'billing_country'       => array( 'autocomplete' => 'billing country' ),
			'billing_postcode'      => array( 'autocomplete' => 'billing postal-code' ),
			
			'shipping_first_name'   => array( 'priority' => 10, 'autocomplete' => 'shipping given-name' ),
			'shipping_last_name'    => array( 'priority' => 20, 'autocomplete' => 'shipping family-name' ),
			'shipping_phone'        => array( 'priority' => 30, 'autocomplete' => 'shipping tel', 'class' => array( 'form-row-first' ) ),
			'shipping_company'      => array( 'priority' => 35, 'autocomplete' => 'shipping organization', 'class' => array( 'form-row-last' ) ),
			'shipping_address_1'    => array( 'autocomplete' => 'shipping address-line1' ),
			'shipping_address_2'    => array( 'autocomplete' => 'shipping address-line2' ),
			'shipping_city'         => array( 'autocomplete' => 'shipping address-level2', 'class' => array( 'form-row-first' ) ),
			'shipping_state'        => array( 'autocomplete' => 'shipping address-level1', 'class' => array( 'form-row-last' ) ),
			'shipping_country'      => array( 'autocomplete' => 'shipping country' ),
			'shipping_postcode'     => array( 'autocomplete' => 'shipping postal-code', 'class' => array( 'form-row-first' ) ),
		) );
	}



	/**
	 * Change default locale fields args.
	 * 
	 * @param   array  $fields  Default address fields args.
	 */
	public function change_default_locale_field_args( $fields ) {
		if ( array_key_exists( 'city', $fields ) ) { $fields['city']['class'] = array( 'form-row-first' ); }
		if ( array_key_exists( 'state', $fields ) ) { $fields['state']['class'] = array( 'form-row-last' ); }
		if ( array_key_exists( 'postcode', $fields ) ) { $fields['postcode']['class'] = array( 'form-row-first' ); }
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



	/**
	 * Change Address Fields arguments for account address edit form.
	 *
	 * @param   array  $fields  Fields used in checkout.
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
	 * 
	 * @param   array  $fields  Fields used in checkout.
	 */
	public function change_order_field_args( $fields ) {
		$field_group = 'order';
		$fields[ $field_group ] = $this->change_checkout_field_args( $fields[ $field_group ] );
		return $fields;
	}

}

FluidCheckout_CheckoutFields::instance();
