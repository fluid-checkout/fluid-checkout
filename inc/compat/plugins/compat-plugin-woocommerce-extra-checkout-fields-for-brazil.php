<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Brazilian Market on WooCommerce (by Claudio Sanches).
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
		add_filter( 'gettext', array( $this, 'change_mailcheck_options_text' ), 10, 3 );

		// Checkout fields args
		add_filter( 'fc_checkout_field_args', array( $this, 'change_checkout_field_args' ), 110 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_field_args' ), 110 );
		add_filter( 'fc_billing_same_as_shipping_field_keys' , array( $this, 'remove_billing_company_from_copy_shipping_field_keys' ), 10 );

		// Make fields required
		add_filter( 'wcbcf_billing_fields', array( $this, 'make_person_type_fields_required' ), 110 );

		// Step complete billing
		add_filter( 'fc_is_step_complete_billing_field_keys_skip_list', array( $this, 'maybe_add_step_complete_billing_field_skip_list_by_person_type' ), 10 );

		// Prevent hiding optional gift option fields behind a link button
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_person_type_fields' ), 10 );

		// Address format
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'change_address_format' ), 20 );
		add_filter( 'fc_billing_substep_text_address_data', array( $this, 'change_billing_address_data_for_substep_text_lines' ), 10 );

		// Substep review text
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_shipping' ), 10 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_billing' ), 10 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_by_person_type' ), 10 );

		// Shipping phone
		if ( class_exists( 'FluidCheckout_CheckoutShippingPhoneField' ) ) {
			add_filter( 'wcbcf_shipping_fields', array( FluidCheckout_CheckoutShippingPhoneField::instance(), 'add_shipping_phone_field' ), 5 );
			add_filter( 'wcbcf_shipping_fields' , array( FluidCheckout_CheckoutShippingPhoneField::instance(), 'change_shipping_company_field_args' ), 10 );
		}
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
	 * Change text for the mailcheck features from this plugin.
	 *
	 * @param   string  $translated   The translated text.
	 * @param   string  $text         The original text.
	 * @param   string  $text_domain  The text domain.
	 */
	public function change_mailcheck_options_text( $translated, $text, $text_domain ) {
		// Bail if not the targetted text domain
		if ( 'woocommerce-extra-checkout-fields-for-brazil' !== $text_domain ) { return $translated; }

		if ( 'Enable Mail Check:' === $text ) {
			$translated = __( 'Enable Mail Check: (disabled feature)', 'fluid-checkout' );
		}
		else if ( 'If checked informs typos in email to users.' === $text ) {
			$translated = __( 'If checked informs typos in email to users. (This feature has been disabled because Fluid Checkout offers the feature. Changes to this option will not take effect)', 'fluid-checkout' );
		}

		return $translated;
	}



	/**
	 * Change checkout fields args.
	 *
	 * @param   array  $field_args  Contains checkout field arguments.
	 */
	public function change_checkout_field_args( $field_args ) {

		$new_field_args = array (
			'billing_email'            => array( 'priority' => 10 ),
			
			'billing_first_name'       => array( 'priority' => 20 ),
			'billing_last_name'        => array( 'priority' => 30 ),
			'billing_phone'            => array( 'priority' => 40 ),
			'billing_cellphone'        => array( 'priority' => 50 ),

			'billing_country'          => array( 'priority' => 70, 'class' => array( 'form-row-wide' ) ),
			'billing_postcode'         => array( 'priority' => 80, 'class' => array( 'form-row-first' ) ),
			'billing_address_1'        => array( 'priority' => 90, 'class' => array( 'form-row-first', 'form-row-two-thirds' ) ),
			'billing_number'           => array( 'priority' => 100, 'class' => array( 'form-row-last', 'form-row-one-third' ) ),
			'billing_address_2'        => array( 'priority' => 110, 'class' => array( 'form-row-wide' ) ),
			'billing_neighborhood'     => array( 'priority' => 120, 'class' => array( 'form-row-first' ) ),
			'billing_city'             => array( 'priority' => 130, 'class' => array( 'form-row-last' ) ),
			'billing_state'            => array( 'priority' => 140, 'class' => array( 'form-row-wide' ) ),

			'billing_persontype'       => array( 'priority' => 150, 'class' => array( 'form-row-wide' ) ),
			'billing_company'          => array( 'priority' => 160, 'class' => array( 'form-row-wide' ) ),
			'billing_cpf'              => array( 'priority' => 170, 'class' => array( 'form-row-first' ) ),
			'billing_rg'               => array( 'priority' => 180, 'class' => array( 'form-row-last' ) ),
			'billing_cnpj'             => array( 'priority' => 190, 'class' => array( 'form-row-first' ) ),
			'billing_ie'               => array( 'priority' => 200, 'class' => array( 'form-row-last' ) ),
			'billing_birthdate'        => array( 'priority' => 210, 'class' => array( 'form-row-first' ) ),
			'billing_sex'              => array( 'priority' => 220, 'class' => array( 'form-row-last' ) ),

			'shipping_first_name'      => array( 'priority' => 20 ),
			'shipping_last_name'       => array( 'priority' => 30 ),
			'shipping_phone'           => array( 'priority' => 40 ),
			'shipping_cellphone'       => array( 'priority' => 50 ),
			'shipping_company'         => array( 'priority' => 60, 'class' => array( 'form-row-wide' ) ),

			'shipping_country'         => array( 'priority' => 70, 'class' => array( 'form-row-wide' ) ),
			'shipping_postcode'        => array( 'priority' => 80, 'class' => array( 'form-row-first' ) ),
			'shipping_address_1'       => array( 'priority' => 90, 'class' => array( 'form-row-first', 'form-row-two-thirds' ) ),
			'shipping_number'          => array( 'priority' => 100, 'class' => array( 'form-row-last', 'form-row-one-third' ) ),
			'shipping_address_2'       => array( 'priority' => 110, 'class' => array( 'form-row-wide' ) ),
			'shipping_neighborhood'    => array( 'priority' => 120, 'class' => array( 'form-row-first' ) ),
			'shipping_city'            => array( 'priority' => 130, 'class' => array( 'form-row-last' ) ),
			'shipping_state'           => array( 'priority' => 140, 'class' => array( 'form-row-wide' ) ),
		);

		// Merge class arguments with existing values
		foreach ( $new_field_args as $field_key => $new_args ) {
			// Skip if class attribute is not set on the original attributes
			if ( ! array_key_exists( $field_key, $field_args ) || ! array_key_exists( 'class', $field_args[ $field_key ] ) || ! is_array( $field_args[ $field_key ][ 'class' ] ) ) { continue; }

			// Skip if class attribute is not set
			if ( ! array_key_exists( 'class', $new_args ) || ! is_array( $new_args[ 'class' ] ) ) { continue; }

			// Merge classes
			$new_args[ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $field_args[ $field_key ][ 'class' ], $new_args[ 'class' ] );
		}

		// Merge field arguments with existing values
		foreach ( $new_field_args as $field_key => $new_args ) {
			// Skip if field args not yet set to the original attributes
			if ( ! array_key_exists( $field_key, $field_args ) ) { continue; }
			
			$new_field_args[ $field_key ] = array_merge( $field_args[ $field_key ], $new_args );
		}
		
		return $new_field_args;
	}

	/**
	 * Change address fields args.
	 *
	 * @param   array  $field_args  Contains locale address field arguments.
	 */
	public function change_default_locale_field_args( $field_args ) {

		$new_field_args = array (
			'first_name'       => array( 'priority' => 20 ),
			'last_name'        => array( 'priority' => 30 ),
			'phone'            => array( 'priority' => 40 ),
			'cellphone'        => array( 'priority' => 50 ),
			'country'          => array( 'priority' => 70, 'class' => array( 'form-row-wide' ) ),
			'postcode'         => array( 'priority' => 80, 'class' => array( 'form-row-first' ) ),
			'address_1'        => array( 'priority' => 90, 'class' => array( 'form-row-first', 'form-row-two-thirds' ) ),
			'number'           => array( 'priority' => 100, 'class' => array( 'form-row-last', 'form-row-one-third' ) ),
			'address_2'        => array( 'priority' => 110, 'class' => array( 'form-row-wide' ) ),
			'neighborhood'     => array( 'priority' => 120, 'class' => array( 'form-row-first' ) ),
			'city'             => array( 'priority' => 130, 'class' => array( 'form-row-last' ) ),
			'state'            => array( 'priority' => 140, 'class' => array( 'form-row-wide' ) ),

			'company'          => array( 'priority' => 60, 'class' => array( 'form-row-wide' ) ),
		);

		// Merge class arguments with existing values
		foreach ( $new_field_args as $field_key => $new_args ) {
			// Skip if class attribute is not set on the original attributes
			if ( ! array_key_exists( $field_key, $field_args ) || ! array_key_exists( 'class', $field_args[ $field_key ] ) || ! is_array( $field_args[ $field_key ][ 'class' ] ) ) { continue; }

			// Skip if class attribute is not set
			if ( ! array_key_exists( 'class', $new_args ) || ! is_array( $new_args[ 'class' ] ) ) { continue; }

			// Merge classes
			$new_args[ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $field_args[ $field_key ][ 'class' ], $new_args[ 'class' ] );
		}

		// Merge field arguments with existing values
		foreach ( $new_field_args as $field_key => $new_args ) {
			// Skip if field args not yet set to the original attributes
			if ( ! array_key_exists( $field_key, $field_args ) ) { continue; }
			
			$new_field_args[ $field_key ] = array_merge( $field_args[ $field_key ], $new_args );
		}
		
		return $new_field_args;
	}



	/**
	 * Remove billing company from fields to copy from shipping address.
	 *
	 * @param   array  $billing_copy_shipping_field_keys  List of billing field ids to copy from the shipping address.
	 */
	public function remove_billing_company_from_copy_shipping_field_keys( $billing_copy_shipping_field_keys ) {
		if ( in_array( 'billing_company', $billing_copy_shipping_field_keys ) ) {
			$billing_copy_shipping_field_keys = array_diff( $billing_copy_shipping_field_keys, array( 'billing_company' ) );
		}
		return $billing_copy_shipping_field_keys;
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

		// Get person type
		$person_type = WC()->checkout()->get_value( 'billing_persontype' );

		// Set existing fields as required
		if ( array_key_exists( 'billing_persontype', $new_fields ) ) { $new_fields[ 'billing_persontype' ][ 'required' ] = true; }

		if ( 1 == $person_type ) { // 1 = Individual
			if ( array_key_exists( 'billing_cpf', $new_fields ) ) { $new_fields[ 'billing_cpf' ][ 'required' ] = true; }
			if ( array_key_exists( 'billing_rg', $new_fields ) ) { $new_fields[ 'billing_rg' ][ 'required' ] = true; }
		}
		else if ( 2 == $person_type ) { // 2 = Legal Person
			if ( array_key_exists( 'billing_cnpj', $new_fields ) ) { $new_fields[ 'billing_cnpj' ][ 'required' ] = true; }
			if ( array_key_exists( 'billing_ie', $new_fields ) ) { $new_fields[ 'billing_ie' ][ 'required' ] = true; }
		}

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
		$skip_list[] = 'billing_company';

		return $skip_list;
	}



	/**
	 * Add extra fields to skip for the substep review text by address type.
	 *
	 * @param   array   $skip_list     List of fields to skip adding to the substep review text.
	 * @param   string  $address_type  The address type.
	 */
	public function change_substep_text_extra_fields_skip_list_by_address_type( $skip_list, $address_type ) {
		$skip_list[] = $address_type . '_persontype';
		$skip_list[] = $address_type . '_number';
		$skip_list[] = $address_type . '_neighborhood';
		return $skip_list;
	}

	/**
	 * Add shipping extra fields to skip for the substep review text.
	 *
	 * @param   array  $skip_list  List of fields to skip adding to the substep review text.
	 */
	public function change_substep_text_extra_fields_skip_list_shipping( $skip_list ) {
		return $this->change_substep_text_extra_fields_skip_list_by_address_type( $skip_list, 'shipping' );
	}

	/**
	 * Add billing extra fields to skip for the substep review text.
	 *
	 * @param   array  $skip_list  List of fields to skip adding to the substep review text.
	 */
	public function change_substep_text_extra_fields_skip_list_billing( $skip_list ) {
		return $this->change_substep_text_extra_fields_skip_list_by_address_type( $skip_list, 'billing' );
	}

	/**
	 * Remove billing company from extra fields to skip for the substep review text.
	 *
	 * @param   array  $skip_list  List of fields to skip adding to the substep review text.
	 */
	public function change_substep_text_extra_fields_skip_list_by_person_type( $skip_list ) {
		$person_type = WC()->checkout()->get_value( 'billing_persontype' );

		if ( 1 == $person_type ) { // 1 = Individual
			$skip_list = array_merge( $skip_list, array( 'billing_cnpj', 'billing_ie' ) );
		}
		else if ( 2 == $person_type ) { // 2 = Legal Person
			$skip_list = array_diff( $skip_list, array( 'billing_company' ) );
			$skip_list = array_merge( $skip_list, array( 'billing_cpf', 'billing_rg' ) );
		}

		return $skip_list;
	}



	/**
	 * Change country address formats for Brazil.
	 *
	 * @param  array  $formats  Default address formats.
	 */
	public function change_address_format( $formats ) {
		$formats['BR'] = str_replace( '{name}', "{name}\n{company}", $formats['BR'] );
		return $formats;
	}



	/**
	 * Remove billing company from the billing address data used for the substep review text.
	 *
	 * @param   array   $address_data  The address data for the substep review text.
	 */
	public function change_billing_address_data_for_substep_text_lines( $address_data ) {
		unset( $address_data[ 'company' ] );
		return $address_data;
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
