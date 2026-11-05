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
		add_filter( 'fc_settings_effective_values', array( $this, 'disable_custom_enhanced_select_component' ), 10 );
		add_filter( 'fc_tools_settings', array( $this, 'change_enhanced_select_settings_args' ), 10 );
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
	 * Force disable the enhanced select fields feature when using Biteship.
	 *
	 * @param  array  $values  Effective settings values.
	 */
	public function disable_custom_enhanced_select_component( $values ) {
		$values = is_array( $values ) ? $values : array();
		$values[ 'fc_use_enhanced_select_components' ] = 'no';
		return $values;
	}

	/**
	 * Disable the enhanced select fields setting and explain why it is forced when using Biteship.
	 *
	 * @param   array  $settings  Admin settings args values.
	 */
	public function change_enhanced_select_settings_args( $settings ) {
		// Define the settings field description
		$description = __( 'The enhanced select fields feature is always disabled when using the Biteship plugin.', 'fluid-checkout' );

		return FluidCheckout_Settings::instance()->disable_settings_field( $settings, 'fc_use_enhanced_select_components', $description );
	}

}

FluidCheckout_Biteship::instance();
