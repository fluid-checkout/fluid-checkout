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
		if ( get_option( 'wfc_enable_integration_ziptastic', false ) && ! empty( get_option( 'wfc_integration_ziptastic_api_key' ) ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_ziptastic_scripts' ) );
			add_filter( 'woocommerce_default_address_fields' , array( $this, 'ziptastic_change_address_fields_priority' ), 10 );
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'change_address_fields_display_class' ), 20 );
		}
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
			'ziptasticVars',
			array( 
				'ziptasticAPIKey'  => get_option( 'wfc_integration_ziptastic_api_key' ),
				'minChars' => 5,
			)
		);
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

		// Add data-ziptastic attribute to postcode
		if ( array_key_exists( 'postcode', $fields ) ) { $fields['postcode']['custom_attributes'] = array( 'data-ziptastic' => '1' ); }
		
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

}

FluidCheckout_IntegrationZiptastic::instance();