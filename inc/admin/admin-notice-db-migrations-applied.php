<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: database migrations applied successfully.
 */
class FluidCheckout_AdminNotices_DBMigrationsApplied extends FluidCheckout {
	
	/**
	 * Plugin prefix for the admin notices options.
	 */
	private static $plugin_prefix = 'fc';



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
	 * Add notice.
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return $notices; }

		// Get flag to show notice
		// Needs to use `get_option` directly as `FluidCheckout_Settings::get_option()` wrapper function is not available yet
		$option_value = get_option( self::$plugin_prefix . '_show_db_update_notice' );

		// Bail if not set to show notice
		if ( ! $option_value || 'yes' !== $option_value ) { return $notices; }

		// Get url of the current page, adding a nonce value and parameter to it
		$database_update_url = wp_nonce_url( add_query_arg( array( self::$plugin_prefix . '_action' => 'dismiss_updated_db' ) ), self::$plugin_prefix . '_updated_db_notice' );

		// Add notice
		$notices[] = array(
			'name'           => self::$plugin_prefix . '_db_migrations_applied',
			'title'          => __( 'Fluid Checkout database updated successfully', 'fluid-checkout' ),
			'description'    => __( 'The database changes where applied successfully.', 'fluid-checkout' ),
			'dismissable'    => false,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary">%s</a>', $database_update_url, __( 'Ok', 'fluid-checkout' ) ),
				sprintf( '<a href="%s" class="button button-secondary">%s</a>', $database_update_url, __( 'Dismiss', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_DBMigrationsApplied::instance();
