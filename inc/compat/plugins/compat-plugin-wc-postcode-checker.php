<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce NL Postcode Checker (by WP Overnight).
 */
class FluidCheckout_WCPostcodeChecker extends FluidCheckout {

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

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'add_optional_fields_skip_fields' ), 10, 2 );

		// Substep review text
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_shipping' ), 100 );
		add_filter( 'fc_substep_text_billing_address_field_keys_skip_list', array( $this, 'change_substep_text_extra_fields_skip_list_billing' ), 100 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'wpo-wcnlpc', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wc-postcode-checker/wc-postcode-checker' ), array( 'jquery', 'wc-address-i18n' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Adds custom fields from the postcode checker plugin to the list of optional fields to skip hiding behind a link button.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from hiding behind a link button.
	 */
	public function add_optional_fields_skip_fields( $skip_field_keys ) {
		$fields_keys = array(
			'address_1',
			'address_2',
			'street_name',
			'house_number',
			'house_number_suffix',

			'shipping_address_1',
			'shipping_address_2',
			'shipping_street_name',
			'shipping_house_number',
			'shipping_house_number_suffix',

			'billing_address_1',
			'billing_address_2',
			'billing_street_name',
			'billing_house_number',
			'billing_house_number_suffix',
		);

		return array_merge( $skip_field_keys, $fields_keys );
	}



	/**
	 * Change shipping extra fields to skip for the substep review text.
	 *
	 * @param   array  $skip_list  List of fields to skip adding to the substep review text.
	 */
	function change_substep_text_extra_fields_skip_list_shipping( $skip_list ) {
		$skip_list = array_merge( $skip_list, array(
			'shipping_house_number',
			'shipping_house_number_suffix',
			'shipping_street_name',
		) );
		return $skip_list;
	}

	/**
	* Change billing extra fields to skip for the substep review text.
	*
	* @param   array  $skip_list  List of fields to skip adding to the substep review text.
	*/
	function change_substep_text_extra_fields_skip_list_billing( $skip_list ) {
		$skip_list = array_merge( $skip_list, array(
			'billing_house_number',
			'billing_house_number_suffix',
			'billing_street_name',
		) );

		return $skip_list;
	}

}

FluidCheckout_WCPostcodeChecker::instance();
