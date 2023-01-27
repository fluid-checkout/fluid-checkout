<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: 
 */
class FluidCheckout_HezarfenForWooCommerce extends FluidCheckout {

	/**
	 * Hold cached values to improve performance.
	 */
	private $cached_values = array();



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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Checkout fields args
		add_filter( 'fc_checkout_field_args', array( $this, 'change_checkout_field_args' ), 110 );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'change_locale_fields_args' ), 110 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_field_args' ), 110 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'wc_hezarfen_checkout_js', self::$directory_url . 'js/compat/plugins/hezarfen-for-woocommerce/checkout' . self::$asset_version . '.js', array( 'jquery', 'wc-checkout', 'wc_hezarfen_mahalle_helper_js' ), NULL );
	}



	/**
	 * Change checkout fields args.
	 *
	 * @param   array  $field_args  Contains checkout field arguments.
	 */
	public function change_checkout_field_args( $field_args ) {
		// Bail if checkout function and object are not available
		if ( ! function_exists( 'WC' ) || ! method_exists( WC(), 'checkout' ) ) { return $field_args; }

		// Get values from cache
		$cache_handle = 'checkout_field_args';
		$skip_cache = false;
		
		// Try to return value from cache
		if ( ! $skip_cache && array_key_exists( $cache_handle, $this->cached_values ) ) {
			// Return value from cache
			return $this->cached_values[ $cache_handle ];
		}
		// Calculate new value
		else {
			$new_field_args = array();
			
			// Get country field values
			$billing_country = WC()->checkout()->get_value( 'billing_country' );
			$shipping_country = WC()->checkout()->get_value( 'shipping_country' );

			// Billing fields
			if ( 'TR' === $billing_country ) {
				$new_field_args = array_merge( $new_field_args, array (
					'billing_state'            => array( 'class' => array( 'form-row-wide' ) ),
					'billing_city'             => array( 'class' => array( 'form-row-wide' ) ),
					'billing_address_1'        => array( 'class' => array( 'form-row-wide' ) ),
					'billing_address_2'        => array( 'class' => array( 'form-row-wide' ) ),
					'billing_postcode'         => array( 'class' => array( 'form-row-first' ) ),
				) );

				// Change field priority when option to sort fields is enabled on the Hezarfen plugin settings
				if ( 'yes' === get_option( 'hezarfen_checkout_fields_auto_sort', 'no' ) ) {
					$new_field_args[ 'billing_state' ][ 'priority' ] = 50;
					$new_field_args[ 'billing_city' ][ 'priority' ] = 60;
					$new_field_args[ 'billing_address_1' ][ 'priority' ] = 70;
					$new_field_args[ 'billing_address_2' ][ 'priority' ] = 80;
					$new_field_args[ 'billing_postcode' ][ 'priority' ] = 90;
				}

				// Hide postcode field when option is enabled on the Hezarfen plugin settings
				if ( 'yes' === get_option( 'hezarfen_hide_checkout_postcode_fields', 'no' ) ) {
					$new_field_args[ 'billing_postcode' ][ 'required' ] = false;
					$new_field_args[ 'billing_postcode' ][ 'type' ] = 'hidden';
					$new_field_args[ 'billing_postcode' ][ 'hidden' ] = true;
					$new_field_args[ 'billing_postcode' ][ 'class' ] = array_key_exists( 'class', $new_field_args[ 'billing_postcode' ] ) ? $new_field_args[ 'billing_postcode' ][ 'class' ] : array();
					$new_field_args[ 'billing_postcode' ][ 'class' ] = array_merge( $new_field_args[ 'billing_postcode' ][ 'class' ], array( 'fc-hidden' ) );
				}
			}

			// Shipping fields
			if ( 'TR' === $shipping_country ) {
				$new_field_args = array_merge( $new_field_args, array (
					'shipping_state'            => array( 'class' => array( 'form-row-wide' ) ),
					'shipping_city'             => array( 'class' => array( 'form-row-wide' ) ),
					'shipping_address_1'        => array( 'class' => array( 'form-row-wide' ) ),
					'shipping_address_2'        => array( 'class' => array( 'form-row-wide' ) ),
					'shipping_postcode'         => array( 'class' => array( 'form-row-first' ) ),
				) );

				// Change field priority when option to sort fields is enabled on the Hezarfen plugin settings
				if ( 'yes' === get_option( 'hezarfen_checkout_fields_auto_sort', 'no' ) ) {
					$new_field_args[ 'shipping_state' ][ 'priority' ] = 50;
					$new_field_args[ 'shipping_city' ][ 'priority' ] = 60;
					$new_field_args[ 'shipping_address_1' ][ 'priority' ] = 70;
					$new_field_args[ 'shipping_address_2' ][ 'priority' ] = 80;
					$new_field_args[ 'shipping_postcode' ][ 'priority' ] = 90;
				}

				// Hide postcode field when option is enabled on the Hezarfen plugin settings
				if ( 'yes' === get_option( 'hezarfen_hide_checkout_postcode_fields', 'no' ) ) {
					$new_field_args[ 'shipping_postcode' ][ 'required' ] = false;
					$new_field_args[ 'shipping_postcode' ][ 'type' ] = 'hidden';
					$new_field_args[ 'shipping_postcode' ][ 'hidden' ] = true;
					$new_field_args[ 'shipping_postcode' ][ 'class' ] = array_key_exists( 'class', $new_field_args[ 'shipping_postcode' ] ) ? $new_field_args[ 'shipping_postcode' ][ 'class' ] : array();
					$new_field_args[ 'shipping_postcode' ][ 'class' ] = array_merge( $new_field_args[ 'shipping_postcode' ][ 'class' ], array( 'fc-hidden' ) );
				}
			}

			// Merge class arguments with existing values
			foreach ( $new_field_args as $field_key => $new_args ) {
				// Skip if class attribute is not set on the original attributes
				if ( ! array_key_exists( $field_key, $field_args ) || ! array_key_exists( 'class', $field_args[ $field_key ] ) || ! is_array( $field_args[ $field_key ][ 'class' ] ) ) { continue; }

				// Skip if class attribute is not set
				if ( ! array_key_exists( 'class', $new_args ) || ! is_array( $new_args[ 'class' ] ) ) { continue; }

				// Merge classes
				if ( class_exists( 'FluidCheckout_CheckoutFields' ) ) {
					$new_args[ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $field_args[ $field_key ][ 'class' ], $new_args[ 'class' ] );
				}
			}

			// Merge field arguments with existing values
			foreach ( $new_field_args as $field_key => $new_args ) {
				// Skip if field args not yet set to the original attributes
				if ( ! array_key_exists( $field_key, $field_args ) ) { continue; }
				
				$new_field_args[ $field_key ] = array_merge( $field_args[ $field_key ], $new_args );
			}
		}

		// Set cache
		$this->cached_values[ $cache_handle ] = $new_field_args;
		
		return $new_field_args;
	}

	/**
	 * Change address fields args.
	 *
	 * @param   array  $field_args  Contains locale address field arguments.
	 */
	public function change_default_locale_field_args( $field_args ) {
		// Set class attributes
		$new_field_args = array (
			'state'            => array( 'class' => array( 'form-row-wide' ) ),
			'city'             => array( 'class' => array( 'form-row-wide' ) ),
			'address_1'        => array( 'class' => array( 'form-row-wide' ) ),
			'address_2'        => array( 'class' => array( 'form-row-wide' ) ),
			'postcode'         => array( 'class' => array( 'form-row-first' ) ),
		);

		// Set field priority when option to sort fields is enabled on the Hezarfen plugin settings
		if ( 'yes' === get_option( 'hezarfen_checkout_fields_auto_sort', 'no' ) ) {
			$new_field_args[ 'state' ][ 'priority' ] = 50;
			$new_field_args[ 'city' ][ 'priority' ] = 60;
			$new_field_args[ 'address_1' ][ 'priority' ] = 70;
			$new_field_args[ 'address_2' ][ 'priority' ] = 80;
			$new_field_args[ 'postcode' ][ 'priority' ] = 90;
		}

		// Merge class arguments with existing values
		foreach ( $new_field_args as $field_key => $new_args ) {
			// Skip if class attribute is not set on the original attributes
			if ( ! array_key_exists( $field_key, $field_args ) || ! array_key_exists( 'class', $field_args[ $field_key ] ) || ! is_array( $field_args[ $field_key ][ 'class' ] ) ) { continue; }

			// Skip if class attribute is not set
			if ( ! array_key_exists( 'class', $new_args ) || ! is_array( $new_args[ 'class' ] ) ) { continue; }

			// Merge classes
			if ( class_exists( 'FluidCheckout_CheckoutFields' ) ) {
				$new_field_args[ $field_key ][ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $field_args[ $field_key ][ 'class' ], $new_args[ 'class' ] );
			}
		}

		// Merge field arguments with existing values
		foreach ( $new_field_args as $field_key => $new_args ) {
			// Skip if field args not yet set to the original attributes
			if ( ! array_key_exists( $field_key, $field_args ) ) { continue; }
			
			$field_args[ $field_key ] = array_merge( $field_args[ $field_key ], $new_args );
		}
		
		return $field_args;
	}

	/**
	 * Change field arguments for Turkey locale.
	 */
	public static function change_locale_fields_args( $locales ) {
		// Merge classes
		if ( class_exists( 'FluidCheckout_CheckoutFields' ) ) {
			// Add missing fields args
			$locales[ 'TR' ][ 'state' ] = array_key_exists( 'state', $locales[ 'TR' ] ) ? $locales[ 'TR' ][ 'state' ] : array();
			$locales[ 'TR' ][ 'city' ] = array_key_exists( 'city', $locales[ 'TR' ] ) ? $locales[ 'TR' ][ 'city' ] : array();
			$locales[ 'TR' ][ 'address_1' ] = array_key_exists( 'address_1', $locales[ 'TR' ] ) ? $locales[ 'TR' ][ 'address_1' ] : array();
			$locales[ 'TR' ][ 'address_2' ] = array_key_exists( 'address_2', $locales[ 'TR' ] ) ? $locales[ 'TR' ][ 'address_2' ] : array();
			$locales[ 'TR' ][ 'postcode' ] = array_key_exists( 'postcode', $locales[ 'TR' ] ) ? $locales[ 'TR' ][ 'postcode' ] : array();

			// Add missing class attribute fields args
			$locales[ 'TR' ][ 'state' ][ 'class' ] = array_key_exists( 'class', $locales[ 'TR' ][ 'state' ] ) ? $locales[ 'TR' ][ 'state' ][ 'class' ] : array();
			$locales[ 'TR' ][ 'city' ][ 'class' ] = array_key_exists( 'class', $locales[ 'TR' ][ 'city' ] ) ? $locales[ 'TR' ][ 'city' ][ 'class' ] : array();
			$locales[ 'TR' ][ 'address_1' ][ 'class' ] = array_key_exists( 'class', $locales[ 'TR' ][ 'address_1' ] ) ? $locales[ 'TR' ][ 'address_1' ][ 'class' ] : array();
			$locales[ 'TR' ][ 'address_2' ][ 'class' ] = array_key_exists( 'class', $locales[ 'TR' ][ 'address_2' ] ) ? $locales[ 'TR' ][ 'address_2' ][ 'class' ] : array();
			$locales[ 'TR' ][ 'postcode' ][ 'class' ] = array_key_exists( 'class', $locales[ 'TR' ][ 'postcode' ] ) ? $locales[ 'TR' ][ 'postcode' ][ 'class' ] : array();

			// Merge existing class attributes with new values
			$locales[ 'TR' ][ 'state' ][ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $locales[ 'TR' ][ 'state' ][ 'class' ], array( 'form-row-wide' ) );
			$locales[ 'TR' ][ 'city' ][ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $locales[ 'TR' ][ 'city' ][ 'class' ], array( 'form-row-wide' ) );
			$locales[ 'TR' ][ 'address_1' ][ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $locales[ 'TR' ][ 'address_1' ][ 'class' ], array( 'form-row-wide' ) );
			$locales[ 'TR' ][ 'address_2' ][ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $locales[ 'TR' ][ 'address_2' ][ 'class' ], array( 'form-row-wide' ) );
			$locales[ 'TR' ][ 'postcode' ][ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $locales[ 'TR' ][ 'postcode' ][ 'class' ], array( 'form-row-first' ) );
		}

		return $locales;
	}

}

FluidCheckout_HezarfenForWooCommerce::instance();
