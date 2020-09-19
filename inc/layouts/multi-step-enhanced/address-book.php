<?php

/**
 * Address book feature
 */
class FluidCheckout_AddressBook extends FluidCheckout {

	private $address_book_entries_per_user = array();


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
		if ( get_option( 'wfc_enable_address_book', 'true' ) !== 'true' ) { return; }

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Address default values
		add_action( 'wp', array( $this, 'maybe_set_address_entry_selected_session_to_default' ), 10 );
		add_action( 'wp', array( $this, 'add_address_field_default_value_hooks' ), 10 );

		// Checkout fields
		add_filter( 'wfc_checkout_fields_args', array( $this, 'change_checkout_fields_args' ), 60 );
		add_filter( 'wfc_checkout_fields_args', array( $this, 'change_checkout_shipping_copy_target_fields_args' ), 70 );

		// Shipping Address Book
		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'output_address_book_shipping_wrapper_start_tag' ), 5 );
		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'output_shipping_address_book' ), 6 );
		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'output_shipping_address_book_new_address_wrapper_start_tag' ), 7 );
		add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'output_shipping_address_book_new_address_wrapper_end_tag' ), 10 );
		add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'output_address_book_wrapper_end_tag' ), 20 );

		// Billing Address Book
		remove_action( 'wfc_checkout_after_step_payment_fields', array( $this->multistep_enhanced(), 'output_billing_fields' ), 20 );
		add_action( 'wfc_checkout_after_step_payment_fields', array( $this, 'output_billing_address_book' ), 20 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_billing_address_book_fragment' ), 10 );

		// Checkbox for saving address
		add_filter( 'woocommerce_checkout_fields' , array( $this, 'add_shipping_save_address_checkbox_field_checkout' ), 100 );
		add_filter( 'woocommerce_checkout_fields' , array( $this, 'add_billing_save_address_checkbox_field_checkout' ), 100 );

		// Do not update total on change for some address fields
		add_filter( 'woocommerce_checkout_fields' , array( $this, 'unset_update_total_on_change_address_fields' ), 100 );

		// Save address to address book
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_addresses_from_order' ), 10, 1 );

		// Persist shipping address selected
		add_action( 'wp_ajax_wfc_set_shipping_address_selected_session', array( $this, 'set_shipping_address_selected_session' ) );
		add_action( 'wp_ajax_nopriv_wfc_set_shipping_address_selected_session', array( $this, 'set_shipping_address_selected_session' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'unset_shipping_address_selected_session' ), 10, 1 );

		// Persist billing address selected
		add_action( 'wp_ajax_wfc_set_billing_address_selected_session', array( $this, 'set_billing_address_selected_session' ) );
		add_action( 'wp_ajax_nopriv_wfc_set_billing_address_selected_session', array( $this, 'set_billing_address_selected_session' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'unset_billing_address_selected_session' ), 10, 1 );

		// Order Review Shipping Info
		if ( get_option( 'wfc_order_review_display_shipping_address', 'true' ) === 'true' ) {
			add_action( 'woocommerce_review_order_before_order_total', array( $this, 'output_order_review_shipping_address' ), 30 );
		}

		// Account pages
		remove_action( 'wfc_edit_account_address_form', array( FluidCheckout_AccountPages::instance(), 'output_default_account_edit_address_content' ), 10, 2 );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'change_edit_address_account_menu_item_label' ), 50, 2 );
		add_filter( 'woocommerce_endpoint_edit-address_title', array( $this, 'change_edit_address_wc_endpoint_title' ), 10, 2 );
		add_action( 'wfc_edit_account_address_form', array( $this, 'output_account_address_book_entries_list_start_tag' ), 10, 2 );
		add_action( 'wfc_edit_account_address_form', array( $this, 'output_account_edit_address_content' ), 20, 2 );
		add_action( 'wfc_edit_account_address_form', array( $this, 'output_account_address_book_entries_list_end_tag' ), 30, 2 );

		// Address Form
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_replace_address_scripts' ), 11 );
		add_filter( 'woocommerce_country_locale_field_selectors', array( $this, 'change_country_locale_field_selectors' ), 10 );
		add_action( 'template_redirect', array( $this, 'maybe_save_address_book_entry' ), 10 );

		// Delete Address
		add_action( 'init', array( $this, 'add_delete_address_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_delete_address_query_var' ), 0 );
		add_action( 'template_redirect', array( $this, 'maybe_delete_address_book_entry' ), 10 );
		
	}



	/**
	 * Return WooCommerce Fluid Checkout multi-step class instance
	 */
	public function multistep() {
		return FluidCheckoutLayout_MultiStep::instance();
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

		$classes[] = 'has-wfc-address-book';
		if ( count( $this->get_saved_user_address_book_entries() ) > 0 ) {
			$classes[] = 'has-wfc-address-book-entries';
		}

		return $classes;
	}





	/**
	 * Change checkout fields args
	 */
	public function change_checkout_fields_args( $field_args ) {

		$field_args = array_merge( $field_args, array(
			'billing_first_name'	=> array( 'custom_attributes' => array( 'data-copy-to-field' => '#shipping_first_name' ) ),
			'billing_last_name'	=> array( 'custom_attributes' => array( 'data-copy-to-field' => '#shipping_last_name' ) ),
			'billing_phone'	=> array( 'custom_attributes' => array( 'data-copy-to-field' => '#shipping_phone' ) ),
		) );

		return $field_args;
	}

	/**
	 * Change checkout shipping fields to prevent to be overwritten with billing fields values copied at frontend
	 */
	public function change_checkout_shipping_copy_target_fields_args( $field_args ) {
		// Bail if saved shipping address is being used
		if ( $this->get_shipping_address_selected_session() === false ) { return $field_args; };

		$field_args = array_merge( $field_args, array(
			'shipping_first_name'	=> array( 'custom_attributes' => array( 'data-field-edited' => '1' ) ),
			'shipping_last_name'	=> array( 'custom_attributes' => array( 'data-field-edited' => '1' ) ),
			'shipping_phone'		=> array( 'custom_attributes' => array( 'data-field-edited' => '1' ) ),
		) );

		return $field_args;
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
		unset( $billing_address['billing_address_same_as'] );

		// Get address id from hidden field and flag to save field
		$shipping_address_id = ! empty( $_POST['shipping_address_id'] ) && absint( $_POST['shipping_address_id'] ) > 0 ? absint( $_POST['shipping_address_id'] ) : null;
		$billing_address_id = ! empty( $_POST['billing_address_id'] ) && absint( $_POST['billing_address_id'] ) > 0 ? absint( $_POST['billing_address_id'] ) : null;
		$shipping_address_save = $_POST['shipping_address_save'] === '1' ? true : false;
		$billing_address_save = $_POST['billing_address_save'] === '1' && $_POST['billing_address_same_as'] != '1' ? true : false;

		// Save new address entries
		$address_book_entries = $this->get_saved_user_address_book_entries();
		if ( $shipping_address_save && ! array_key_exists( $shipping_address_id, $address_book_entries ) ) { $this->add_new_address_book_entry( $shipping_address ); }
		if ( $billing_address_save && ! array_key_exists( $billing_address_id, $address_book_entries ) ) { $this->add_new_address_book_entry( $billing_address ); }
	}



	/**
	 * Get user's saved addresses
	 */
	public function get_saved_user_address_book_entries( $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );

		// Get from cache if available
		if ( array_key_exists( $user_id, $this->address_book_entries_per_user ) ) {
			return $this->address_book_entries_per_user[ $user_id ];
		}
		
		$address_book_entries = get_user_meta( $user_id, '_wfc_address_book', true );

		// Return empty address book if not valid or inexistent
		if ( ! is_array( $address_book_entries ) ) {
			$address_book_entries = array();
		}

		// Add user's address book entries to cache
		$this->address_book_entries_per_user[ $user_id ] = $address_book_entries;

		return $address_book_entries;
	}

	/**
	 * Get user's saved addresses for shipping address book
	 */
	public function get_saved_user_address_book_entries_for_shipping( $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );
		
		$allowed_countries = WC()->countries->get_shipping_countries();
		$address_book_entries = $this->get_saved_user_address_book_entries( $user_id );
		
		foreach ( $address_book_entries as $key => $value ) {
			if( ! is_array( $value ) || ! array_key_exists( 'country', $value ) || ! in_array( $value[ 'country' ], array_keys( $allowed_countries ) ) ) {
				unset( $address_book_entries[ $key ] );
			}
		}
		
		// $this->locale = array_intersect_key( $this->locale, array_merge( $this->get_allowed_countries(), $this->get_shipping_countries() ) );

		return $address_book_entries;
	}

	/**
	 * Get user's saved addresses for billing address book
	 */
	public function get_saved_user_address_book_entries_for_billing( $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );
		
		$allowed_countries = WC()->countries->get_allowed_countries();
		$address_book_entries = $this->get_saved_user_address_book_entries( $user_id );
		
		foreach ( $address_book_entries as $key => $value ) {
			if( ! is_array( $value ) || ! array_key_exists( 'country', $value ) || ! in_array( $value[ 'country' ], array_keys( $allowed_countries ) ) ) {
				unset( $address_book_entries[ $key ] );
			}
		}
		
		// $this->locale = array_intersect_key( $this->locale, array_merge( $this->get_allowed_countries(), $this->get_shipping_countries() ) );

		return $address_book_entries;
	}



	/**
	 * Get an address entry from user's saved addresses
	 */
	public function get_address_book_entry( $address_id, $user_id = null ) {
		// Bail if address id not valid
		if ( ! $address_id ) { return false; }

		$user_id = $this->get_user_id( $user_id );
		$address_book_entries = $this->get_saved_user_address_book_entries( $user_id );
		$address_entry = array_key_exists( $address_id, $address_book_entries ) ? $address_book_entries[ $address_id ] : false;
		
		return $address_entry;
	}



	/**
	 * Get default address entry data for shipping
	 */
	public function get_default_shipping_address_from_saved_entries( $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );
		$address_book_entries = $this->get_saved_user_address_book_entries_for_shipping( $user_id );
		
		// Bail if not addresses saved
		if ( ! $address_book_entries || count( $address_book_entries ) == 0 ) { return false; }

		// Try get default, or get first
		$default_address_entry = false;
		$first = true;
		foreach ( $address_book_entries as $address_id => $entry ) {
			if ( $first ) { $default_address_entry = $entry; }
			if ( $entry['default'] === true ) {
				$default_address_entry = $entry;
				break;
			}
			$first = false;
		}

		return $default_address_entry;
	}

	/**
	 * Get default address entry data for billing
	 */
	public function get_default_billing_address_from_saved_entries( $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );
		$address_book_entries = $this->get_saved_user_address_book_entries_for_billing( $user_id );
		
		// Bail if not addresses saved
		if ( ! $address_book_entries || count( $address_book_entries ) == 0 ) { return false; }

		// Try get default, or get first
		$default_address_entry = false;
		$first = true;
		foreach ( $address_book_entries as $address_id => $entry ) {
			if ( $first ) { $default_address_entry = $entry; }
			if ( $entry['default'] === true ) {
				$default_address_entry = $entry;
				break;
			}
			$first = false;
		}

		return $default_address_entry;
	}


	
	/**
	 * Save a new address book entry for the user
	 */
	public function add_new_address_book_entry( $address_entry, $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );

		$address_book_entries = $this->get_saved_user_address_book_entries( $user_id );
		
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
		
		// TODO: Maybe clear/update cached address book entries after saving

		// Save and return saving result
		return update_user_meta( $user_id, '_wfc_address_book', $address_book_entries );
	}

	/**
	 * Update a saved address book entry for the user
	 */
	public function update_address_book_entry( $address_entry, $user_id = null ) {
		// Bail if address entry doesn't have address_id
		if ( ! array_key_exists( 'address_id', $address_entry ) ) { return false; }
		
		// TODO: Validate address entry fields at this point
		
		$address_book_entries = $this->get_saved_user_address_book_entries( $user_id );

		// Update address entry values
		$address_id = $address_entry[ 'address_id' ];
		$address_book_entries[ $address_id ] = $address_entry;
		
		// TODO: Maybe clear/update cached address book entries after saving

		// Save and return saving result
		return update_user_meta( $user_id, '_wfc_address_book', $address_book_entries );
	}

	/**
	 * Delete a address book entry for the user
	 */
	public function delete_address_book_entry( $address_id, $user_id = null ) {
		// Bail if address_id not proviced
		if ( ! $address_id ) { return false; }
		
		$address_book_entries = $this->get_saved_user_address_book_entries( $user_id );
		unset( $address_book_entries[ $address_id ] );
		
		// TODO: Maybe clear/update cached address book entries after saving

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
			'class'     => array( 'form-row-wide', 'save-address-checkbox-field' ),
			'value'		=> '1',
			'default'	=> 1,
			'priority'	=> 200,
			'custom_attributes' => array(
				'data-address-book-save' => 1,
			),
		) );
	}

	/**
	 * Add shipping save address checkbox field to edit address fields.
	 */
	public function add_shipping_save_address_checkbox_field( $fields ) {
		// Bail if user not logged in
		if ( ! is_user_logged_in() ) { return $fields; }

		$fields['shipping_address_save'] = $this->get_shipping_save_address_checkbox_field();
		
		// TODO: Check if we can add the save checkbox field without the need for checkout fields feature to be enabled
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
			'class'     => array( 'form-row-wide', 'save-address-checkbox-field' ),
			'value'		=> '1',
			'default'	=> 1,
			'priority'	=> 200,
			'custom_attributes' => array(
				'data-address-book-save' => 1,
			),
		) );
	}

	/**
	 * Add billing save address checkbox field to edit address fields.
	 */
	public function add_billing_save_address_checkbox_field( $fields ) {
		// Bail if user not logged in
		if ( ! is_user_logged_in() ) { return $fields; }
		
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
	 * Unset update checkout on change for address fields
	 */
	public function unset_update_total_on_change_address_fields( $fields ) {
		$target_fields = array(
			'shipping' => array( 'shipping_country' ),
			'billing' => array( 'billing_country' ),
		);

		foreach ( $target_fields as $address_type => $target_address_fields ) {
			foreach ( $target_address_fields as $field_key ) {
				$classes = $fields[ $address_type ][ $field_key ]['class'];
				
				foreach ( $classes as $key => $class ) {
					if ( $class == 'update_totals_on_change' ) {
						unset( $fields[ $address_type ][ $field_key ]['class'][ $key ] );
					}
				}
			}
		}

		return $fields;
	}



	/**
	 * Output address book entries for shipping step
	 */
	function output_shipping_address_book() {
		$address_book_entries_shipping = $this->get_saved_user_address_book_entries_for_shipping();
		
		do_action( 'wfc_shipping_address_book_before_entries' );
	
		wc_get_template( 'checkout/address-book-entries-shipping.php', array(
			'address_type'			=> 'shipping',
			'address_book_entries'	=> $address_book_entries_shipping,
			'address_entry_same_as'	=> null,
		) );

		do_action( 'wfc_shipping_address_book_after_entries' );
	}

	/**
	 * Output address book new address form wrapper start tag
	 */
	public function output_shipping_address_book_new_address_wrapper_start_tag() {
		$active_class = $this->get_shipping_address_entry_checked_state( array( 'address_id' => 'new_shipping' ), false ) ? 'active' : '';
		// TODO: Move `noscript` tag to it's own function to have only one tag of this type
		echo '<noscript><style type="text/css">.wfc-address-book__form-wrapper{display:block !important;}</style></noscript>';
		echo '<div class="wfc-address-book__form-wrapper '. $active_class .'">';
	}

	/**
	 * Output address book new address form wrapper end tag
	 */
	public function output_shipping_address_book_new_address_wrapper_end_tag() {
		echo '</div>';
	}




	/**
	 * Output address book entries for billing step
	 */
	function get_billing_address_book_markup() {
		ob_start();
		$this->output_billing_address_book();
		return ob_get_clean();
	}

	/**
	 * Output address book entries for billing step
	 */
	function output_billing_address_book() {
		// Output billing address book only when shipping needed
		if ( WC()->cart->needs_shipping() ) {
			$this->output_address_book_billing_wrapper_start_tag();

			do_action( 'wfc_checkout_before_step_billing_fields' );

			$this->output_billing_address_book_markup();
			$this->output_billing_address_book_new_address_wrapper_start_tag();
			$this->output_billing_fields();
			$this->output_billing_address_book_new_address_wrapper_end_tag();

			do_action( 'wfc_checkout_after_step_billing_fields' );

			$this->output_address_book_wrapper_end_tag();
		}
		// Output only billing form fields without address book when shipping not needed
		else {
			do_action( 'wfc_checkout_before_step_billing_fields' );

			$this->output_billing_fields();

			do_action( 'wfc_checkout_after_step_billing_fields' );
		}
	}

	/**
	 * Get address entry for "same as" address option
	 */
	public function get_same_as_shipping_address_entry_value() {
		$address_book_entries = $this->get_saved_user_address_book_entries_for_billing();
		$address_entry_same_as = $this->get_shipping_address_selected_session();

		// Try to get first available address
		if ( ! $address_entry_same_as && count( $address_book_entries ) > 0 ) {
			$address_entry_same_as = $address_book_entries[ array_keys( $address_book_entries )[0] ];
		}

		// Set flag for "same as" address option
		if ( is_array( $address_entry_same_as ) ) {
			$address_entry_same_as[ 'address_same_as' ] = '1';
		}
		
		// Unset address "same as" entry if country not allowed
		$allowed_countries = WC()->countries->get_allowed_countries();
		if( ! is_array( $address_entry_same_as ) || ! array_key_exists( 'country', $address_entry_same_as ) || ! in_array( $address_entry_same_as[ 'country' ], array_keys( $allowed_countries ) ) ) {
			$address_entry_same_as = null;
		}

		return $address_entry_same_as;
	}
	
	/**
	 * Output address book entries for billing step
	 */
	public function output_billing_address_book_markup() {
		$address_book_entries = $this->get_saved_user_address_book_entries_for_billing();
		$address_entry_same_as = $this->get_same_as_shipping_address_entry_value();
		
		do_action( 'wfc_billing_address_book_before_entries', $address_book_entries, $address_entry_same_as );
	
		wc_get_template( 'checkout/address-book-entries-billing.php', array(
			'address_type'					=> 'billing',
			'address_book_entries'			=> $address_book_entries,
			'address_entry_same_as'			=> $address_entry_same_as,
			'same_as_address_type_label'	=> _x( 'shipping address', '"same as" address type label', 'woocommerce-fluid-checkout' ),
		) );

		do_action( 'wfc_billing_address_book_after_entries', $address_book_entries, $address_entry_same_as );
	}

	/**
	 * Output billing fields except those already added at contact step
	 */
	public function output_billing_fields() {
		wc_get_template(
			'checkout/form-billing.php',
			array(
				'checkout'			=> WC()->checkout(),
				'ignore_fields'		=> $this->multistep_enhanced()->get_contact_step_display_fields(),
			)
		);
	}


	
	/**
	 * Output address book new address form wrapper start tag
	 */
	public function output_billing_address_book_new_address_wrapper_start_tag() {
		$billing_address = $this->get_billing_address_selected_session();
		$is_same_address_selected = ! $billing_address || $billing_address && array_key_exists( 'address_same_as', $billing_address ) && $billing_address['address_same_as'] == '1';
		$active_class = ! $is_same_address_selected && $this->get_billing_address_entry_checked_state( array( 'address_id' => 'new_billing' ), false ) ? 'active' : '';
		echo '<div class="wfc-address-book__form-wrapper '. $active_class .'">';
	}

	/**
	 * Output address book new address form wrapper end tag
	 */
	public function output_billing_address_book_new_address_wrapper_end_tag() {
		echo '</div>';
	}





	/**
	 * Output address book wrapper start tag
	 */
	public function output_address_book_wrapper_start_tag( $address_type ) {
		echo '<div class="wfc-address-book wfc-address-book--'.esc_attr( $address_type ).'" data-address-type="'.esc_attr( $address_type ).'">';
	}

	/**
	 * Output address book wrapper end tag
	 */
	public function output_address_book_wrapper_end_tag() {
		echo '</div>';
	}

	/**
	 * Output address book wrapper start tag for shipping address
	 */
	public function output_address_book_shipping_wrapper_start_tag() {
		$this->output_address_book_wrapper_start_tag( 'shipping' );
	}

	/**
	 * Output address book wrapper start tag for billing address
	 */
	public function output_address_book_billing_wrapper_start_tag() {
		$this->output_address_book_wrapper_start_tag( 'billing' );
	}





	/**
	 * Add billing address book as checkout fragment.
	 */
	function add_checkout_billing_address_book_fragment( $fragments ) {
		$billing_address_book = $this->get_billing_address_book_markup();
		$fragments['.wfc-address-book--billing'] = $billing_address_book;
		return $fragments;
	}





	/**
	 * Add default value hook for address fields
	 */
	public function add_address_field_default_value_hooks() {
		$default_address_fields = WC()->countries->get_default_address_fields();
		$address_types = array( 'shipping', 'billing' );
		
		foreach ( $address_types as $address_type ) {
			foreach ( $default_address_fields as $field_key => $value ) {
				$input = $address_type.'_'.$field_key;
				
				// Add filter for customer address values
				$method_name = 'change_customer_'.$input;
				if ( method_exists( $this, $method_name ) ) {
					add_filter( 'woocommerce_customer_get_' . $input, array( $this, $method_name ), 10, 2 );
				}

				// Add default field value filter
				add_filter( 'default_checkout_' . $input, array( $this, 'change_default_address_field_value' ), 10, 2 );
			}
		}
	}

	/**
	 * Change default address field value
	 */
	public function change_default_address_field_value( $value, $input ) {
		// Bail for some fields
		$ignore_list = apply_filters( 'wfc_default_address_field_ignore_list', array( 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email' ) );
		if ( in_array( $input, $ignore_list ) ) { return $value; }

		// Get address type from field input name
		$address_type = strpos( $input, 'shipping' ) == 0 ? 'shipping' : '';
		$address_type = empty( $address_type ) && strpos( $input, 'billing' ) == 0 ? 'billing' : '';

		// Bail if not address field
		if ( empty( $address_type ) ) { return $value; }

		// Get field value from address book
		$address_field_key = str_replace( $address_type.'_', '', $input );
		$address_data = $this->get_customer_selected_address_data( $address_type );
		$value = $address_data[ $address_field_key ];

		return $value;
	}





	/**
	 * Change customer shipping country value
	 */
	public function change_customer_shipping_country( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'shipping', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'country', $address_data ) ? $address_data['country'] : '';
	}

	/**
	 * Change customer shipping state value
	 */
	public function change_customer_shipping_state( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'shipping', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'state', $address_data ) ? $address_data['state'] : '';
	}

	/**
	 * Change customer shipping postcode value
	 */
	public function change_customer_shipping_postcode( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'shipping', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'postcode', $address_data ) ? $address_data['postcode'] : '';
	}

	/**
	 * Change customer shipping city value
	 */
	public function change_customer_shipping_city( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'shipping', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'city', $address_data ) ? $address_data['city'] : '';
	}

	/**
	 * Change customer shipping address_1 value
	 */
	public function change_customer_shipping_address_1( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'shipping', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'address_1', $address_data ) ? $address_data['address_1'] : '';
	}

	/**
	 * Change customer shipping address_2 value
	 */
	public function change_customer_shipping_address_2( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'shipping', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'address_2', $address_data ) ? $address_data['address_2'] : '';
	}



	/**
	 * Change customer billing country value
	 */
	public function change_customer_billing_country( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'billing', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'country', $address_data ) ? $address_data['country'] : '';
	}

	/**
	 * Change customer billing state value
	 */
	public function change_customer_billing_state( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'billing', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'state', $address_data ) ? $address_data['state'] : '';
	}

	/**
	 * Change customer billing postcode value
	 */
	public function change_customer_billing_postcode( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'billing', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'postcode', $address_data ) ? $address_data['postcode'] : '';
	}

	/**
	 * Change customer billing city value
	 */
	public function change_customer_billing_city( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'billing', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'city', $address_data ) ? $address_data['city'] : '';
	}

	/**
	 * Change customer billing address_1 value
	 */
	public function change_customer_billing_address_1( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'billing', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'address_1', $address_data ) ? $address_data['address_1'] : '';
	}

	/**
	 * Change customer billing address_2 value
	 */
	public function change_customer_billing_address_2( $value, $customer ) {
		$address_data = $this->get_customer_selected_address_data( 'billing', $customer->get_id() );
		if ( ! is_array( $address_data ) || empty( $address_data ) ) { return $value; }
		return array_key_exists( 'address_2', $address_data ) ? $address_data['address_2'] : '';
	}





	/**
	 * Get the customer's address data from address book or session
	 */
	public function get_customer_selected_address_data( $address_type, $customer_id = null ) {
		$customer_id = $this->get_user_id( $customer_id );
		
		$address_book_entries = $this->get_saved_user_address_book_entries( $customer_id );

		// Get address data from session or first of the list
		$address_data = $address_book_entries[ array_keys( $address_book_entries )[0] ];
		$address_data_session = $this->{'get_'.$address_type.'_address_selected_session'}();
		
		if ( $address_data_session !== false && is_array( $address_data_session ) && array_key_exists( 'address_id', $address_data_session ) ) {
			$address_data = $address_data_session;
		}
		elseif ( $address_type == 'billing' && ! $address_data_session ) {
			$address_data_shipping = $this->get_customer_selected_address_data( 'shipping', $customer_id );
			$address_data_shipping['address_same_as'] = '1';
			$address_data = $address_data_shipping;
		}

		return $address_data;
	}



	/**
	 * Get address entry display label for account pages
	 */
	public function get_account_address_entry_display_label( $address_entry ) {
		$state_label = ! empty( $address_entry['state'] ) ? ', '.$address_entry['state'] : '';
		$country_label = WC()->countries->countries[ $address_entry['country'] ];

		$display_label = sprintf( '%1$s %2$s %3$s %4$s %5$s %6$s',
			array_key_exists( 'first_name', $address_entry ) ? '<span class="address-book-entry__name">'.$address_entry['first_name'] . ' ' . $address_entry['last_name'].'</span>' : '',
			'<span class="address-book-entry__address_1">'.$address_entry['address_1'].'</span>',
			array_key_exists( 'address_2', $address_entry ) ? '<span class="address-book-entry__address_2">'.$address_entry['address_2'].'</span>' : '',
			'<span class="address-book-entry__location">'.$address_entry['city'].$state_label.' '.$address_entry['postcode'].'</span>',
			'<span class="address-book-entry__country">'.$country_label.'</span>',
			array_key_exists( 'company', $address_entry ) ? '<span class="address-book-entry__company">'.$address_entry['company'].'</span>' : ''
		);

		return $display_label;
	}



	/**
	 * Get address entry display label
	 */
	public function get_shipping_address_entry_display_label( $address_entry ) {
		$state_label = ! empty( $address_entry['state'] ) ? ', '.$address_entry['state'] : '';
		$country_label = WC()->countries->countries[ $address_entry['country'] ];

		$display_label = sprintf( '%1$s %2$s %3$s %4$s %5$s %6$s',
			array_key_exists( 'first_name', $address_entry ) ? '<span class="address-book-entry__name">'.$address_entry['first_name'] . ' ' . $address_entry['last_name'].'</span>' : '',
			'<span class="address-book-entry__address_1">'.$address_entry['address_1'].'</span>',
			array_key_exists( 'address_2', $address_entry ) ? '<span class="address-book-entry__address_2">'.$address_entry['address_2'].'</span>' : '',
			'<span class="address-book-entry__location">'.$address_entry['city'].$state_label.' '.$address_entry['postcode'].'</span>',
			'<span class="address-book-entry__country">'.$country_label.'</span>',
			array_key_exists( 'company', $address_entry ) ? '<span class="address-book-entry__company">'.$address_entry['company'].'</span>' : ''
		);

		return $display_label;
	}



	/**
	 * Get address entry display label for billing address
	 */
	public function get_billing_address_entry_display_label( $address_entry ) {
		$state_label = ! empty( $address_entry['state'] ) ? ', '.$address_entry['state'] : '';
		$country_label = WC()->countries->countries[ $address_entry['country'] ];

		$display_label = sprintf( '%1$s %2$s %3$s %4$s',
			'<span class="address-book-entry__address_1">'.$address_entry['address_1'].'</span>',
			array_key_exists( 'address_2', $address_entry ) ? '<span class="address-book-entry__address_2">'.$address_entry['address_2'].'</span>' : '',
			'<span class="address-book-entry__location">'.$address_entry['city'].$state_label.' '.$address_entry['postcode'].'</span>',
			'<span class="address-book-entry__country">'.$country_label.'</span>'
		);

		return $display_label;
	}



	/**
	 * Get address entry checked state
	 */
	public function get_address_entry_checked_state( $address_type, $address_entry, $first = false ) {
		$checked_address = false;

		$address_data_session = $this->{'get_'.$address_type.'_address_selected_session'}();
		$address_id_session = $address_data_session && array_key_exists( 'address_id', $address_data_session ) ? $address_data_session['address_id'] : null;
		$address_same_as_session = $address_data_session && array_key_exists( 'address_same_as', $address_data_session ) && $address_data_session['address_same_as'] == 1;

		// Same address was checked and is new address option
		if ( $address_same_as_session && $address_id_session == 'new_shipping' && $address_entry['address_id'] == $address_id_session ) {
			$checked_address = true;
		}
		// Billing same as shipping
		elseif ( $address_type == 'billing' && ! $address_data_session && is_array( $address_entry ) && array_key_exists( 'address_same_as', $address_entry ) && $address_entry['address_same_as'] == 1 ) {
			$checked_address = true;
		}
		// Address id matches
		elseif ( $address_id_session != null && $address_entry['address_id'] == $address_id_session ) {
			$checked_address = true;
		}
		// Is default address
		elseif ( is_array( $address_entry ) && array_key_exists( 'default', $address_entry ) ) {
			$checked_address = $address_entry['default'] === true;
		}
		// Is first address in the list
		elseif( $address_id_session == null || empty( $address_id_session ) || ( $address_type == 'billing' && ! $address_data_session ) ) {
			$checked_address = $first === true;
		}

		return $checked_address;
	}



	/**
	 * Set shipping address selected on session
	 */
	public function set_shipping_address_selected_session_value( $address_data ) {
		// Bail if address data invalid
		if ( ! $address_data || ! is_array( $address_data ) ) { return; }
		
		// Set session value
		WC()->session->set( 'wfc_shipping_address_selected', $address_data );

		// Set billing same as shipping
		$billing_address = $this->get_billing_address_selected_session();
		if ( ! $billing_address || ( array_key_exists( 'address_same_as', $billing_address ) && $billing_address['address_same_as'] == '1' ) ) {
			$allowed_countries = WC()->countries->get_allowed_countries();
			if ( is_array( $address_data ) && array_key_exists( 'country', $address_data ) && in_array( $address_data[ 'country' ], array_keys( $allowed_countries ) ) ) {
				$address_data[ 'address_same_as' ] = '1';
				WC()->session->set( 'wfc_billing_address_selected', $address_data );
			}
		}
	}

	/**
	 * Set billing address selected on session
	 */
	public function set_billing_address_selected_session_value( $address_data ) {
		// Bail if address data invalid
		if ( ! $address_data || ! is_array( $address_data ) ) { return; }
		
		// Set session value
		WC()->session->set( 'wfc_billing_address_selected', $address_data );
	}

	/**
	 * Set shipping address selected on session, from address book default values.
	 */
	public function maybe_set_address_entry_selected_session_to_default() {
		// Bail if not cart or checkout pages
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() ) ) { return; }

		// Maybe set session shipping address value to default
		if ( ! $this->get_shipping_address_selected_session() && count( $this->get_saved_user_address_book_entries_for_shipping() ) > 0 ) {
			$address_data = $this->get_default_shipping_address_from_saved_entries();
			$this->set_shipping_address_selected_session_value( $address_data );
		}

		// Maybe set session billing address value to default
		// Defaults to the shipping address if the same address is available for billing
		// An address might not be available for both shipping and billing
		// depending on the allowed countries defined at WooCommerce > Settings
		if ( ! $this->get_billing_address_selected_session() && count( $this->get_saved_user_address_book_entries_for_billing() ) > 0 ) {
			$address_data = $this->get_default_billing_address_from_saved_entries();
			$this->set_billing_address_selected_session_value( $address_data );
		}
	}

	/**
	 * Set shipping address selected on session.
	 */
	public function set_shipping_address_selected_session() {
		if ( isset( $_POST['address_data'] ) && is_array( $_POST['address_data'] ) ) {
			// Get sanitized address data from post request values
			$address_data = $_POST['address_data'];
			foreach ( array_keys( $address_data ) as $key ) {
				$address_data[ $key ] = sanitize_text_field( $address_data[ $key ] );
			}

			$this->set_shipping_address_selected_session_value( $address_data );
		}
		else {
			// Clear session value
			$this->unset_shipping_address_selected_session();
		}
	}

	/**
	 * Get shipping address selected value from session.
	 **/
	public function get_shipping_address_selected_session() {
		$address_data = WC()->session->get( 'wfc_shipping_address_selected' );
		return $address_data != null ? $address_data : false;
	}

	/**
	 * Unset shipping address selected session.
	 **/
	public function unset_shipping_address_selected_session() {
		WC()->session->set( 'wfc_shipping_address_selected', null );
	}

	/**
	 * Get shipping address entry checked state
	 */
	public function get_shipping_address_entry_checked_state( $address_entry, $first = false ) {
		return $this->get_address_entry_checked_state( 'shipping', $address_entry, $first );
	}


	


	/**
	 * Set billing address selected on session.
	 */
	public function set_billing_address_selected_session() {
		if ( isset( $_POST['address_data'] ) && is_array( $_POST['address_data'] ) ) {
			// Get sanitized address data
			$address_data = $_POST['address_data'];
			foreach ( array_keys( $address_data ) as $key ) {
				$address_data[ $key ] = sanitize_text_field( $address_data[ $key ] );
			}

			// Set session value
			$this->set_billing_address_selected_session_value( $address_data );
		}
		else {
			// Clear session value
			$this->unset_billing_address_selected_session();
		}
	}

	/**
	 * Get billing address selected value from session.
	 **/
	public function get_billing_address_selected_session() {
		$address_data = WC()->session->get( 'wfc_billing_address_selected' );
		return $address_data != null ? $address_data : false;
	}

	/**
	 * Unset billing address selected session.
	 **/
	public function unset_billing_address_selected_session() {
		WC()->session->set( 'wfc_billing_address_selected', null );
	}

	/**
	 * Get billing address entry checked state
	 */
	public function get_billing_address_entry_checked_state( $address_entry, $first = false ) {
		return $this->get_address_entry_checked_state( 'billing', $address_entry, $first );
	}





	/**
	 * Output shipping address for order review section
	 */
	public function output_order_review_shipping_address() {
		wc_get_template(
			'checkout/review-order-shipping-address.php',
			array(
				'checkout'			=> WC()->checkout(),
				'shipping_address'	=> $this->get_shipping_address_selected_session(),
			)
		);
	}





	/**
	 * Change the label for edit address account menu item
	 */
	public function change_edit_address_account_menu_item_label( $items, $endpoints ) {
		if ( array_key_exists( 'edit-address', $items ) ) { $items[ 'edit-address' ] = __( 'Address book', 'woocommerce-fluid-checkout' ); }
		return $items;
	}

	/**
	 * Change the label edit address endpoint title
	 */
	public function change_edit_address_wc_endpoint_title( $title, $endpoint ) {
		$title = __( 'Address book', 'woocommerce-fluid-checkout' );
		return $title;
	}



	/**
	 * Output address book list start tag for account pages
	 */
	public function output_account_address_book_entries_list_start_tag( $load_address, $address ) {
		echo sprintf( '<div class="%s">', empty( $load_address ) ? 'wfc-address-book__entries' : 'wfc-address-book__entry-form' );
	}

	/**
	 * Output address book list end tag for account pages
	 */
	public function output_account_address_book_entries_list_end_tag( $load_address, $address ) {
		echo '</div>';
	}

	/**
	 * Output the address book content
	 */
	public function output_account_edit_address_content( $load_address, $address ) {
		$address_book_entries = $this->get_saved_user_address_book_entries();

		// Get address entry to edit
		$address_entry = $this->get_address_book_entry( $load_address );
		$address = WC()->countries->get_address_fields( wc_clean( wp_unslash( $address_entry[ 'country' ] ) ), '' );

		// Transpose address entry values to address fiels
		if ( $address_entry !== false ) {
			foreach ( $address as $key => $field ) {
				if ( array_key_exists( $key, $address_entry ) ) {
					$address[ $key ][ 'value' ] = $address_entry[ $key ];
				}
			}
		}
	
		wc_get_template( 'myaccount/address-book-entries.php', array(
			'address_book_entries'	=> $address_book_entries,
			'address_id' => $load_address,
			'address' => $address,
		) );
	}



	/**
	 * Replace WooCommerce address related scripts with modified version targeting field ids without address type prefix
	 */
	public function enqueue_replace_address_scripts() {
		wp_deregister_script( 'wc-country-select' );
		wp_deregister_script( 'wc-address-i18n' );
		wp_register_script( 'wc-country-select', self::$directory_url . 'js/country-select'. self::$asset_version . '.js', array( 'jquery' ), NULL, true );
		wp_register_script( 'wc-address-i18n', self::$directory_url . 'js/address-i18n'. self::$asset_version . '.js', array( 'jquery', 'wc-country-select' ), NULL, true );
	}

	/**
	 * Change selectors for address locale fields
	 */
	public function change_country_locale_field_selectors( $locale_fields ) {
		$locale_fields = array(
			'address_1' => '#address_1_field, #billing_address_1_field, #shipping_address_1_field',
			'address_2' => '#address_2_field, #billing_address_2_field, #shipping_address_2_field',
			'state'     => '#state_field, #billing_state_field, #shipping_state_field, #calc_shipping_state_field',
			'postcode'  => '#postcode_field, #billing_postcode_field, #shipping_postcode_field, #calc_shipping_postcode_field',
			'city'      => '#city_field, #billing_city_field, #shipping_city_field, #calc_shipping_city_field',
		);

		return $locale_fields;
	}

	
	
	/**
	 * Maybe process and save and address book entry edit or add form submission
	 */
	public function maybe_save_address_book_entry() {
		global $wp;

		$nonce_value = wc_get_var( $_REQUEST['woocommerce-edit-address-book-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-edit_address_book' ) ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'edit_address_book' !== $_POST['action'] ) {
			return;
		}

		wc_nocache_headers();

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$address_id = isset( $wp->query_vars['edit-address'] ) ? wc_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true ) : 'new';
		
		if ( ! isset( $_POST[ 'country' ] ) ) {
			return;
		}
		
		$address = WC()->countries->get_address_fields( wc_clean( wp_unslash( $_POST[ 'country' ] ) ), '' );
		$address_entry = array();

		foreach ( $address as $key => $field ) {
			if ( ! isset( $field['type'] ) ) {
				$field['type'] = 'text';
			}

			// Get Value.
			if ( 'checkbox' === $field['type'] ) {
				$value = (int) isset( $_POST[ $key ] );
			} else {
				$value = isset( $_POST[ $key ] ) ? wc_clean( wp_unslash( $_POST[ $key ] ) ) : '';
			}

			// Hook to allow modification of value.
			$value = apply_filters( 'wfc_process_address_book_entry_field_' . $key, $value );

			// Validation: Required fields.
			if ( ! empty( $field['required'] ) && empty( $value ) ) {
				/* translators: %s: Field name. */
				wc_add_notice( sprintf( __( '%s is a required field.', 'woocommerce' ), $field['label'] ), 'error', array( 'id' => $key ) );
			}

			if ( ! empty( $value ) ) {
				// Validation and formatting rules.
				if ( ! empty( $field['validate'] ) && is_array( $field['validate'] ) ) {
					foreach ( $field['validate'] as $rule ) {
						switch ( $rule ) {
							case 'postcode':
								$country = wc_clean( wp_unslash( $_POST[ 'country' ] ) );
								$value   = wc_format_postcode( $value, $country );

								if ( '' !== $value && ! WC_Validation::is_postcode( $value, $country ) ) {
									switch ( $country ) {
										case 'IE':
											$postcode_validation_notice = __( 'Please enter a valid Eircode.', 'woocommerce' );
											break;
										default:
											$postcode_validation_notice = __( 'Please enter a valid postcode / ZIP.', 'woocommerce' );
									}
									wc_add_notice( $postcode_validation_notice, 'error' );
								}
								break;
							case 'phone':
								if ( '' !== $value && ! WC_Validation::is_phone( $value ) ) {
									/* translators: %s: Phone number. */
									wc_add_notice( sprintf( __( '%s is not a valid phone number.', 'woocommerce' ), '<strong>' . $field['label'] . '</strong>' ), 'error' );
								}
								break;
						}
					}
				}
			}

			// Add field value to address book entry
			$address_entry[ $key ] = $value;
		}

		// TODO: Maybe add action hook similar to `woocommerce_after_save_address_validation`

		if ( 0 < wc_notice_count( 'error' ) ) {
			return;
		}

		// Save new entry
		if ( $address_id === 'new' ) {
			$this->add_new_address_book_entry( $address_entry, $user_id );
		}
		// Update existing entry
		else {
			$address_entry[ 'address_id' ] = $address_id;
			$this->update_address_book_entry( $address_entry, $user_id );
		}

		wc_add_notice( __( 'Address saved successfully.', 'woocommerce-fluid-checkout' ) );

		// TODO: Maybe add action hook similar to `woocommerce_customer_save_address`

		wp_safe_redirect( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}



	/**
	 * Maybe delete address book entry
	 */
	public function maybe_delete_address_book_entry() {
		global $wp;

		$nonce_value = wc_get_var( $_REQUEST['wfc-delete-address-book-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		// Bail if delete address nonce was not provided
		if ( ! $nonce_value ) { return; }

		// Redirect to address list if nonce is invalid
		if ( ! wp_verify_nonce( $nonce_value, 'wfc-delete_address_book' ) ) {
			wp_safe_redirect( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}

		wc_nocache_headers();

		$user_id = get_current_user_id();

		// Bail if user not valid
		if ( $user_id <= 0 ) { return; }

		$address_id = isset( $wp->query_vars['delete-address'] ) ? wc_edit_address_i18n( sanitize_title( $wp->query_vars['delete-address'] ), true ) : false;

		// Bail if address id not provided
		if ( $address_id === false ) { return; }

		$this->delete_address_book_entry( $address_id, $user_id );

		wc_add_notice( __( 'Address deleted successfully.', 'woocommerce-fluid-checkout' ) );

		wp_safe_redirect( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}


	/**
	 * Register delete-address endpoint to use inside My Account page.
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function add_delete_address_endpoint() {
		add_rewrite_endpoint( 'delete-address', EP_ROOT | EP_PAGES );
	}

	public function add_delete_address_query_var( $vars ) {
		$vars[] = 'delete-address';
		return $vars;
	}

}

FluidCheckout_AddressBook::instance();
