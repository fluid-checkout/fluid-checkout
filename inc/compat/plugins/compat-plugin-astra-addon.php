<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Astra Pro (by Brainstorm Force).
 */
class FluidCheckout_AstraAddon extends FluidCheckout {

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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Customer details element
		add_filter( 'fc_checkout_wrapper_inside_element_custom_attributes', array( $this, 'add_customer_details_element_attributes' ) );

		// Disable theme addon features
		add_filter( 'astra_get_option_array', array( $this, 'force_change_theme_options' ), 10, 3 );

		// Adds placeholder for modern input.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'label_fields_customization' ), 1100 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Modern input styles event handler
		wp_register_script( 'fc-compat-astra-addon-woo-common-input-event-handler', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/astra-addon/woo-common-input-event-handler' ), array(), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-astra-addon-woo-common-input-event-handler', 'window.addEventListener("load",function(){WooCommonInputEventHandler.init();})' );

		// Label as placeholder
		wp_register_script( 'astra-checkout-labels-as-placeholders', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/astra-addon/checkout-labels-as-placeholders' ), array( 'jquery', 'astra-addon-js' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-astra-addon-woo-common-input-event-handler' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if not using the modern input style
		if ( ! function_exists( 'astra_get_option' ) || 'modern' !== astra_get_option( 'woo-input-style-type' ) ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Force changing the theme addon feature options to avoid conflicts with Fluid Checkout.
	 */
	public function force_change_theme_options( $theme_options, $option, $default ) {
		// Disable Astra PRO checkout options
		$theme_options[ 'checkout-content-width' ] = 'default';
		$theme_options[ 'checkout-layout-type' ] = 'default';
		$theme_options[ 'two-step-checkout' ] = false;
		$theme_options[ 'checkout-coupon-display' ] = false;
		$theme_options[ 'checkout-persistence-form-data' ] = false;

		// Set display order notes option to `yes` to prevent it from removing the field from the checkout page.
		// Fluid Checkout option to show/hide the order notes field superseeds the theme option.
		$theme_options[ 'checkout-order-notes-display' ] = 'yes';

		// Remove order summary and payment section colors from the theme
		$theme_options[ 'order-summary-background-color' ] = '';
		$theme_options[ 'payment-option-content-background-color' ] = '';

		return $theme_options;
	}



	/**
	 * Change order comments placeholder and label, and set billing phone number to not required.
	 *
	 * @param array $fields checkout fields.
	 */
	public function label_fields_customization( $fields ) {
		// Bail if Astra functions are not available
		if ( ! function_exists( 'astra_get_option' ) ) { return $fields; }

		// COPIED from class ASTRA_Ext_WooCommerce_Markup

		// CHANGE: Use checkout request conditional from Fluid Checkout instead of `is_checkout`.
		if ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() && ! is_wc_endpoint_url( 'order-received' ) && 'modern' === astra_get_option( 'woo-input-style-type' ) ) {

			$field_types = array(
				'billing',
				'shipping',
				'account',
				'order',
			);

			foreach ( $field_types as $type ) {

				if ( isset( $fields[ $type ] ) && is_array( $fields[ $type ] ) ) {

					foreach ( $fields[ $type ] as $key => $field ) {
						// Add label as placeholder if the placeholder value is empty.
						if ( empty( $fields[ $type ][ $key ]['placeholder'] ) ) {
							$fields[ $type ][ $key ]['placeholder'] = $fields[ $type ][ $key ]['label'];

							// CHANGED: Add `*` for required fields.
							if ( isset( $fields[ $type ][ $key ]['required'] ) && $fields[ $type ][ $key ]['required'] ) {
								$fields[ $type ][ $key ]['placeholder'] .= ' *';
							}
						}

						// Remove screen-reader-text class from labels.
						if ( isset( $fields[ $type ][ $key ]['label_class'] ) ) {
							$fields[ $type ][ $key ]['label_class'] = array_diff( $fields[ $type ][ $key ]['label_class'], array( 'screen-reader-text' ) );
						}
					}
				}
			}
		}

		return $fields;

		// END - COPIED from class ASTRA_Ext_WooCommerce_Markup
	}



	/**
	 * Add custom attributes to the customer details element.
	 *
	 * @param   Array   $custom_attributes   HTML attributes.
	 */
	public function add_customer_details_element_attributes( $custom_attributes ) {
		// Bail if Astra functions are not available
		if ( ! function_exists( 'astra_get_option' ) ) { return $custom_attributes; }

		// Bail if option for label as placeholder is not enabled
		if ( true !== astra_get_option( 'checkout-labels-as-placeholders' ) ) { return $custom_attributes; }

		$custom_attributes[ 'id' ] = 'customer_details';

		return $custom_attributes;
	}

}

FluidCheckout_AstraAddon::instance();
