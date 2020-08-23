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

		// Address default values
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
		remove_action( 'wfc_checkout_before_step_billing_fields', array( $this->multistep(), 'output_billing_step_section_title' ), 10 );
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
		if ( $shipping_address_save && ! array_key_exists( $shipping_address_id, $address_book_entries ) ) { $this->save_address_book_entry( $shipping_address ); }
		if ( $billing_address_save && ! array_key_exists( $billing_address_id, $address_book_entries ) ) { $this->save_address_book_entry( $billing_address ); }
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
	 * Save a new address to users address book
	 */
	public function save_address_book_entry( $address_entry, $user_id = null ) {
		$user_id = $this->get_user_id( $user_id );

		// Get existing entries
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
		$address_book_entries = $this->get_saved_user_address_book_entries();
		
		do_action( 'wfc_shipping_address_book_before_entries' );
	
		wc_get_template( 'checkout/address-book-entries-shipping.php', array(
			'address_type'			=> 'shipping',
			'address_book_entries'	=> $address_book_entries,
			'address_entry_same_as'	=> null,
		) );

		do_action( 'wfc_shipping_address_book_after_entries' );
	}

	/**
	 * Output address book new address form wrapper start tag
	 */
	public function output_shipping_address_book_new_address_wrapper_start_tag() {
		$active_class = $this->get_shipping_address_entry_checked_state( array( 'address_id' => 'new' ), false ) ? 'active' : '';
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
		$this->output_address_book_billing_wrapper_start_tag();
		$this->multistep()->output_billing_step_section_title();
		$this->output_billing_address_book_markup();
		$this->output_billing_address_book_new_address_wrapper_start_tag();
		$this->multistep_enhanced()->output_billing_fields();
		$this->output_billing_address_book_new_address_wrapper_end_tag();
		$this->output_address_book_wrapper_end_tag();
	}
	
	/**
	 * Output address book entries for billing step
	 */
	public function output_billing_address_book_markup() {
		$address_book_entries = $this->get_saved_user_address_book_entries();
		$address_entry_same_as = $this->get_shipping_address_selected_session();
		if ( ! $address_entry_same_as && count( $address_book_entries ) > 0 ) { $address_entry_same_as = $address_book_entries[ array_keys( $address_book_entries )[0] ]; }
		$address_entry_same_as[ 'address_same_as' ] = '1';
		
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
	 * Output address book new address form wrapper start tag
	 */
	public function output_billing_address_book_new_address_wrapper_start_tag() {
		$billing_address = $this->get_billing_address_selected_session();
		$is_same_address_selected = ! $billing_address || $billing_address && array_key_exists( 'address_same_as', $billing_address ) && $billing_address['address_same_as'] == '1';
		$active_class = ! $is_same_address_selected && $this->get_billing_address_entry_checked_state( array( 'address_id' => 'new' ), false ) ? 'active' : '';
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
		
		// Bail if user doesn't have saved addresses
		if ( ! $address_book_entries || count( $address_book_entries ) <= 0 ) { return $default_location; }

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
	 * Get address entry display label
	 */
	public function get_shipping_address_entry_display_label( $address_entry ) {
		$display_label = sprintf( '%1$s %2$s %3$s %4$s %5$s %6$s',
			array_key_exists( 'first_name', $address_entry ) ? '<span class="address-book-entry__name">'.$address_entry['first_name'] . ' ' . $address_entry['last_name'].'</span>' : '',
			'<span class="address-book-entry__address_1">'.$address_entry['address_1'].'</span>',
			array_key_exists( 'address_2', $address_entry ) ? '<span class="address-book-entry__address_2">'.$address_entry['address_2'].'</span>' : '',
			'<span class="address-book-entry__location">'.$address_entry['city'].' '.$address_entry['state'].' '.$address_entry['country'].'</span>',
			'<span class="address-book-entry__postcode">'.$address_entry['postcode'].'</span>',
			array_key_exists( 'company', $address_entry ) ? '<span class="address-book-entry__company">'.$address_entry['company'].'</span>' : ''
		);

		return $display_label;
	}



	/**
	 * Get address entry display label for billing address
	 */
	public function get_billing_address_entry_display_label( $address_entry ) {
		$display_label = sprintf( '%1$s %2$s %3$s %4$s',
			'<span class="address-book-entry__address_1">'.$address_entry['address_1'].'</span>',
			array_key_exists( 'address_2', $address_entry ) ? '<span class="address-book-entry__address_2">'.$address_entry['address_2'].'</span>' : '',
			'<span class="address-book-entry__location">'.$address_entry['city'].' '.$address_entry['state'].' '.$address_entry['country'].'</span>',
			'<span class="address-book-entry__postcode">'.$address_entry['postcode'].'</span>'
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
		if ( $address_same_as_session && $address_id_session == 'new' && $address_entry['address_id'] == $address_id_session ) {
			$checked_address = true;
		}
		// Billing same as shipping
		elseif ( $address_type == 'billing' && ! $address_data_session && array_key_exists( 'address_same_as', $address_entry ) && $address_entry['address_same_as'] == 1 ) {
			$checked_address = true;
		}
		// Address id matches
		elseif ( $address_id_session != null && $address_entry['address_id'] == $address_id_session ) {
			$checked_address = true;
		}
		// Is default address
		// TODO: Check with how default address is saved and retrieved
		elseif ( array_key_exists( 'default', $address_entry ) ) {
			$checked_address = $address_entry['default'] === true;
		}
		// Is first address in the list
		elseif( $address_id_session == null || empty( $address_id_session ) ) {
			$checked_address = $first === true;
		}

		return $checked_address;
	}




	/**
	 * Set shipping address selected on session.
	 */
	public function set_shipping_address_selected_session() {
		if ( isset( $_POST['address_data'] ) && is_array( $_POST['address_data'] ) ) {
			// Get sanitized address data
			$address_data = $_POST['address_data'];
			foreach ( array_keys( $address_data ) as $key ) {
				$address_data[ $key ] = sanitize_text_field( $address_data[ $key ] );
			}

			// Set session value
			WC()->session->set( 'wfc_shipping_address_selected', $address_data );

			// Set billing same as shipping
			$billing_address = $this->get_billing_address_selected_session();
			if ( ! $billing_address || ( array_key_exists( 'address_same_as', $billing_address ) && $billing_address['address_same_as'] == '1' ) ) {
				$address_data[ 'address_same_as' ] = '1';
				WC()->session->set( 'wfc_billing_address_selected', $address_data );
			}

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
			WC()->session->set( 'wfc_billing_address_selected', $address_data );
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

}

FluidCheckout_AddressBook::instance();
