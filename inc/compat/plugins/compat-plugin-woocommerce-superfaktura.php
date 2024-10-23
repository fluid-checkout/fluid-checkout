<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: SuperFaktúra WooCommerce (by 2day.sk, Webikon).
 */
class FluidCheckout_WooCommerceSuperFaktura extends FluidCheckout {

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

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields' ), 10 );

		// Billing fields
		add_filter( 'woocommerce_billing_fields', array( $this, 'maybe_set_additional_billing_fields_required' ), 100 );

		// Substep complete
		add_filter( 'fc_is_substep_complete_billing_address_field_keys_skip_list', array( $this, 'maybe_add_substep_complete_billing_address_field_skip_list' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'wc-sf-checkout-js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-superfaktura/checkout' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Prevent hiding some optional fields behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields( $skip_list ) {
		$skip_list[] = 'billing_company';
		$skip_list[] = 'billing_company_wi_id';
		$skip_list[] = 'billing_company_wi_vat';
		$skip_list[] = 'billing_company_wi_tax';

		return $skip_list;
	}


	/**
	 * Maybe set additional billing fields as required.
	 */
	public function maybe_set_additional_billing_fields_required( $fields ) {
		// Get value for the checkbox field "Purchasing as a company"
		$is_company_purchase = WC()->checkout->get_value( 'wi_as_company' );

		// Bail if not a company purchase
		if ( empty( $is_company_purchase ) ) { return $fields; }

		// Maybe set "ID #" field (ICO) as required.
		// Use function `get_option` directly to be able to pass in the default value.
		if ( array_key_exists( 'billing_company_wi_id', $fields ) && 'required' === get_option( 'woocommerce_sf_add_company_billing_fields_id', 'optional' ) ) {
			$fields[ 'billing_company_wi_id' ][ 'required' ] = true;
		}

		// Maybe set "VAT #" field (IC DPH) as required.
		// Use function `get_option` directly to be able to pass in the default value.
		if ( array_key_exists( 'billing_company_wi_vat', $fields ) && 'required' === get_option( 'woocommerce_sf_add_company_billing_fields_vat', 'optional' ) ) {
			$fields[ 'billing_company_wi_vat' ][ 'required' ] = true;
		}

		// Maybe set "TAX ID #" field (DIC) as required.
		// Use function `get_option` directly to be able to pass in the default value.
		if ( array_key_exists( 'billing_company_wi_tax', $fields ) && 'required' === get_option( 'woocommerce_sf_add_company_billing_fields_tax', 'optional' ) ) {
			$fields[ 'billing_company_wi_tax' ][ 'required' ] = true;
		}

		return $fields;
	}



	/**
	 * Maybe set to skip checking if additional fields are required when determining if the substep is complete.
	 * 
	 * @param  array  List of fields to skip checking for required value.
	 */
	public function maybe_add_substep_complete_billing_address_field_skip_list( $skip_list ) {
		// Get value for the checkbox field "Purchasing as a company"
		$is_company_purchase = WC()->checkout->get_value( 'wi_as_company' );

		// Bail if when purchasing as a company
		if ( ! empty( $is_company_purchase ) ) { return $skip_list; }

		// Add fields to skip list
		$skip_list[] = 'billing_company_wi_id';
		$skip_list[] = 'billing_company_wi_vat';
		$skip_list[] = 'billing_company_wi_tax';

		return $skip_list;
	}

}

FluidCheckout_WooCommerceSuperFaktura::instance();
