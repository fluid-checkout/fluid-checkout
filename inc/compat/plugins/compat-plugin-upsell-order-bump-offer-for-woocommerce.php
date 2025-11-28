<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Upsell Funnel Builder for WooCommerce (by WP Swings).
 */
class FluidCheckout_UpsellOrderBumpOfferForWooCommerce extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Order bump section
		$this->maybe_move_order_bump_section();
	}



	/**
	 * Maybe move the order bump section.
	 */
	public function maybe_move_order_bump_section() {
		// Bail if required functions are not available
		if ( ! function_exists( 'wps_ubo_lite_retrieve_bump_location_details' ) ) { return; }

		// Bail if public class is not available
		$class_name = 'Upsell_Order_Bump_Offer_For_Woocommerce_Public';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get plugin class object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if object or its method is not available
		if ( ! is_object( $class_object ) || ! method_exists( $class_object, 'show_offer_bump' ) ) { return; }

		// Get plugin options
		$plugin_options = FluidCheckout_Settings::instance()->get_option( 'wps_ubo_global_options', array() );

		// Get order bump location
		$order_bump_location = ! empty( $plugin_options[ 'wps_ubo_offer_location' ] ) ? $plugin_options[ 'wps_ubo_offer_location' ] : '_after_payment_gateways';

		// Get new position args
		$position_args = $this->get_order_bump_section_position_args( $order_bump_location );

		// Remove section from default position
		$location_details = wps_ubo_lite_retrieve_bump_location_details( $order_bump_location );
		if ( isset( $location_details[ 'hook' ] ) && isset( $location_details[ 'priority' ] ) ) {
			remove_action( $location_details[ 'hook' ], array( $class_object, 'show_offer_bump' ), $location_details[ 'priority' ] );
		}

		// Add order bump to the new position
		add_action( $position_args[ 'hook' ], array( $class_object, 'show_offer_bump' ), $position_args[ 'priority' ] );
	}

	/**
	 * Maybe output order bump section in payment step.
	 *
	 * @param   string  $step_id  The step ID.
	 */
	public function maybe_output_order_bump_in_step_payment( $step_id ) {
		// Bail if not at payment step
		if ( 'payment' !== $step_id ) { return; }

		// Get plugin class object
		$class_name = 'Upsell_Order_Bump_Offer_For_Woocommerce_Public';
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if object not found or method not available
		if ( ! is_object( $class_object ) || ! method_exists( $class_object, 'show_offer_bump' ) ) { return; }

		// Output order bump section
		$class_object->show_offer_bump();
	}



	/**
	 * Get custom hook and priority for order bump position.
	 *
	 * @param   string  $position  The order bump section position.
	 */
	public function get_order_bump_section_position_args( $position ) {
		// Define custom positions
		$position_args = array(
			'_before_order_summary'      => array( 'hook' => 'fc_checkout_before_order_review', 'priority' => 5 ),
			'_before_payment_gateways'   => array( 'hook' => 'fc_checkout_payment', 'priority' => 5 ),
			'_after_payment_gateways'    => array( 'hook' => 'fc_checkout_payment', 'priority' => 85 ),
			'_before_place_order_button' => array( 'hook' => 'fc_place_order', 'priority' => 5 ),
		);

		// Maybe set the default position
		if ( ! isset( $position_args[ $position ] ) ) {
			$position = '_after_payment_gateways';
		}

		return apply_filters( 'fc_upsell_order_bump_section_position_args', $position_args[ $position ] );
	}

}

FluidCheckout_UpsellOrderBumpOfferForWooCommerce::instance();
