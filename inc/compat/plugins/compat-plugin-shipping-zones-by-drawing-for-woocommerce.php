<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Shipping Zones by Drawing for WooCommerce (by Arosoft.se).
 */
class FluidCheckout_ShippingZonesByDrawingForWooCommerce extends FluidCheckout {

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

		// Plugin settings
		add_filter( 'szbd_section1_settings', array( $this, 'change_map_position_settings_labels' ), 10, 1 );

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'add_optional_fields_skip_fields' ), 10, 2 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Map section position
		$this->map_position_hooks();
	}

	/**
	 * Add or remove hooks for map position.
	 */
	public function map_position_hooks() {
		// Bail if plugin class is not available
		if ( ! class_exists( 'SZBD' ) ) { return; }

		// Get class object
		$class_object = SZBD::instance();

		// Get map placement option value
		// Use `get_option` directly to match how the plugin uses its settings
		$placement = get_option( 'szbd_precise_address', 'no' ) != 'no' ? get_option( 'szbd_map_placement', 'before_payment' ) : 'none';

		switch ($placement) {
			case 'before_details':
				remove_action( 'woocommerce_checkout_before_customer_details', array( $class_object, 'insert_to_checkout' ), 10 );
				add_action( 'fc_before_substep_fields_shipping_address', array( $class_object, 'insert_to_checkout' ), 10 );
				break;
			case 'after_order_notes':
			case 'after_billing_form':
				remove_action( 'woocommerce_after_order_notes', array( $class_object, 'insert_to_checkout' ), 99 );
				remove_action( 'woocommerce_after_checkout_billing_form', array( $class_object, 'insert_to_checkout' ), 99 );
				add_action( 'fc_after_substep_fields_shipping_address', array( $class_object, 'insert_to_checkout' ), 99 );
				break;
		}
	}



	/**
	 * Change map position settings labels, explaining where the map is will be displayed when using Fluid Checkout.
	 *
	 * @param  array  $settings     Settings.
	 */
	public function change_map_position_settings_labels( $settings ) {
		foreach ( $settings as $index => $setting_args ) {
			// Skip if not the map position setting
			if ( ! array_key_exists( 'id', $setting_args ) || 'szbd_map_placement' !== $setting_args[ 'id' ] ) { continue; }

			// Skip if options attribute are not available
			if ( ! array_key_exists( 'options', $setting_args ) ) { continue; }

			// Change labels for options
			foreach ( $setting_args[ 'options' ] as $option_key => $option_label ) {
				switch ( $option_key ) {
					case 'before_details':
						$settings[ $index ][ 'options' ][ $option_key ] = __( 'Before Shipping Address', 'fluid-checkout' ) . ' ' . sprintf( __( '(originally "%s")',  'fluid-checkout' ), $settings[ $index ][ 'options' ][ $option_key ] );
						break;
					case 'after_order_notes':
					case 'after_billing_form':
						$settings[ $index ][ 'options' ][ $option_key ] = __( 'After Shipping Address', 'fluid-checkout' ) . ' ' . sprintf( __( '(originally "%s")',  'fluid-checkout' ), $settings[ $index ][ 'options' ][ $option_key ] );
						break;
				}
			}
		}

		return $settings;
	}



	/**
	 * Adds fields to skip hiding behind a link button.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from hiding behind a link button.
	 */
	public function add_optional_fields_skip_fields( $skip_field_keys ) {
		$fields_keys = array(
			'szbd-picked',
		);

		return array_merge( $skip_field_keys, $fields_keys );
	}

}

FluidCheckout_ShippingZonesByDrawingForWooCommerce::instance();
