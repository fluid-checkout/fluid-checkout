<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Carrier Agents (by Markup.fi).
 */
class FluidCheckout_WooCarrierAgents extends FluidCheckout {

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
		// Shipping methods hooks
		add_filter( 'woo_carrier_agents_search_output', array( $this, 'maybe_change_postcode_search_placement' ), 10 );
	}



	/**
	 * Maybe change postcode search placement to inside the shipping methods.
	 * 
	 * @param   array  $hooks  Action hooks for the postcode search.
	 */
	public function maybe_change_postcode_search_placement( $hooks ) {
		// `$hooks` needs to be an array
		if ( ! is_array( $hooks ) ) { $hooks = array(); }

		// Remove postcode search from the order summary section if it exists
		if ( isset( $hooks['woocommerce_checkout_order_review'] ) ) {
			unset( $hooks['woocommerce_checkout_order_review'] );
		}
		
		// Add postcode search to the shipping methods section if it's not already there
		if ( ! in_array( 'fc_shipping_methods_after_packages_inside', $hooks ) ) {
			// Target hook name and priority
			$hooks['fc_shipping_methods_after_packages_inside'] = 10;
		}
		
		return $hooks;
	}

}

FluidCheckout_WooCarrierAgents::instance();
