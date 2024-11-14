<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: Plugin WooCommerce Checkout Manager by Quadlayers enabled.
 */
class FluidCheckout_AdminNotices_WooCommmerceCheckoutManager_Enabled extends FluidCheckout {

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
		add_action( 'fc_admin_notices', array( $this, 'add_notice' ), 10 );
	}



	/**
	 * Check if component is activated on a single install or network wide.
	 */
	public function is_component_activated() {
		$plugin_path_name = 'woocommerce-checkout-manager/woocommerce-checkout-manager.php';

		// Get plugin file path
		$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . $plugin_path_name;
		
		// Bail if plugin file does not exist
		if ( ! file_exists( $plugin_file ) ) { return false; }
		
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( $plugin_path_name ) && class_exists( 'QuadLayers\WOOCCM\Plugin' );
	}



	/**
	 * Add notice.
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'manage_options' ) ) { return $notices; }

		// Bail if component is not activated
		if ( ! $this->is_component_activated() ) { return $notices; }

		// Get link to the documentation
		$docs_link = 'https://fluidcheckout.com/docs/compat-plugin-woocommerce-checkout-manager/';

		$notices[] = array(
			'name'           => 'woocommerce_checkout_manager_enabled',
			'title'          => __( 'The plugin WooCommerce Checkout Manager by Quadlayers is not compatibile with Fluid Checkout', 'fluid-checkout' ),
			'description'    => sprintf( __( 'Some features of WooCommerce Checkout Manager by Quadlayers might not work as expected when using it with Fluid Checkout. Read <a href="%s">our documentation</a> for more information and alternative solutions.', 'fluid-checkout' ), $docs_link ),
			'dismissable'    => true,
			'error'          => true,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary">%s</a>', $docs_link, __( 'Read documentation', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_WooCommmerceCheckoutManager_Enabled::instance();
