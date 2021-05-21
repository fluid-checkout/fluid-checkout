<?php

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
	 * Initialize hooks.
	 */
	public function hooks() {
		// Checkout fields args
		add_filter( 'woocommerce_billing_fields', array( $this, 'change_checkout_field_args' ), 10 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'change_checkout_field_args' ), 10 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'change_order_field_args' ), 10 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_field_args' ), 10 );
	}



	/**
	 * Get the checkout fields args.
	 */
	public function get_checkout_field_args() {
		$billing_email_description = WC()->cart->needs_shipping() ? __( 'Order and tracking number will be sent to this email address.', 'woocommerce-fluid-checkout' ) : __( 'Order number and receipt will be sent to this email address.', 'woocommerce-fluid-checkout' );
		$billing_address_2_label = __( 'Appartment, suite, unit, building, floor, etc.', 'woocommerce-fluid-checkout' );
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
			'shipping_company'      => array( 'priority' => 30, 'autocomplete' => 'shipping organization', 'class' => array( 'form-row-first' ) ),
			'shipping_address_1'    => array( 'autocomplete' => 'shipping address-line1' ),
			'shipping_address_2'    => array( 'autocomplete' => 'shipping address-line2', 'label' => $shipping_address_2_label ),
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
