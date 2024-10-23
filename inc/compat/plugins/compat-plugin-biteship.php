<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Biteship for WooCommerce (by Biteship).
 */
class FluidCheckout_Biteship extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'replace_enqueue_assets' ), 5 );

		// Enhanced select fields
		add_filter( 'pre_option_fc_use_enhanced_select_components', array( $this, 'disable_custom_enhanced_select_component' ), 10, 3 );
	}



	/**
	 * Register assets.
	 */
	public function replace_enqueue_assets() {
		// Scripts
		wp_deregister_script( 'biteship' );
		wp_enqueue_script( 'biteship', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/biteship/biteship-public' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Change the option for the place order position to always `below_order_summary` when using Germanized.
	 *
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function disable_custom_enhanced_select_component( $pre_option, $option, $default ) {
		return 'no';
	}

}

FluidCheckout_Biteship::instance();
