<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Elementor PRO (by Elementor).
 */
class FluidCheckout_ElementorPRO extends FluidCheckout {

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
		// Replace widgets
		add_action( 'elementor/widgets/register', array( $this, 'unregister_widgets' ), 100 );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 110 );
	}



	/**
	 * Unregister widgets.
	 *
	 * @param   \Elementor\Widgets_Manager  $widgets_manager  The widgets manager.
	 */
	public function unregister_widgets( $widgets_manager ) {
		$widgets_manager->unregister( 'woocommerce-checkout-page' );
	}



	/**
	 * Register widgets.
	 * 
	 * @param   \Elementor\Widgets_Manager  $widgets_manager  The widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		require_once( self::$directory_path . 'inc/compat/plugins/elementor-pro/widgets/woocommerce/checkout.php' );
		$widgets_manager->register( new FluidCheckout_ElementorPRO_Checkout() );
	}

}

FluidCheckout_ElementorPRO::instance(); 
