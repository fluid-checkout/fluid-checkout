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
		add_filter( self::$plugin_prefix . '_admin_notices', array( $this, 'add_notice' ), 10 );
	}



	/**
	 * Add notice.
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return $notices; }

		// Bail if migrations class is unavailable
		if ( ! class_exists( 'FluidCheckout_AdminDBMigrations' ) ) { return $notices; }

		$migrations = FluidCheckout_AdminDBMigrations::instance();

		// Bail if there are no medium or high risk steps requiring confirmation
		if ( ! $migrations->has_migrations_requiring_confirmation() ) { return $notices; }

		// Get url of the current page, adding a nonce value and parameter to it
		$database_update_url = wp_nonce_url( add_query_arg( array( self::$plugin_prefix . '_action' => 'update_db' ) ), self::$plugin_prefix . '_update_db' );

		$highest_risk = $migrations->get_highest_pending_risk();

		// Build description
		// Do not wrap the full description in a single `<p>` so block elements (lists) stay valid HTML.
		if ( FluidCheckout_AdminDBMigrations::RISK_HIGH === $highest_risk ) {
			$description = '<p>' . __( 'Some changes to the database are required. As with any update, we recommend you to <strong>take a full backup of your website before proceeding</strong>.', 'fluid-checkout' ) . '</p>';
		} else {
			$description = '<p>' . __( 'Some changes to the database are required.', 'fluid-checkout' ) . '</p>';
		}

		// Maybe add migration change descriptions for medium and high steps only
		$migrations_descriptions = $migrations->get_migrations_descriptions( array(
			FluidCheckout_AdminDBMigrations::RISK_MEDIUM,
			FluidCheckout_AdminDBMigrations::RISK_HIGH,
		) );
		if ( ! empty( $migrations_descriptions ) ) {
			$description .= '<p>' . __( 'The following changes will be applied:', 'fluid-checkout' ) . '</p>';
			$description .= '<ul class="ul-disc">';
			foreach ( $migrations_descriptions as $migration_description ) {
				$description .= '<li>' . esc_html( $migration_description ) . '</li>';
			}
			$description .= '</ul>';
		}

		// Add notice
		$notices[] = array(
			'name'            => self::$plugin_prefix . '_db_migrations',
			'title'           => __( 'Fluid Checkout database update', 'fluid-checkout' ),
			'description'     => $description,
			'paragraph_wrap'  => false,
			'dismissible'     => false,
			'actions'         => array(
				sprintf( '<a href="%s" class="button button-primary">%s</a>', $database_update_url, __( 'Update database', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_DBMigrations::instance();
