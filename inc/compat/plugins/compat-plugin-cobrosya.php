<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce CobrosYA.com (by CobrosYA.com).
 */
class FluidCheckout_CobrosYA extends FluidCheckout {

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
		// Billing fields
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_add_billing_fields' ), 10 );

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields' ), 10 );
	}



	/**
	 * Maybe add billing fields required by the payment gateways.
	 */
	public function maybe_add_billing_fields( $field_groups ) {
		// Bail if function is not available
		if ( ! function_exists( 'woocommerce_gateway_CobrosYA_checkout_fields' ) ) { return $field_groups; }

		// Define payment gateways which require additional fields
		$gateways = array(
			'cyaabitab',
			'cyaanda',
			'cyabandes',
			'cyabanred',
			'cyabbva',
			'cyacabal',
			'cyacreditel',
			'cyadiners',
			'cyadiscover',
			'cyaebrou',
			'cyaheritage',
			'cyahsbc',
			'cyaitau',
			'cyalider',
			'cyamastercarddesc',
			'cyamastercarddesc2',
			'cyamastercard',
			'cyaoca',
			'cyapasscard',
			'cyaredpagos',
			'cyasantander',
			'cyascotiabank',
			'cyatarjetad',
			'cyavisadesc',
			'cyavisadesc2',
			'cyavisa',
		);

		// Get list of enabled gateways
		$enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways_ids = array_keys( $enabled_gateways );

		// Get ids for enabled payment gateways that require additional fields
		$enabled_gateways_intersect = array_intersect( $gateways, $enabled_gateways_ids );
		
		// Bail if no payment gateways that require additional fields are enabled
		if ( empty( $enabled_gateways_intersect ) ) { return $field_groups; }

		// Add additional fields
		$field_groups = woocommerce_gateway_CobrosYA_checkout_fields( $field_groups );

		return $field_groups;
	}



	/**
	 * Prevent hiding some optional fields behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields( $skip_list ) {
		$skip_list[] = 'billing_consumo_final';
		$skip_list[] = 'billing_rut';
		$skip_list[] = 'billing_razon_social';

		return $skip_list;
	}

}

FluidCheckout_CobrosYA::instance();
