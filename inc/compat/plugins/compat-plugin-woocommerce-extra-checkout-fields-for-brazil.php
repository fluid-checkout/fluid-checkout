<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Brazilian Market for WooCommerce (by Claudio Sanches).
 */
class FluidCheckout_WooCommerceExtraCheckoutFieldsForBrazil extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Force change options
		add_filter( 'option_wcbcf_settings', array( $this, 'disable_mailcheck_option' ), 10 );

		// New checkout fields.
		add_filter( 'wcbcf_billing_fields', array( $this, 'make_billing_fields_required' ), 110 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
	}



	/**
	 * Disable the Mailcheck feature from the plugin settings.
	 *
	 * @param   array  $settings  The plugin settings.
	 */
	public function disable_mailcheck_option( $settings ) {
		unset( $settings[ 'mailcheck' ] );
		return $settings;
	}


	
	/**
	 * Make billing fields registered as required.
	 *
	 * @param   array  $new_fields  Checkout field args
	 */
	public function make_billing_fields_required( $new_fields ) {
		if ( array_key_exists( 'billing_persontype', $new_fields ) ) { $new_fields[ 'billing_persontype' ][ 'required' ] = true; }
		if ( array_key_exists( 'billing_cpf', $new_fields ) ) { $new_fields[ 'billing_cpf' ][ 'required' ] = true; }
		if ( array_key_exists( 'billing_rg', $new_fields ) ) { $new_fields[ 'billing_rg' ][ 'required' ] = true; }
		if ( array_key_exists( 'billing_cnpj', $new_fields ) ) { $new_fields[ 'billing_cnpj' ][ 'required' ] = true; }
		if ( array_key_exists( 'billing_ie', $new_fields ) ) { $new_fields[ 'billing_ie' ][ 'required' ] = true; }
		return $new_fields;
	}

}

FluidCheckout_WooCommerceExtraCheckoutFieldsForBrazil::instance();
