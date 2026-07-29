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
	 * @param  mixed   $pre_option   The value to return instead of the option value.
	 * @param  string  $option       Option name.
	 * @param  mixed   $default      The fallback value to return if the option does not exist.
	 */
	public function disable_custom_enhanced_select_component( $pre_option, $option, $default ) {
		return 'no';
	}

	/**
	 * Disable the enhanced select fields setting and explain why it is forced when using Biteship.
	 *
	 * @param   array  $settings  Admin settings args values.
	 */
	public function change_enhanced_select_settings_args( $settings ) {
		// Iterate settings
		foreach ( $settings as $key => $setting_args ) {
			// Skip settings other than enhanced select fields
			if ( ! array_key_exists( 'id', $setting_args ) || 'fc_use_enhanced_select_components' !== $setting_args[ 'id' ] ) { continue; }

			// Disable enhanced select fields setting and change description explaining why it was disabled
			$setting_args[ 'disabled' ] = true;
			$setting_args[ 'custom_attributes' ][ 'disabled' ] = true;
			$setting_args[ 'desc' ] = __( 'The enhanced select fields feature is always disabled when using the Biteship plugin.', 'fluid-checkout' );
			unset( $setting_args[ 'desc_tip' ] );
			$settings[ $key ] = $setting_args;
		}

		return $settings;
	}

}

FluidCheckout_Biteship::instance();
