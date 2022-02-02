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

		// Make fields required
		add_filter( 'wcbcf_billing_fields', array( $this, 'make_person_type_fields_required' ), 110 );

		// Step complete billing
		add_filter( 'fc_is_step_complete_billing_field_keys_skip_list', array( $this, 'maybe_add_step_complete_billing_field_skip_list_by_person_type' ), 10 );

		// Prevent hiding optional gift option fields behind a link button
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_person_type_fields' ), 10 );
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
	public function make_person_type_fields_required( $new_fields ) {
		// // Get plugin settings
		$settings = get_option( 'wcbcf_settings' );
		$only_brazil = isset( $settings[ 'only_brazil' ] ) ? true : false;

		// Get billing country
		$billing_country = WC()->checkout->get_value( 'billing_country' );

		// Bail if person type fields are set as required only when billing country is Brazil
		if ( $only_brazil && 'BR' !== $billing_country ) { return $new_fields; }

		// Set existing fields are required
		if ( array_key_exists( 'billing_persontype', $new_fields ) ) { $new_fields[ 'billing_persontype' ][ 'required' ] = true; }
		if ( array_key_exists( 'billing_cpf', $new_fields ) ) { $new_fields[ 'billing_cpf' ][ 'required' ] = true; }
		if ( array_key_exists( 'billing_rg', $new_fields ) ) { $new_fields[ 'billing_rg' ][ 'required' ] = true; }
		if ( array_key_exists( 'billing_cnpj', $new_fields ) ) { $new_fields[ 'billing_cnpj' ][ 'required' ] = true; }
		if ( array_key_exists( 'billing_ie', $new_fields ) ) { $new_fields[ 'billing_ie' ][ 'required' ] = true; }

		return $new_fields;
	}



	/**
	 * Prevent hiding optional person type related fields behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_person_type_fields( $skip_list ) {
		$skip_list[] = 'billing_persontype';
		$skip_list[] = 'billing_cnpj';
		$skip_list[] = 'billing_ie';
		$skip_list[] = 'billing_cpf';
		$skip_list[] = 'billing_rg';
		return $skip_list;
	}



	/**
	 * Add fields by person type to the step complete verification skip list.
	 * 
	 * @param  array  List of fields to skip checking for required value.
	 */
	public function maybe_add_step_complete_billing_field_skip_list_by_person_type( $skip_list ) {
		// Get plugin settings
		$settings = get_option( 'wcbcf_settings' );

		// Bail if person type option does not allow both types at checkout
		if ( 1 != $settings[ 'person_type' ] ) { return $skip_list; } // 1 = Individuals and Legal Person
		
		// Bail if person type is invalid
		$person_type = WC()->checkout()->get_value( 'billing_persontype' );
		if ( 1 != $person_type && 2 != $person_type ) { return $skip_list; }

		// Add Legal Person fields to skip list when person type is Individual
		if ( 1 == $person_type ) { // 1 = Individual
			$skip_list[] = 'billing_cnpj';
			$skip_list[] = 'billing_ie';
		}
		// Add Individual fields to skip list when person type is Legal Person
		else if ( 2 == $person_type ) {  // 2 = Legal Person
			$skip_list[] = 'billing_cpf';
			$skip_list[] = 'billing_rg';
		}

		return $skip_list;
	}

}

FluidCheckout_WooCommerceExtraCheckoutFieldsForBrazil::instance();
