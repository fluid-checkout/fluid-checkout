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

		// Register assets
		add_filter( 'wp', array( $this, 'maybe_replace_plugin_scripts' ), 10 ); // Use 'wp' hook to override the plugin's assets registration within the 'woocommerce_billing_fields' hook

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'add_optional_fields_skip_fields' ), 10 );

		// Plugin fields visibility
		add_action( 'woocommerce_billing_fields', array( $this, 'hide_plugin_fields' ), 10 );
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

}

FluidCheckout_WooCommerceEUVatField::instance();
