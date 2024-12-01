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

		// Order received page template
		add_filter( 'fc_enable_checkout_page_template', array( $this, 'maybe_disable_checkout_page_template_for_order_received_page' ), 300 );
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



	/**
	 * Check if the current page is Elementor's custom order received page.
	 */
	public function is_custom_order_received_page() {
		// Initialize variable
		$is_custom_order_received_page = false;

		// Get Elementor's customer order received page ID
		$elementor_order_received_page_id = get_option( 'elementor_woocommerce_purchase_summary_page_id' );

		// Bail if Elementor's customer order received page ID is not set
		if ( empty( $elementor_order_received_page_id ) ) { return $is_custom_order_received_page; }

		// Bail if not on Elementor's custom order received page
		if ( ! is_page( $elementor_order_received_page_id ) ) { return $is_custom_order_received_page; }

		// Otherwise, it is the custom order received page
		return true;
	}



	/**
	 * Maybe disable the checkout page template on the custom order received page from this plugin.
	 */
	public function maybe_disable_checkout_page_template_for_order_received_page( $is_enabled ) {
		// Bail if not on the custom order received page
		if ( ! $this->is_custom_order_received_page() ) { return $is_enabled; }

		// Otherwise, disable the custom checkout page template
		return false;
	}

}

FluidCheckout_ElementorPRO::instance(); 
