<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Woocommerce GUS/Regon (by gotoweb.pl).
 */
class FluidCheckout_WoocommerceGUS extends FluidCheckout {

	/**
	 * VAT field key from the plugin.
	 */
	public const VAT_FIELD_KEY = 'gus_nip_value';



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
		// VAT field args
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_vat_field_priority' ), 10, 3 );
	}



	/**
	 * Maybe change priority for the VAT field from the plugin.
	 *
	 * @param  array  $field_groups   The checkout fields.
	 */
	public function maybe_change_vat_field_priority( $field_groups ) {
		// Bail if VAT field from the plugin is not available
		if ( ! array_key_exists( 'billing', $field_groups ) || ! array_key_exists( self::VAT_FIELD_KEY, $field_groups[ 'billing' ] ) ) { return $field_groups; }

		// Set new priority
		$field_groups[ 'billing' ][ self::VAT_FIELD_KEY ][ 'priority' ] = 230;

		return $field_groups;
	}

}

FluidCheckout_WoocommerceGUS::instance();
