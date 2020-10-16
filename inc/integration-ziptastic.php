<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckout_IntegrationZiptastic extends FluidCheckout {

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
		// Bail if ziptastic integration not enabled
		if ( get_option( 'wfc_enable_integration_ziptastic', 'false' ) !== 'true' || empty( get_option( 'wfc_integration_ziptastic_api_key' ) ) ) { return; }

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_ziptastic_scripts' ) );
		add_filter( 'woocommerce_default_address_fields' , array( $this, 'add_ziptastic_custom_attributes' ), 20 );
		
		// TODO: Fix order and size of fields when ziptastic is enabled
		// add_filter( 'wfc_checkout_field_args', array( $this, 'change_address_field_args' ), 100 );
		// add_filter( 'woocommerce_default_address_fields' , array( $this, 'ziptastic_change_address_fields_priority' ), 20 );
		// add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_address_fields_display_class' ), 20 );
	}



	/**
	 * Enqueue integration scripts and styles
	 */
	public function enqueue_ziptastic_scripts() {
		global $wp_query;

		// Bail if address form not present
		if( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! ( is_account_page() && isset( $wp_query->query_vars['edit-address'] ) ) ) ){ return; }
		
		wp_localize_script( 
			'wfc-bundles',
			'wfcZiptasticVars',
			array (
				'ziptasticAPIKey'  => get_option( 'wfc_integration_ziptastic_api_key' ),
				'minChars' => 5,
			)
		);
	}



	/**
	 * Add data-ziptastic attribute to postcode
	 */
	public function add_ziptastic_custom_attributes( $fields ) {
		if ( array_key_exists( 'postcode', $fields ) ) { $fields['postcode']['custom_attributes'] = array( 'data-ziptastic' => '1' ); }
		return $fields;
	}



	/**
	 * Change address default locale fields priority order on the frontend.
	 */
	public function ziptastic_change_address_fields_priority( $fields ) {
		
		$fields_display_order = array(
			'country' => 45,
			'postcode' => 46,
			'address_1' => 50, 
			'address_2' => 60,
			'city' => 70,
			'state' => 80,
		);
		
		// Set fields priority
		foreach( $fields_display_order as $field => $priority ) {
			if ( array_key_exists( $field, $fields ) ) { $fields[ $field ]['priority'] = $priority; }
		}
		
		return $fields;
	}



	/**
	 * Change shipping fields display order.
	 */
	public function change_address_fields_display_class( $fields ) {
		$types = array( 'billing', 'shipping' );
		$field_keys = array( 'country', 'postcode' );
		$field_classes = array(
			'country' => array( 'form-row-first' ),
			'postcode' => array( 'form-row-last' ),
		);
		
		foreach( $types as $type ) {
			foreach( $field_classes as $field_key => $value ) {
				$classes_to_remove = array( 'form-row-wide' );

				$classes = $fields[ $type ][ $type . '_' . $field_key ]['class'];
				$classes = array_diff( $classes, $classes_to_remove );
				$classes = array_merge( $classes, $field_classes[ $field_key ] );

				$fields[ $type ][ $type . '_' . $field_key ]['class'] = $classes;
			}
		}

		return $fields;
	}



	/**
	 * Change address fields args to display in best order for ziptastic auto-fill
	 */
	public function change_address_field_args( $field_args ) {
		$field_args = wc_array_overlay( $field_args, array(
			'billing_country'		=> array( 'priority' => 45, 'class' => array( 'form-row-first' ) ),
			'billing_postcode'		=> array( 'priority' => 46, 'class' => array( 'form-row-last' ) ),
			'billing_address_1'		=> array( 'priority' => 50 ),
			'billing_address_2'		=> array( 'priority' => 60 ),
			'billing_city'			=> array( 'priority' => 70 ),
			'billing_state'			=> array( 'priority' => 80 ),

			'shipping_country'		=> array( 'priority' => 45, 'class' => array( 'form-row-first' ) ),
			'shipping_postcode'		=> array( 'priority' => 46, 'class' => array( 'form-row-last' ) ),
			'shipping_address_1'	=> array( 'priority' => 50 ),
			'shipping_address_2'	=> array( 'priority' => 60 ),
			'shipping_city'			=> array( 'priority' => 70 ),
			'shipping_state'		=> array( 'priority' => 80 ),
		) );

		return $field_args;
	}

}

FluidCheckout_IntegrationZiptastic::instance();
