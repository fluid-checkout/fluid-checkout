<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: BRT Fermopoint (by BRT)
 */
class FluidCheckout_WC_BRT_FermopointShippingMethods extends FluidCheckout {

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
		// Bail if Fermopoint classes are not available
		if ( ! class_exists( 'WC_BRT_FermoPoint_Shipping_Methods' ) || ! WC_BRT_FermoPoint_Shipping_Methods::instance() || ! WC_BRT_FermoPoint_Shipping_Methods::instance()->core ) { return; }

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Move fermopoint details section
		remove_action( 'woocommerce_review_order_after_shipping', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'add_maps_or_list' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'add_maps_or_list' ), 10 );

		// Output hidden fields
		remove_action( 'woocommerce_review_order_before_submit', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'my_custom_checkout_field' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );
	}
	



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'wc_brt_fermopoint_shipping_methods_js', self::$directory_url . 'js/compat/plugins/wc-brt-fermopoint-shipping-methods/wc_brt_fermopoint_shipping_methods_js' . self::$asset_version . '.js', array( 'jquery' ), NULL );
	}



	/**
	 * Add maps or list output from the plugin, replacing `tr` elements with `div`.
	 */
	public function add_maps_or_list() {
		// Get the maps or list output from the plugin
		ob_start();
		WC_BRT_FermoPoint_Shipping_Methods::instance()->core->add_maps_or_list();
		$html = ob_get_clean();

		// Replace `tr` elements with `div`
		$replace = array(
			'<tr' => '<div',
			'</tr' => '</div',
			'<td' => '<div',
			'</td' => '</div',
		);
		$html = str_replace( array_keys( $replace ), array_values( $replace ), $html );

		// Output
		echo $html;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields( $checkout ) {
		// Get fields values from session
		$pudo_data = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'wc_brt_fermopoint-selected_pudo' );
		$pudo_id = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'wc_brt_fermopoint-pudo_id' );

		// CHANGE: Remove `display: none` to keep the custom fields section visible as it is needed to display the validation message
		echo '<div id="wc_brt_fermopoint-custom_checkout_fields">';
		echo '<input type="hidden" id="wc_brt_fermopoint-selected_pudo" name="wc_brt_fermopoint-selected_pudo" value="'. esc_attr( $pudo_data ) .'">';
		echo '<input type="hidden" id="wc_brt_fermopoint-pudo_id" name="wc_brt_fermopoint-pudo_id" value="'. esc_attr( $pudo_id ) .'">';
		echo '</div>';
	}

	/**
	 * Add settings to the plugin settings JS object for the checkout validation.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_checkout_validation( $settings ) {
		// Get current values
		$current_form_row_selector = array_key_exists( 'formRowSelector', $settings ) ? $settings[ 'formRowSelector' ] : '';
		$current_validate_field_selector = array_key_exists( 'validateFieldsSelector', $settings ) ? $settings[ 'validateFieldsSelector' ] : '';
		$current_reference_node_selector = array_key_exists( 'referenceNodeSelector', $settings ) ? $settings[ 'referenceNodeSelector' ] : '';
		// $current_always_validate_selector = array_key_exists( 'alwaysValidateFieldsSelector', $settings ) ? $settings[ 'alwaysValidateFieldsSelector' ] : '';

		// Prepend new values to existing settings
		$settings[ 'formRowSelector' ] = '#wc_brt_fermopoint-custom_checkout_fields' . ( ! empty( $current_form_row_selector ) ? ', ' : '' ) . $current_form_row_selector;
		$settings[ 'validateFieldsSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		// $settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="wc_brt_fermopoint-pudo_id"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}

}

FluidCheckout_WC_BRT_FermopointShippingMethods::instance();
