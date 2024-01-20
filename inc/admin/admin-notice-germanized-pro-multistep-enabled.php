<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: multistep feature from Germanized PRO plugin enabled.
 */
class FluidCheckout_AdminNotices_GermanizedPRO_MultistepFeatureEnabled extends FluidCheckout {

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
		$plugin_path_name = 'woocommerce-germanized-pro/woocommerce-germanized-pro.php';

		// Get plugin file path
		$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . $plugin_path_name;
		
		// Bail if plugin file does not exist
		if ( ! file_exists( $plugin_file ) ) { return false; }
		
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( $plugin_path_name ) && class_exists( 'WooCommerce_Germanized_Pro' );
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

		// Bail if multistep feature is not enabled
		// Need to get option directly as the Lite plugin might not be activated at this point
		if ( 'yes' !== get_option( 'woocommerce_gzdp_checkout_enable' ) ) { return $notices; }

		$notices[] = array(
			'name'           => 'germanized_pro_multistep_feature_enabled',
			'title'          => __( 'Germanized PRO multistep checkout feature needs to be disabled when using Fluid Checkout', 'fluid-checkout' ),
			'description'    => __( 'When using Fluid Checkout, the multistep feature from the Germanized PRO plugin becomes unnecessary and can cause <strong>critical errors while processing new orders</strong>. Please disable the Germanized PRO multistep checkout feature in the plugin settings.', 'fluid-checkout' ),
			'dismissable'    => false,
			'error'          => true,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=germanized-multistep_checkout' ), __( 'Go to Germanized settings', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_GermanizedPRO_MultistepFeatureEnabled::instance();
