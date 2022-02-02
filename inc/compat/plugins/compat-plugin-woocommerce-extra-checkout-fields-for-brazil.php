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
		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'replace_wcbcf_script' ), 20 );

		// Force change options
		add_filter( 'option_wcbcf_settings', array( $this, 'disable_mailcheck_option' ), 10 );

		// New checkout fields.
		add_filter( 'wcbcf_billing_fields', array( $this, 'make_billing_fields_required' ), 110 );
	}



	/**
	 * Replace plugin scripts with modified versions.
	 */
	public function replace_wcbcf_script() {
		// Replace frontend script
		wp_deregister_script( 'woocommerce-extra-checkout-fields-for-brazil-front' );
		wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-front', self::$directory_url . 'js/compat/plugins/woocommerce-extra-checkout-fields-for-brazil/frontend'. self::$asset_version . '.js', array( 'jquery', 'jquery-mask' ), NULL, true );
		
		// Add localized script params
		// Copied from the original plugin
		$settings = get_option( 'wcbcf_settings' );
		$autofill = isset( $settings[ 'addresscomplete' ] ) ? 'yes' : 'no';
		wp_localize_script (
			'woocommerce-extra-checkout-fields-for-brazil-front',
			'wcbcf_public_params',
			array(
				'state'              => esc_js( __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
				'required'           => esc_js( __( 'required', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
				// CHANGE: Always set mailcheck feature as disabled
				'mailcheck'          => 'no',
				'maskedinput'        => isset( $settings[ 'maskedinput' ] ) ? 'yes' : 'no',
				'addresscomplete'    => apply_filters( 'woocommerce_correios_enable_autofill_addresses', false ) ? false : $autofill,
				'person_type'        => absint( $settings[ 'person_type' ] ),
				'only_brazil'        => isset( $settings[ 'only_brazil' ] ) ? 'yes' : 'no',
				'sort_state_country' => version_compare( WC_VERSION, '3.0', '>=' ),
			)
		);
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
