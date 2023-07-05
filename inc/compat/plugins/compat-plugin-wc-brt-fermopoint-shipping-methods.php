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

		// Enqueue assets
		// add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// Move fermopoint details section
		remove_action( 'woocommerce_review_order_after_shipping', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'add_maps_or_list' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'add_maps_or_list' ), 10 );

		// Output hidden fields
		remove_action( 'woocommerce_review_order_before_submit', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'my_custom_checkout_field' ) );
		add_action( 'fc_checkout_after', array( WC_BRT_FermoPoint_Shipping_Methods::instance()->core, 'my_custom_checkout_field' ) );

		// Hidden fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields' ), 10 );
		add_filter( 'woocommerce_form_field', array( $this, 'add_optional_form_field_link_button' ), 100, 4 );
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
	 * Prevent hiding optional fields.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields( $skip_list ) {
		$skip_list = array_merge( $skip_list, array(
			'wc_brt_fermopoint-selected_pudo',
			'wc_brt_fermopoint-pudo_id',
		) );
		return $skip_list;
	}

	/**
	 * Get the checkout fields args.
	 *
	 * @param   string  $field  Field html markup to be changed.
	 * @param   string  $key    Field key.
	 * @param   array   $args   Field args.
	 * @param   mixed   $value  Value of the field. Defaults to `null`.
	 */
	public function add_optional_form_field_link_button( $field, $key, $args, $value ) {
		// Bail if not targetted fields
		$target_fields = array(
			'wc_brt_fermopoint-selected_pudo',
			'wc_brt_fermopoint-pudo_id',
		);
		if ( ! in_array( $key, $target_fields ) ) { return $field; }

		// Bail if field value is not empty
		if ( ! empty( $value ) ) { return $field; }

		
		// Replace value with session value
		$field_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( $key );
		$field = str_replace( 'value=""', 'value="'. esc_attr( $field_value ) .'"', $field );

		return $field;
	}

}

FluidCheckout_WC_BRT_FermopointShippingMethods::instance();
