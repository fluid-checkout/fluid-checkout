<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: breaking changes of version 2.0.
 */
class FluidCheckout_AdminNotices_BreakingChanges_Version_200 extends FluidCheckout {

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
	 * Check if Fluid Checkout (PRO) is activated on a single install or network wide.
	 * Otherwise, will display an admin notice.
	 */
	public function is_fluid_checkout_pro_activated() {
		$plugin_path_name = 'fluid-checkout-pro/fluid-checkout-pro.php';

		// Get plugin file path
		$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . $plugin_path_name;
		
		// Bail if plugin file does not exist
		if ( ! file_exists( $plugin_file ) ) { return false; }
		
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( $plugin_path_name ) && class_exists( 'FluidCheckout_PRO' );
	}



	/**
	 * Check if Fluid Checkout (V1) is activated on a single install or network wide.
	 * Otherwise, will display an admin notice.
	 */
	public function is_fluid_checkout_v1_activated() {
		$plugin_path_name = 'fluid-checkout-v1/fluid-checkout-v1.php';

		// Get plugin file path
		$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . $plugin_path_name;
		
		// Bail if plugin file does not exist
		if ( ! file_exists( $plugin_file ) ) { return false; }
		
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( $plugin_path_name ) && class_exists( 'FluidCheckout_v1' );
	}



	/**
	 * Add plugin review request notice.
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return $notices; }

		// Bail if either Fluid Checkout PRO or V1 plugins are activated
		if ( $this->is_fluid_checkout_pro_activated() || $this->is_fluid_checkout_v1_activated() ) { return $notices; }

		$notices[] = array(
			'name'           => 'breaking_changes_version_200',
			'title'          => __( 'Fluid Checkout 2.0 - Important changes!', 'fluid-checkout' ),
			'description'    => __( '<strong>Express checkout</strong>, <strong>Local pickup</strong>, <strong>Gift message</strong> and <strong>Packing list templates</strong> have been moved to Fluid Checkout PRO. <br>For a limited time, you can get these features at no extra cost.', 'fluid-checkout' ),
			'error'          => true,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', 'https://fluidcheckout.com/version-2-moved-features/', __( 'Keep using moved features', 'fluid-checkout' ) ),
				sprintf( '<a href="%s" class="button button-secondary" target="_blank">%s</a>', 'https://fluidcheckout.com/pricing/', __( 'Upgrade to PRO', 'fluid-checkout' ) ),
				sprintf( '<a href="%s" class="button button-secondary" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/fluid-checkout/', __( 'I need help!', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_BreakingChanges_Version_200::instance();
