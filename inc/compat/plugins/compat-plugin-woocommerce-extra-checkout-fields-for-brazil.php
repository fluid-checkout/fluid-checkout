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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'replace_wcbcf_script' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_brazilian_documents_validation_scripts' ), 20 );

		// JS settings object
		add_filter( 'fc_checkout_validation_brazilian_documents_script_settings', array( $this, 'add_js_settings_checkout_validation_brazilian_documents' ), 10 );

		// Force change options
		add_filter( 'option_wcbcf_settings', array( $this, 'disable_mailcheck_option' ), 10 );
		add_filter( 'gettext', array( $this, 'change_mailcheck_options_text' ), 10, 3 );

		// Checkout fields args
		add_filter( 'fc_checkout_field_args', array( $this, 'change_checkout_field_args' ), 110 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_field_args' ), 110 );
		add_filter( 'fc_billing_same_as_shipping_field_keys' , array( $this, 'remove_billing_company_from_copy_shipping_field_keys' ), 10 );
		add_filter( 'fc_shipping_same_as_billing_field_keys' , array( $this, 'remove_shipping_company_from_copy_billing_field_keys' ), 10 );

		// Checkout fields validation
		add_filter( 'woocommerce_billing_fields', array( $this, 'add_brazilian_documents_validation_classes' ), 1100 ); // Needs to be higher than 1000 to run after checkout field editor plugins

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_person_type_fields' ), 10 );

		// Address format
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'change_address_format' ), 20 );
		add_filter( 'fc_billing_substep_text_address_data', array( $this, 'change_billing_address_data_for_substep_text_lines' ), 10 );

		// Substep review text
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_shipping' ), 10 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_billing' ), 10 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_by_person_type' ), 10 );

		// Step complete billing
		add_filter( 'fc_is_substep_complete_billing_address_field_keys_skip_list', array( $this, 'maybe_add_substep_complete_billing_address_field_skip_list_by_person_type' ), 10 );
		add_filter( 'fc_is_substep_complete_billing_address', array( $this, 'maybe_set_substep_incomplete_billing_address' ), 10 );

		// Add validation status classes to checkout fields
		add_filter( 'woocommerce_form_field_args', array( $this, 'add_checkout_field_validation_status_classes' ), 100, 3 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		$this->shipping_phone_hooks();
	}

	/**
	 * Add or remove late hooks for shipping phone field.
	 */
	public function shipping_phone_hooks() {
		// Bail if shipping phone class is not available
		if ( ! class_exists( 'FluidCheckout_CheckoutShippingPhoneField' ) ) { return; }

		// Bail if shipping phone field is disabled
		if ( ! FluidCheckout_Steps::instance()->is_shipping_phone_enabled() ) { return; }

		// Shipping phone
		add_filter( 'wcbcf_shipping_fields', array( FluidCheckout_CheckoutShippingPhoneField::instance(), 'add_shipping_phone_field' ), 5 );
		add_filter( 'wcbcf_shipping_fields' , array( FluidCheckout_CheckoutShippingPhoneField::instance(), 'change_shipping_company_field_args' ), 10 );
	}



	/**
	 * Replace plugin scripts with modified versions.
	 */
	public function replace_wcbcf_script() {
		// Replace frontend script, also removing dependency on Mailcheck script from the Brazilian Market plugin
		wp_deregister_script( 'woocommerce-extra-checkout-fields-for-brazil-front' );
		wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-front', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-extra-checkout-fields-for-brazil/frontend' ), array( 'jquery', 'jquery-mask' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		
		// Replace settings object for the Brazilian Market plugin
		$settings = FluidCheckout_Settings::instance()->get_option( 'wcbcf_settings' );
		$autofill = isset( $settings['addresscomplete'] ) ? 'yes' : 'no';
		wp_localize_script(
			'woocommerce-extra-checkout-fields-for-brazil-front',
			'bmwPublicParams',
			array(
				'state'                => esc_js( __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
				'required'             => esc_js( __( 'required', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
				// CHANGE: Added new parameter to hold label for optional fields
				'optional'             => esc_js( __( 'optional', 'woocommerce' ) ),
				// CHANGE: Always set mailcheck feature as disabled because we already provide this feature
				'mailcheck'            => 'no',
				// CHANGE: Maybe disable masked input when International phone number feature is enabled
				'maskedinput_phone'    => true === apply_filters( 'fc_compat_wcbcf_disable_marked_input_phone_feature', false ) ? 'no' : 'yes',
				'maskedinput'          => isset( $settings['maskedinput'] ) ? 'yes' : 'no',
				'person_type'          => absint( $settings['person_type'] ),
				'only_brazil'          => isset( $settings['only_brazil'] ) ? 'yes' : 'no',
				/* translators: %hint%: email hint */
				'suggest_text'         => esc_js( __( 'Did you mean: %hint%?', 'woocommerce-extra-checkout-fields-for-brazil' ) ),
			)
		);
	}



	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_brazilian_documents_validation_scripts() {
		// Bail if checkout validation class is not available
		if ( ! class_exists( 'FluidCheckout_Validation' ) ) { return; }

		// Bail if not checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Enqueue validation scripts
		FluidCheckout_Validation::instance()->enqueue_scripts_brazilian_documents_validation();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 * 
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings_checkout_validation_brazilian_documents( $settings ) {
		// Get Brazilian Market plugin settings
		$wcbcf_settings = FluidCheckout_Settings::instance()->get_option( 'wcbcf_settings' );

		// Add validation settings
		$settings = array_merge( $settings, array(
			'validateCPF'         => isset( $wcbcf_settings[ 'validate_cpf' ] ) ? 'yes' : 'no',
			'validateCNPJ'        => isset( $wcbcf_settings[ 'validate_cnpj' ] ) ? 'yes' : 'no',
		) );

		return $settings;
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
		// Bail if not on admin pages
		if ( ! is_admin() ) { return $translated; }

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
			'billing_postcode'         => array( 'priority' => 80, 'class' => array( 'form-row-first' ) ), // CEP validation class is dynamically added via JavaScript
			'billing_address_1'        => array( 'priority' => 90, 'class' => array( 'form-row-first', 'form-row-two-thirds' ) ),
			'billing_number'           => array( 'priority' => 100, 'class' => array( 'form-row-last', 'form-row-one-third' ) ),
			'billing_address_2'        => array( 'priority' => 110, 'class' => array( 'form-row-wide' ) ),
			'billing_neighborhood'     => array( 'priority' => 120, 'class' => array( 'form-row-first' ) ),
			'billing_city'             => array( 'priority' => 130, 'class' => array( 'form-row-first' ) ),
			'billing_state'            => array( 'priority' => 140, 'class' => array( 'form-row-last' ) ),

			'billing_persontype'       => array( 'priority' => 300, 'class' => array( 'form-row-wide' ) ),
			'billing_company'          => array( 'priority' => 310, 'class' => array( 'form-row-wide' ) ),
			'billing_cpf'              => array( 'priority' => 320, 'class' => array( 'form-row-first' ) ),
			'billing_rg'               => array( 'priority' => 330, 'class' => array( 'form-row-last' ) ),
			'billing_cnpj'             => array( 'priority' => 340, 'class' => array( 'form-row-first' ) ),
			'billing_ie'               => array( 'priority' => 350, 'class' => array( 'form-row-last' ) ),
			'billing_birthdate'        => array( 'priority' => 360, 'class' => array( 'form-row-first' ) ),
			'billing_sex'              => array( 'priority' => 370, 'class' => array( 'form-row-last' ) ),

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
			'shipping_city'            => array( 'priority' => 130, 'class' => array( 'form-row-first' ) ),
			'shipping_state'           => array( 'priority' => 140, 'class' => array( 'form-row-last' ) ),
		);

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
			'city'             => array( 'priority' => 130, 'class' => array( 'form-row-first' ) ),
			'state'            => array( 'priority' => 140, 'class' => array( 'form-row-last' ) ),

			'company'          => array( 'priority' => 60, 'class' => array( 'form-row-wide' ) ),
		);

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
	 * Add Brazilian documents validation classes to checkout field arguments.
	 */
	public function add_brazilian_documents_validation_classes( $fields ) {
		// Maybe add CPF validation class
		if ( array_key_exists( 'billing_cpf', $fields ) ) {
			$fields[ 'billing_cpf' ][ 'class' ] = array_key_exists( 'class', $fields[ 'billing_cpf' ] ) ? $fields[ 'billing_cpf' ][ 'class' ] : array();
			$fields[ 'billing_cpf' ][ 'class' ][] = 'validate-cpf';
		}

		// Maybe add CNPJ validation class
		if ( array_key_exists( 'billing_cnpj', $fields ) ) {
			$fields[ 'billing_cnpj' ][ 'class' ] = array_key_exists( 'class', $fields[ 'billing_cnpj' ] ) ? $fields[ 'billing_cnpj' ][ 'class' ] : array();
			$fields[ 'billing_cnpj' ][ 'class' ][] = 'validate-cnpj';
		}

		return $fields;
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
	 * Remove shipping company from fields to copy from billing address.
	 *
	 * @param   array  $shipping_copy_billing_field_keys  List of shipping field ids to copy from the billing address.
	 */
	public function remove_shipping_company_from_copy_billing_field_keys( $shipping_copy_billing_field_keys ) {
		if ( in_array( 'shipping_company', $shipping_copy_billing_field_keys ) ) {
			$shipping_copy_billing_field_keys = array_diff( $shipping_copy_billing_field_keys, array( 'shipping_company' ) );
		}
		return $shipping_copy_billing_field_keys;
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
		$skip_list[] = 'persontype';
		$skip_list[] = 'cnpj';
		$skip_list[] = 'ie';
		$skip_list[] = 'cpf';
		$skip_list[] = 'rg';
		$skip_list[] = 'company';

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
		// Get plugin settings
		$settings = FluidCheckout_Settings::instance()->get_option( 'wcbcf_settings' );
		
		$person_type = WC()->checkout()->get_value( 'billing_persontype' );

		// Maybe add legal person fields to skip list when individual person is enabled
		// $settings['person_type']: 1 = Individuals and Legal Person, 2 = Individual person only
		// $person_type: 1 = Individual Person
		if ( 2 == $settings[ 'person_type' ] || ( 1 == $settings[ 'person_type' ] && 1 == $person_type ) ) {
			$skip_list = array_merge( $skip_list, array( 'billing_cnpj', 'billing_ie' ) );
		}
		// Maybe add individual person fields to skip list when legal person is enabled
		// $settings['person_type']: 1 = Individuals and Legal Person, 3 = Legal person only
		// $person_type: 2 = Legal Person
		else if ( 3 == $settings[ 'person_type' ] || ( 1 == $settings[ 'person_type' ] && 2 == $person_type ) ) {
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
	public function maybe_add_substep_complete_billing_address_field_skip_list_by_person_type( $skip_list ) {
		// Get plugin settings
		$settings = FluidCheckout_Settings::instance()->get_option( 'wcbcf_settings' );

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

	/**
	 * Maybe set the billing address substep as incomplete if CPF or CNPJ field values are invalid.
	 *
	 * @param   bool   $is_substep_complete  Whether the step is to be considered complete or not.
	 */
	public function maybe_set_substep_incomplete_billing_address( $is_substep_complete ) {
		// Bail if required class does not exist
		if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil_Formatting' ) ) { return $is_substep_complete; }

		// Get Brazilian Market plugin settings
		$settings = FluidCheckout_Settings::instance()->get_option( 'wcbcf_settings' );

		// Bail if person type is disabled
		// 0 = None (person type field disabled)
		if ( ! isset( $settings[ 'person_type' ] ) || 0 == $settings[ 'person_type' ] ) { return $is_substep_complete; }

		// Bail if person type is enabled but invalid
		if ( 1 == $settings[ 'person_type' ] ) { // 1 = Individuals and Legal Person
			$person_type = WC()->checkout()->get_value( 'billing_persontype' );
			$allowed_person_types = array( 1, 2 );
			if ( ! in_array( $person_type, $allowed_person_types ) ) { return $is_substep_complete; }
		}

		// Maybe validate CPF
		// $settings['person_type']: 1 = Individuals and Legal Person, 2 = Individual person only
		// $person_type: 1 = Individual Person
		if ( ( 2 == $settings[ 'person_type' ] || ( 1 == $settings[ 'person_type' ] && 1 == $person_type ) ) && isset( $settings[ 'validate_cpf' ] ) ) {
			$billing_cpf = WC()->checkout()->get_value( 'billing_cpf' );
			if ( ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cpf( $billing_cpf ) ) {
				return false;
			}
		}
		// Maybe validate CNPJ
		// $settings['person_type']: 1 = Individuals and Legal Person, 3 = Legal person only
		// $person_type: 2 = Legal Person
		else if ( ( 3 == $settings[ 'person_type' ] || ( 1 == $settings[ 'person_type' ] && 2 == $person_type ) ) && isset( $settings[ 'validate_cnpj' ] ) ) {
			$billing_cnpj = WC()->checkout()->get_value( 'billing_cnpj' );
			if ( ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cnpj( $billing_cnpj ) ) {
				return false;
			}
		}

		return $is_substep_complete;
	}



	/**
	 * Add validation status classes to checkout fields args before outputting them to the page.
	 *
	 * @param   array   $args   Checkout field args.
	 * @param   string  $key    Field key.
	 * @param   mixed   $value  Field value.
	 *
	 * @return  array           Modified checkout field args.
	 */
	public function add_checkout_field_validation_status_classes( $args, $key, $value ) {
		// Bail if fields are not to be validated
		if ( 'billing_cpf' !== $key && 'billing_cnpj' !== $key ) { return $args; }

		// Bail if required class does not exist
		if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil_Formatting' ) ) { return $args; }

		// Get Brazilian Market plugin settings
		$settings = FluidCheckout_Settings::instance()->get_option( 'wcbcf_settings' );

		// Maybe convert the class argument to an array
		if ( is_string( $args[ 'class' ] ) ) {
			$args[ 'class' ] = explode( ' ', $args[ 'class' ] );
		}

		// Maybe set CPF field as invalid
		if ( 'billing_cpf' === $key && isset( $settings[ 'validate_cpf' ] ) ) {
			$billing_cpf = WC()->checkout()->get_value( 'billing_cpf' );
			if ( ! empty( $billing_cpf ) && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cpf( $billing_cpf ) ) {
				$args[ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $args[ 'class' ], array( 'woocommerce-invalid', 'woocommerce-invalid-cpf' ) );
			}
		}

		// Maybe set CNPJ field as invalid
		if ( 'billing_cnpj' === $key && isset( $settings[ 'validate_cnpj' ] ) ) {
			$billing_cnpj = WC()->checkout()->get_value( 'billing_cnpj' );
			if ( ! empty( $billing_cnpj ) && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cnpj( $billing_cnpj ) ) {
				$args[ 'class' ] = FluidCheckout_CheckoutFields::instance()->merge_form_field_class_args( $args[ 'class' ], array( 'woocommerce-invalid', 'woocommerce-invalid-cnpj' ) );
			}
		}

		return $args;
	}

}

FluidCheckout_WooCommerceExtraCheckoutFieldsForBrazil::instance();
