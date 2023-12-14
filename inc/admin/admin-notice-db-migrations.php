<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: database migrations needed.
 */
class FluidCheckout_AdminNotices_DBMigrations extends FluidCheckout {

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
		add_action( self::$plugin_prefix . '_admin_notices', array( $this, 'add_notice' ), 10 );
	}



	/**
	 * Add notice.
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return $notices; }

		// Bail if there are no database migrations to apply
		if ( ! class_exists( 'FluidCheckout_AdminDBMigrations' ) || ! FluidCheckout_AdminDBMigrations::instance()->has_migrations_to_apply() ) { return $notices; }

		// Get url of the current page, adding a nonce value and parameter to it
		$database_update_url = wp_nonce_url( add_query_arg( array( self::$plugin_prefix . '_action' => 'update_db' ) ), self::$plugin_prefix . '_update_db' );

		// Add notice
		$notices[] = array(
			'name'           => self::$plugin_prefix . '_db_migrations',
			'title'          => __( 'Fluid Checkout database update', 'fluid-checkout' ),
			'description'    => __( 'Some changes to the database are required. As with any update, we recommend you to <strong>take a full backup of your website before proceeding</strong>.', 'fluid-checkout' ),
			'dismissable'    => false,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary">%s</a>', $database_update_url, __( 'Update database', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_DBMigrations::instance();
