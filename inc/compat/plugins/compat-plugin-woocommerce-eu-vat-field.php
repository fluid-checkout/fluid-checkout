<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce EU Vat & B2B (by Lagudi Domenico).
 */
class FluidCheckout_WooCommerceEUVatField extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Bail if the VAT field is not enabled
		if ( ! $this->is_vat_field_enabled() ) { return; }

		// Checkout validation hooks
		$this->checkout_validation_hooks();

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Register assets
		add_filter( 'wp', array( $this, 'maybe_replace_plugin_scripts' ), 10 ); // Use 'wp' hook to override the plugin's assets registration within the 'woocommerce_billing_fields' hook

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'add_optional_fields_skip_fields' ), 10 );

		// Plugin fields visibility
		add_action( 'woocommerce_billing_fields', array( $this, 'hide_plugin_fields' ), 10 );

		// Hidden fields
		add_action( 'woocommerce_checkout_billing', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Hidden fields fragment
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_billing_hidden_fields_fragment' ), 10 );

		// Maybe set substep as incomplete
		// add_filter( 'fc_is_substep_complete_billing_address', array( $this, 'maybe_set_substep_incomplete_billing_address' ), 10 );
	}

	/**
	 * Add or remove checkout validation hooks.
	 */
	public function checkout_validation_hooks() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if class is not available
		$class_name = 'WCEV_CheckoutPage';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if class object is not found
		if ( ! $class_object ) { return; }

		// VAT validation
		remove_action( 'woocommerce_checkout_update_order_review', array( $class_object, 'validate_vat_field_and_remove_tax' ), 10 );
	}



	/**
	 * Check if the VAT field from the plugin is enabled.
	 */
	public function is_vat_field_enabled() {
		global $wcev_vat_field_model;
		$is_enabled = false;

		// Bail if required object or its method is not available
		if ( ! $wcev_vat_field_model || ! method_exists( $wcev_vat_field_model, 'get_all_options' ) ) { return $is_enabled; }

		// Get plugin options
		$options = $wcev_vat_field_model->get_all_options();

		// Check if the the VAT field is enabled
		if ( array_key_exists( 'disable_eu_vat_field', $options ) && ! $options[ 'disable_eu_vat_field' ] ) {
			$is_enabled = true;
		}

		return $is_enabled;
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Add validation script
		wp_register_script( 'fc-checkout-validation-woocommerce-eu-vat-field', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-eu-vat-field/checkout-validation-woocommerce-eu-vat-field' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-woocommerce-eu-vat-field', 'window.addEventListener("load",function(){CheckoutValidationWooCommerceEUVatField.init(fcSettings.checkoutValidationWooCommerceEUVatField);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-woocommerce-eu-vat-field' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}

	/**
	 * Replace plugin scripts with modified version.
	 */
	public function maybe_replace_plugin_scripts() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Plugin's scripts
		wp_register_script( 'wcev-field-checkout-page', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-eu-vat-field/frontend-checkout-page' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'wcev-field-visibility-managment', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-eu-vat-field/frontend-eu-vat-field-visibility' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		global $wcev_text_helper;

		// Bail if object or its method is not available
		if ( ! $wcev_text_helper || ! method_exists( $wcev_text_helper , 'get_texts' ) ) { return $settings; }

		// Get validation messages from the plugin
		$messages = $wcev_text_helper->get_texts();

		// Add validation settings
		$settings[ 'checkoutValidationWooCommerceEUVatField' ] = array(
			'validationMessages'  => array(
				'vat_not_valid' => ! empty( $messages[ 'error_text' ] ) ? $messages[ 'error_text' ] : __( 'Vat number is invalid', 'woocommerce-eu-vat-field' ),
				'vat_not_unique' => ! empty( $messages[ 'unique_vat_number_check_fail_text' ] ) ? $messages[ 'unique_vat_number_check_fail_text' ] : __( 'Vat number has been already associated to another user.', 'woocommerce-eu-vat-field' ),
			),
		);

		return $settings;
	}



	/**
	 * Adds custom fields from the postcode checker plugin to the list of optional fields to skip hiding behind a link button.
	 *
	 * @param  array  $skip_list  Checkout field keys to skip from hiding behind a link button.
	 */
	public function add_optional_fields_skip_fields( $skip_list ) {
		$fields_keys = array(
			'billing_eu_vat',
			'billing_request_eu_vat',
			'billing_company',
			'billing_business_consumer_selector',
			'billing_it_sid_pec',
			'billing_it_codice_fiscale',
			'billing_es_nif_nie',
			'billing_gr_tax_office',
			'billing_gr_business_activity',
			'billing_sk_company_id',
			'billing_sk_company_dic',
			'billing_cz_company_id',
		);

		return array_merge( $skip_list, $fields_keys );
	}



	/**
	 * Hide fields for which visibility is toggled through plugin's JS.
	 * Required to stop the fields from appearing on the initial page load
	 * before the first `update_checkout` event is triggered.
	 *
	 * @param  array  $skip_list  Checkout field keys to skip from hiding behind a link button.
	 */
	public function hide_plugin_fields( $fields ) {
		// Hide the plugin fields (not remove them)
		$fields_to_hide = array(
			'billing_eu_vat',
			'billing_request_eu_vat',
			'billing_company',
			'billing_it_sid_pec',
			'billing_it_codice_fiscale',
			'billing_es_nif_nie',
			'billing_gr_tax_office',
			'billing_gr_business_activity',
			'billing_sk_company_id',
			'billing_sk_company_dic',
			'billing_cz_company_id',
		);

		// Add CSS class to hide the fields
		foreach ( $fields_to_hide as $field_key ) {
			if ( isset( $fields[ $field_key ] ) ) {
				$fields[ $field_key ][ 'class' ][] = 'fc-woocommerce-eu-vat-field-hidden';
			}
		}

		return $fields;
	}



	/**
	 * Check if the VAT number is valid.
	 *
	 * @param  string  $vat_number  The VAT number to validate.
	 */
	public function is_vat_number_valid( $vat_number ) {
		global $wcev_vat_field_model;
		$is_valid = true;

		// Bail if required object or its method is not available
		if ( ! $wcev_vat_field_model || ! method_exists( $wcev_vat_field_model, 'validate_vat' ) ) { return $is_valid; }

		// Get billing country
		$billing_country = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'billing_country' );

		// Maybe validate VAT number
		if ( $billing_country ) {
			// Validate VAT number
			$is_valid = $wcev_vat_field_model->validate_vat( $billing_country, $vat_number );
		}

		return $is_valid;
	}

	/**
	 * Check if the VAT number is unique.
	 *
	 * @param  string  $vat_number  The VAT number to validate.
	 */
	public function is_vat_number_unique( $vat_number ) {
		global $wcev_vat_field_model;
		global $wcev_customer_model;
		$is_unique = true;

		// Bail if required objects or their methods are not available
		if ( ! $wcev_vat_field_model || ! method_exists( $wcev_vat_field_model, 'get_all_options' ) ) { return $is_unique; }
		if ( ! $wcev_customer_model || ! method_exists( $wcev_customer_model, 'is_vat_number_already_associated_to_a_customer' ) ) { return $is_unique; }

		// Get plugin options
		$options = $wcev_vat_field_model->get_all_options();

		// Bail if uniqueness check is not enabled in the plugin options
		if ( empty( $options[ 'enable_uniqueness_check' ] ) ) { return $is_unique; }

		if ( $vat_number && $wcev_customer_model->is_vat_number_already_associated_to_a_customer( $vat_number ) ) {
			$is_unique = false;
		}

		return $is_unique;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields() {
		// Get entered VAT number
		$field_key = 'billing_eu_vat';
		$vat_number = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( $field_key );

		error_log(print_r('vat numb: '. $vat_number, true));

		// Validate VAT number
		$is_valid = $this->is_vat_number_valid( $vat_number );
		$is_unique = $this->is_vat_number_unique( $vat_number );


		error_log(print_r('$is_valid: ' . $is_valid, true));

		// Output custom hidden fields
		echo '<div id="woocommerce-eu-vat-field-custom_checkout_fields" class="form-row fc-no-validation-icon">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" name="woocommerce-eu-vat-field-is-valid" value="'. esc_attr( $is_valid ) .'" class="woocommerce-eu-vat-field-is-valid">';
		echo '<input type="hidden" name="woocommerce-eu-vat-field-is-unique" value="'. esc_attr( $is_unique ) .'" class="woocommerce-eu-vat-field-is-unique">';
		echo '</div>';
		echo '</div>';
	}



	/**
	 * Add hidden fields as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_billing_hidden_fields_fragment( $fragments ) {
		// Get custom hidden fields HTML
		ob_start();
		$this->output_custom_hidden_fields();
		$html = ob_get_clean();

		// Add fragment
		$fragments[ '#woocommerce-eu-vat-field-custom_checkout_fields' ] = $html;
		return $fragments;
	}



	/**
	 * Maybe set the billing address substep step as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_billing_address( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Get field values
		$vat_number = WC()->checkout->get_value( 'billing_eu_vat' );

		// Validate VAT number
		$is_valid = $this->is_vat_number_valid( $vat_number );

		// Maybe set step as incomplete
		if ( ! $is_valid ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}

}

FluidCheckout_WooCommerceEUVatField::instance();
