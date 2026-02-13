<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Hezarfen For Woocommerce (by Intense Yazılım Ltd.)
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

		// Keep Hezarfen tax fields (Title/billing_company, Tax Number, Tax Office) in the always-visible billing only section
		// When in the "billing same as shipping" collapsible block, these required fields can be hidden and cause validation errors. Hezarfen uses billing_company as "Invoice Title".
		add_filter( 'fc_billing_same_as_shipping_field_keys', array( $this, 'remove_billing_company_from_copy_shipping_field_keys' ), 10 );

		// When invoice type is empty, the tax fields are hidden but still required - causing validation errors.
		// Unset them from validation when not selected (same as Hezarfen does for "person" type).
		add_action( 'woocommerce_before_checkout_process', array( $this, 'maybe_unset_hezarfen_tax_fields_when_invoice_type_empty' ), 5 );

		// Hide company tax fields (Invoice Title, Tax Number, Tax Office) from billing summary when "Personal" invoice type is selected
		add_filter( 'fc_substep_billing_address_text_lines', array( $this, 'remove_hezarfen_company_fields_from_billing_summary_when_personal' ), 999 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'wc_hezarfen_checkout_js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/hezarfen-for-woocommerce/checkout' ), array( 'jquery', 'wc-checkout', 'wc_hezarfen_mahalle_helper_js' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
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
				if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'hezarfen_checkout_fields_auto_sort' ) ) {
					$new_field_args[ 'billing_state' ][ 'priority' ] = 50;
					$new_field_args[ 'billing_city' ][ 'priority' ] = 60;
					$new_field_args[ 'billing_address_1' ][ 'priority' ] = 70;
					$new_field_args[ 'billing_address_2' ][ 'priority' ] = 80;
					$new_field_args[ 'billing_postcode' ][ 'priority' ] = 90;
				}

				// Hide postcode field when option is enabled on the Hezarfen plugin settings
				if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'hezarfen_hide_checkout_postcode_fields' ) ) {
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
				if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'hezarfen_checkout_fields_auto_sort' ) ) {
					$new_field_args[ 'shipping_state' ][ 'priority' ] = 50;
					$new_field_args[ 'shipping_city' ][ 'priority' ] = 60;
					$new_field_args[ 'shipping_address_1' ][ 'priority' ] = 70;
					$new_field_args[ 'shipping_address_2' ][ 'priority' ] = 80;
					$new_field_args[ 'shipping_postcode' ][ 'priority' ] = 90;
				}

				// Hide postcode field when option is enabled on the Hezarfen plugin settings
				if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'hezarfen_hide_checkout_postcode_fields' ) ) {
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
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'hezarfen_checkout_fields_auto_sort' ) ) {
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



	/**
	 * Remove billing company (Title) from fields to copy from shipping.
	 * When Hezarfen checkout tax fields are active, billing_company is repurposed as "Title" (invoice title)
	 * and must remain in the always-visible billing-only section to prevent "Billing Title is a required field" errors
	 * when the "billing same as shipping" block is collapsed.
	 *
	 * @param   array  $billing_copy_shipping_field_keys  List of billing field keys to copy from shipping.
	 * @return  array  Modified list of billing field keys.
	 */
	public function remove_billing_company_from_copy_shipping_field_keys( $billing_copy_shipping_field_keys ) {
		// Bail if Hezarfen checkout tax fields are not active
		if ( 'yes' !== get_option( 'hezarfen_show_hezarfen_checkout_tax_fields', 'no' ) ) {
			return $billing_copy_shipping_field_keys;
		}

		// Remove billing company from fields to copy from shipping
		if ( is_array( $billing_copy_shipping_field_keys ) && in_array( 'billing_company', $billing_copy_shipping_field_keys ) ) {
			$billing_copy_shipping_field_keys = array_diff( $billing_copy_shipping_field_keys, array( 'billing_company' ) );
		}

		return $billing_copy_shipping_field_keys;
	}



	/**
	 * When Hezarfen invoice type is empty, the Title/Tax Number/Tax Office fields are hidden (hezarfen-hide-form-field)
	 * but still required - causing "Billing Title is a required field" etc. errors.
	 * Unset these fields from checkout validation when invoice type is not selected (same logic as Hezarfen's "person" type).
	 */
	public function maybe_unset_hezarfen_tax_fields_when_invoice_type_empty() {
		// Bail if Hezarfen checkout tax fields are not active
		if ( 'yes' !== get_option( 'hezarfen_show_hezarfen_checkout_tax_fields', 'no' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$invoice_type = isset( $_POST['billing_hez_invoice_type'] ) ? sanitize_key( wp_unslash( $_POST['billing_hez_invoice_type'] ) ) : '';

		// When empty or "person", the company tax fields (Title, Tax Number, Tax Office) are hidden - don't validate them.
		if ( '' === $invoice_type || 'person' === $invoice_type ) {
			add_filter( 'woocommerce_checkout_fields', array( $this, 'unset_hezarfen_company_tax_fields_from_validation' ), 999998, 1 );

			// Clear cached checkout fields so get_checkout_fields() re-initializes and applies our filter.
			// Fields are cached on first use (e.g. during form render); without this our filter never runs.
			if ( function_exists( 'WC' ) && WC()->checkout ) {
				WC()->checkout->checkout_fields = null;
			}
		}
	}



	/**
	 * Unset Hezarfen company tax fields (Title, Tax Number, Tax Office) from checkout fields.
	 * Used when invoice type is empty or "person" - these fields are hidden and should not be validated.
	 *
	 * @param   array  $fields  Checkout fields.
	 * @return  array  Modified checkout fields.
	 */
	public function unset_hezarfen_company_tax_fields_from_validation( $fields ) {
		if ( isset( $fields['billing'] ) ) {
			unset( $fields['billing']['billing_company'], $fields['billing']['billing_hez_tax_number'], $fields['billing']['billing_hez_tax_office'] );
		}
		return $fields;
	}



	/**
	 * Remove company tax fields (Invoice Title, Tax Number, Tax Office) from billing summary text lines
	 * when "Personal" invoice type is selected.
	 *
	 * @param   array  $review_text_lines  The list of lines shown in the billing address substep review text.
	 * @return  array  Modified list of lines.
	 */
	public function remove_hezarfen_company_fields_from_billing_summary_when_personal( $review_text_lines ) {
		// Bail if Hezarfen checkout tax fields are not active
		if ( 'yes' !== get_option( 'hezarfen_show_hezarfen_checkout_tax_fields', 'no' ) ) {
			return $review_text_lines;
		}

		// Get the current Hezarfen invoice type for display purposes.
		$invoice_type = $this->get_hezarfen_invoice_type_for_display();
		if ( '' !== $invoice_type && 'person' !== $invoice_type ) {
			return $review_text_lines;
		}

		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) {
			return $review_text_lines;
		}

		// Get display values for company fields to remove from summary
		$values_to_remove = array();
		if ( function_exists( 'WC' ) && WC()->checkout && class_exists( 'FluidCheckout_Steps' ) ) {
			$address_fields = WC()->checkout->get_checkout_fields( 'billing' );
			$company_field_keys = array( 'billing_company', 'billing_hez_tax_number', 'billing_hez_tax_office' );
			foreach ( $company_field_keys as $field_key ) {
				if ( isset( $address_fields[ $field_key ] ) ) {
					$field_value = WC()->checkout->get_value( $field_key );
					$display_value = FluidCheckout_Steps::instance()->get_field_display_value( $field_value, $field_key, $address_fields[ $field_key ] );
					if ( ! empty( $display_value ) ) {
						$values_to_remove[] = wp_strip_all_tags( $display_value );
					}
				}
			}
		}

		// Bail if no values to remove
		if ( empty( $values_to_remove ) ) {
			return $review_text_lines;
		}

		// Remove the values from the review text lines
		return array_values( array_filter( $review_text_lines, function( $line ) use ( $values_to_remove ) {
			$line_stripped = wp_strip_all_tags( $line );
			return ! in_array( $line_stripped, $values_to_remove, true );
		} ) );
	}



	/**
	 * Get the current Hezarfen invoice type for display purposes.
	 * Checks checkout value, POST data, and parsed post_data.
	 *
	 * @return  string  'person', 'company', or empty string.
	 */
	private function get_hezarfen_invoice_type_for_display() {
		// Initialize invoice type
		$invoice_type = '';
		// Get invoice type from checkout value
		if ( function_exists( 'WC' ) && WC()->checkout ) {
			$invoice_type = WC()->checkout->get_value( 'billing_hez_invoice_type' );
		}
		// Get invoice type from POST data
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' === $invoice_type && isset( $_POST['post_data'] ) ) {
			parse_str( wp_unslash( $_POST['post_data'] ), $post_data );
			$invoice_type = isset( $post_data['billing_hez_invoice_type'] ) ? sanitize_key( $post_data['billing_hez_invoice_type'] ) : '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' === $invoice_type && isset( $_POST['billing_hez_invoice_type'] ) ) {
			$invoice_type = sanitize_key( wp_unslash( $_POST['billing_hez_invoice_type'] ) );
		}
		return $invoice_type;
	}

}

FluidCheckout_HezarfenForWooCommerce::instance();
