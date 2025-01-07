<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Germanized for WooCommerce Pro (by Vendidero)
 */
class FluidCheckout_WooCommerceGermanizedPRO extends FluidCheckout {

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
		// Bail if Germanized Lite not active
		if ( ! is_plugin_active( 'woocommerce-germanized/woocommerce-germanized.php' ) ) { return; }

		// VAT validation
		$this->vat_validation_hooks();
	}

	/**
	 * Initialize VAT validation hooks.
	 */
	public function vat_validation_hooks() {
		// Bail if VAT helper class not available
		if ( ! class_exists( 'WC_GZDP_VAT_Helper' ) ) { return; }

		// Maybe add VAT validation hooks
		if ( 'yes' === get_option( 'woocommerce_gzdp_enable_vat_check' ) ) {
			// Substep review text
			add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list' , array( $this, 'add_vat_id_field_step_review_text_skip_list' ), 10 );
			add_filter( 'fc_substep_text_billing_address_field_keys_skip_list' , array( $this, 'add_vat_id_field_step_review_text_skip_list' ), 10 );
		}
	}


	
	/**
	 * Add address save checkbox fields to the substep review text skip list.
	 *
	 * @param   array  $field_keys_skip_list  The list of fields to skip adding to the substep review text.
	 */
	public function add_vat_id_field_step_review_text_skip_list( $field_keys_skip_list ) {
		// Bail if not an array
		if ( ! is_array( $field_keys_skip_list ) ) { return $field_keys_skip_list; }

		$field_keys_skip_list = array_merge( $field_keys_skip_list, array(
			'shipping_vat_id',
			'billing_vat_id',
		) );

		return $field_keys_skip_list;
	}

}

FluidCheckout_WooCommerceGermanizedPRO::instance();
