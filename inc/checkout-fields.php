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
	 * Initialize hooks.
	 */
	public function hooks() {
		// Checkout fields args
		add_filter( 'woocommerce_billing_fields', array( $this, 'change_checkout_field_args' ), 100 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'change_checkout_field_args' ), 100 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'change_order_field_args' ), 100 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_field_args' ), 100 );

		// Remove `screen-reader-text` from some fields
		add_filter( 'woocommerce_default_address_fields', array( $this, 'remove_screen_reader_class_default_locale_field_args' ), 100 );

		// Add class for fields with description
		add_filter( 'woocommerce_default_address_fields', array( $this, 'add_field_has_description_class_default_locale_field_args' ), 100 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_field_has_description_class_checkout_fields_args' ), 100 );

		// Select2 field class
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_select2_field_class' ), 100, 3 );
	}



	/**
	 * Get the checkout fields args.
	 */
	public function get_checkout_field_args() {
		$needs_shipping = is_checkout() ? WC()->cart->needs_shipping() : true;
		$billing_email_description = $needs_shipping ? __( 'Order and tracking number will be sent to this email address.', 'fluid-checkout' ) : __( 'Order number and receipt will be sent to this email address.', 'fluid-checkout' );
		$billing_company_class = get_option( 'woocommerce_checkout_phone_field', 'required' ) === 'required' ? 'form-row-last' : 'form-row-wide';

		$fields_args = array(
			'billing_email'         => array( 'priority' => 5, 'autocomplete' => 'contact email', 'description' => $billing_email_description, 'type' => 'email' ),

			'billing_first_name'    => array( 'priority' => 10, 'autocomplete' => 'contact given-name' ),
			'billing_last_name'     => array( 'priority' => 20, 'autocomplete' => 'contact family-name' ),
			'billing_phone'         => array( 'priority' => 30, 'autocomplete' => 'contact tel', 'class' => array( 'form-row-first' ), 'type' => 'tel' ),
			'billing_company'       => array( 'priority' => 40, 'autocomplete' => 'billing organization', 'class' => array( $billing_company_class ) ),
			'billing_address_1'     => array( 'autocomplete' => 'billing address-line1' ),
			'billing_address_2'     => array( 'autocomplete' => 'billing address-line2' ),
			'billing_city'          => array( 'autocomplete' => 'billing address-level2' ),
			'billing_state'         => array( 'autocomplete' => 'billing address-level1' ),
			'billing_country'       => array( 'autocomplete' => 'billing country' ),
			'billing_postcode'      => array( 'autocomplete' => 'billing postal-code' ),

			'shipping_first_name'   => array( 'priority' => 10, 'autocomplete' => 'shipping given-name' ),
			'shipping_last_name'    => array( 'priority' => 20, 'autocomplete' => 'shipping family-name' ),
			'shipping_company'      => array( 'priority' => 30, 'autocomplete' => 'shipping organization', 'class' => array( 'form-row-wide' ) ),
			'shipping_address_1'    => array( 'autocomplete' => 'shipping address-line1' ),
			'shipping_address_2'    => array( 'autocomplete' => 'shipping address-line2' ),
			'shipping_city'         => array( 'autocomplete' => 'shipping address-level2' ),
			'shipping_state'        => array( 'autocomplete' => 'shipping address-level1' ),
			'shipping_country'      => array( 'autocomplete' => 'shipping country' ),
			'shipping_postcode'     => array( 'autocomplete' => 'shipping postal-code' ),
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
		$new_field_args = array(
			'address_1' => array( 'class' => array( 'form-row-wide' ), 'description' => __( 'House number and street name.', 'fluid-checkout' ) ),
			'address_2' => array( 'class' => array( 'form-row-wide' ), 'label' => __( 'Apartment, unit, building, floor, etc.', 'fluid-checkout' ), 'placeholder' => __( 'Apartment, unit, building, floor, etc.', 'fluid-checkout' ) ),
		);

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
		$new_field_args = $this->get_checkout_field_args();

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
	 * Add extra class for `select2` fields.
	 *
	 * @param   array   $args   Checkout field args.
	 * @param   string  $key    Field key.
	 * @param   mixed   $value  Field value.
	 *
	 * @return  array           Modified checkout field args.
	 */
	public function add_select2_field_class( $args, $key, $value ) {
		$select2_field_types = apply_filters( 'fc_select2_field_types', array( 'country', 'state', 'select' ) );

		// Bail if field type is not a select2 field
		if ( ! in_array( $args[ 'type' ], $select2_field_types ) ) { return $args; }

		// Initialize class argument if not existing yet
		if ( ! array_key_exists( 'class', $args ) ) { $args[ 'class' ] = array(); }

		// Add extra class
		$args[ 'class' ] = array_merge( $args[ 'class' ], array( 'fc-select2-field' ) );

		return $args;
	}

}

FluidCheckout_CheckoutFields::instance();
