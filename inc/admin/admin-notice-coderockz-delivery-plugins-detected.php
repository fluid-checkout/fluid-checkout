<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: CodeRockz Delivery plugins detected.
 */
class FluidCheckout_AdminNotices_CodeRockzDeliveryPlugins extends FluidCheckout {

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
		// Slug to compare to
		$plugin_files = array(
			'woo-delivery/coderockz-woo-delivery.php',
			'coderockz-woocommerce-delivery-date-time-pro/coderockz-woo-delivery.php',
		);

		// Get list of current active plugins
		// Needs to use `get_option` directly as `FluidCheckout_Settings::get_option()` wrapper function is not available yet
		$active_plugins = (array) get_option( 'active_plugins', array() );

		// Check if any of the plugin files is in the list of active plugins
		foreach ( $plugin_files as $plugin_file ) {
			if ( in_array( $plugin_file, $active_plugins ) ) {
				return true;
			}
		}

		// Otherwise, return false.
		return false;
	}

	/**
	 * Check if legacy integrations plugin is activated on a single install or network wide.
	 */
	public function is_legacy_integrations_activated() {
		// Slug to compare to
		$plugin_file = 'fluid-checkout-legacy2/fluid-checkout-legacy2.php';

		// Get list of current active plugins
		// Needs to use `get_option` directly as `FluidCheckout_Settings::get_option()` wrapper function is not available yet
		$active_plugins = (array) get_option( 'active_plugins', array() );

		// Check if any of the plugin files is in the list of active plugins
		if ( in_array( $plugin_file, $active_plugins ) ) {
			return true;
		}

		// Otherwise, return false.
		return false;
	}



	/**
	 * Add notice.
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'manage_options' ) ) { return $notices; }

		// Bail if none of target component are activated
		if ( ! $this->is_component_activated() ) { return $notices; }

		// Bail if Legacy Integrations plugins is detected
		if ( $this->is_legacy_integrations_activated() ) { return $notices; }

		// Bail if PRO is activated and version is at least 3.0+
		if ( $this->is_pro_activated() && version_compare( $this->get_plugin_version( 'fluid-checkout-pro/fluid-checkout-pro.php' ), '3.0.0-beta-10', '>=' ) ) { return $notices; }

		$notices[] = array(
			'name'           => 'coderockz_plugins_detected',
			'title'          => __( 'Fluid Checkout and Delivery Date plugins by CodeRockz', 'fluid-checkout' ),
			'description'    => __( '<p>We detected that you are using the plugins Delivery & Pickup Date Time for WooCommerce (free and PRO) by CodeRockz. Integration with these plugins have been moved to Fluid Checkout PRO.</p>', 'fluid-checkout' ),
			'error'          => true,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary">%s</a>', 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=coderockz-integrations&mtm_source=lite-plugin', __( 'Upgrade to PRO', 'fluid-checkout' ) ),
				sprintf( '<a href="%s" class="button button-secondary" target="_blank">%s</a>', 'https://fluidcheckout.com/version-4-customization-migration/#compat-delivery-date-coderockz', __( 'What should I do?', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_CodeRockzDeliveryPlugins::instance();
