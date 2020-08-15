<?php

/**
 * Address book feature
 */
class FluidCheckout_AddressBook extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->init();
	}
	


	/**
	 * Initialize class.
	 */
	public function init() {
		// Bail if address book not enabled
		if ( ! get_option( 'wfc_enable_address_book', true ) ) { return; }

		// Bail if checkout layout is not multi-step-enhanced
		$active_checkout_layout_key = FluidCheckout_CheckoutLayouts::instance()->get_active_checkout_layout_key();
		if ( $active_checkout_layout_key !== 'multi-step-enhanced' ) { return; }

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Shipping Address Book
		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'output_address_book_wrapper_start_tag' ), 5 );
		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'output_shipping_address_book' ), 6 );
		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'output_address_book_new_address_wrapper_start_tag' ), 7 );
		add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'output_address_book_new_address_wrapper_end_tag' ), 10 );
		add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'output_address_book_wrapper_end_tag' ), 20 );

		// Save address checkboxes
		add_filter( 'woocommerce_checkout_fields' , array( $this, 'add_shipping_save_address_checkbox_field_checkout' ), 100 );
		add_filter( 'woocommerce_checkout_fields' , array( $this, 'add_billing_save_address_checkbox_field_checkout' ), 100 );

		// Save address
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_addresses_from_order' ), 10, 1 );
	}


	/**
	 * Return WooCommerce Fluid Checkout multi-step enhanced class instance
	 */
	public function multistep_enhanced() {
		return FluidCheckoutLayout_MultiStepEnhanced::instance();
	}

	/**
	 * Return WooCommerce Fluid Checkout checkout fields class instance
	 */
	public function checkout_fields() {
		return FluidCheckout_CheckoutFields::instance();
	}





	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		return array_merge( $classes, array( 'has-wfc-address-book' ) );
	}





	/**
	 * Save order addresses to address book
	 */
	public function save_addresses_from_order( $order_id ) {
		// Bail if user not logged in
		if ( ! is_user_logged_in() ) { return; }

		$order = wc_get_order( $order_id );

		// Get addresses from order
		$shipping_address = $order->get_address( 'shipping' );
		$billing_address = $order->get_address( 'billing' );

		// Remove address data to avoid saving unnecessary information
		unset( $shipping_address['email'] );
		unset( $shipping_address['shipping_address_save'] );
		unset( $billing_address['email'] );
		unset( $billing_address['billing_address_save'] );

		// Get address id from hidden field and flag to save field
		$shipping_address_id = ! empty( $_POST['_shipping_address_id'] ) && absint( $_POST['_shipping_address_id'] ) > 0 ? absint( $_POST['_shipping_address_id'] ) : null;
		$billing_address_id = ! empty( $_POST['_billing_address_id'] ) && absint( $_POST['_billing_address_id'] ) > 0 ? absint( $_POST['_billing_address_id'] ) : null;
		$shipping_address_save = $_POST['shipping_address_save'] === '1' ? true : false;
		$billing_address_save = $_POST['billing_address_save'] === '1' ? true : false;

		// Save new address entries
		$address_book_entries = $this->get_user_address_book_entries();
		if ( $shipping_address_save && ! array_key_exists( $shipping_address_id, $address_book_entries ) ) { $this->save_address_book_entry( $shipping_address ); }
		if ( $billing_address_save && ! array_key_exists( $billing_address_id, $address_book_entries ) ) { $this->save_address_book_entry( $billing_address ); }
	}



	/**
	 * Get user's saved addresses
	 */
	public function get_user_address_book_entries( $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );
		
		$address_book_entries = get_user_meta( $user_id, '_wfc_address_book', true );

		// Return empty address book if not valid or inexistent
		if ( ! is_array( $address_book_entries ) ) {
			$address_book_entries = array();
		}

		return $address_book_entries;
	}


	
	/**
	 * Save a new address to users address book
	 */
	public function save_address_book_entry( $address_entry, $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );

		// Get existing entries
		$address_book_entries = $this->get_user_address_book_entries( $user_id );
		
		// Get or create address id
		$address_id = is_array( $address_entry ) && array_key_exists( 'address_id' ) ? $address_entry['address_id'] : null;
		while ( $address_id == null ) {
			$new_address_id = wp_rand( 10000, 99999 );
			
			// Make sure new id doesn't exist for the user saved address entries
			if ( ! array_key_exists( $new_address_id, $address_book_entries ) ) {
				$address_id = $new_address_id;
			}
		}

		// Add or replace address id on address data
		$address_entry[ 'address_id' ] = $address_id;
		
		// Add new entry
		$address_book_entries[ $address_id ] = $address_entry;

		// Save and return saving result
		return update_user_meta( $user_id, '_wfc_address_book', $address_book_entries );
	}





	/**
	 * Get shipping save address checkbox field
	 */
	public function get_shipping_save_address_checkbox_field() {
		return apply_filters( 'wfc_shipping_save_checkout_field', array(
			'label'     => __( 'Save address for future purchase', 'woocommerce-fluid-checkout' ),
			'type'		=> 'checkbox',
			'required'  => false,
			'class'     => array( 'form-row-wide' ),
			'value'		=> '1',
    		'default'	=> 1,
		) );
	}

	/**
	 * Add shipping save address checkbox field to edit address fields.
	 */
	public function add_shipping_save_address_checkbox_field( $fields ) {
		$fields['shipping_address_save'] = $this->get_shipping_save_address_checkbox_field();
		
		$fields_args = $this->checkout_fields()->get_checkout_fields_args( 'shipping' );
		foreach( $fields_args as $field => $values ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ] = array_merge( $fields[ $field ], $values ); }
		}

		return $fields;
	}

	/**
	 * Add shipping save address checkbox field to checkout fields.
	 */
	public function add_shipping_save_address_checkbox_field_checkout( $fields ) {
		$fields['shipping'] = $this->add_shipping_save_address_checkbox_field( $fields['shipping'] );
		return $fields;
	}



	/**
	 * Get billing save address checkbox field
	 */
	public function get_billing_save_address_checkbox_field() {
		return apply_filters( 'wfc_billing_save_checkout_field', array(
			'label'     => __( 'Save address for future purchase', 'woocommerce-fluid-checkout' ),
			'type'		=> 'checkbox',
			'required'  => false,
			'class'     => array( 'form-row-wide' ),
			'value'		=> '1',
    		'default'	=> 1,
		) );
	}

	/**
	 * Add billing save address checkbox field to edit address fields.
	 */
	public function add_billing_save_address_checkbox_field( $fields ) {
		$fields['billing_address_save'] = $this->get_billing_save_address_checkbox_field();
		
		$fields_args = $this->checkout_fields()->get_checkout_fields_args( 'billing' );
		foreach( $fields_args as $field => $values ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ] = array_merge( $fields[ $field ], $values ); }
		}

		return $fields;
	}

	/**
	 * Add billing save address checkbox field to checkout fields.
	 */
	public function add_billing_save_address_checkbox_field_checkout( $fields ) {
		$fields['billing'] = $this->add_billing_save_address_checkbox_field( $fields['billing'] );
		return $fields;
	}



	/**
	 * Output address book entries for shipping step
	 */
	function output_shipping_address_book() {
		$address_book_entries = $this->get_user_address_book_entries();
		
		do_action( 'wfc_shipping_address_book_before_entries' );
	
		wc_get_template( 'checkout/address-book-entries.php', array(
			'address_type'			=> 'shipping',
			'address_book_entries'	=> $address_book_entries,
		) );

		do_action( 'wfc_shipping_address_book_after_entries' );
	}

	/**
	 * Output address book new address form wrapper start tag
	 */
	public function output_address_book_new_address_wrapper_start_tag() {
		echo '<noscript><style type="text/css">.wfc-address-book__form-wrapper{display:block !important;}</style></noscript>';
		echo '<div class="wfc-address-book__form-wrapper">';
	}

	/**
	 * Output address book new address form wrapper end tag
	 */
	public function output_address_book_new_address_wrapper_end_tag() {
		echo '</div>';
	}




	/**
	 * Output address book wrapper start tag
	 */
	public function output_address_book_wrapper_start_tag() {
		echo '<div class="wfc-address-book">';
	}

	/**
	 * Output address book wrapper end tag
	 */
	public function output_address_book_wrapper_end_tag() {
		echo '</div>';
	}

}

FluidCheckout_AddressBook::instance();
