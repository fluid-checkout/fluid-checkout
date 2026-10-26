<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: clean up legacy individual settings options after profile migration.
 */
class FluidCheckout_AdminNotices_SettingsLegacyCleanup extends FluidCheckout {

	/**
	 * Plugin prefix for the admin notices options.
	 */
	private static $plugin_prefix = 'fc';

	/**
	 * Time to wait after migration before showing the cleanup notice (`strtotime`-relative, e.g. `2 months`).
	 */
	const NOTICE_DELAY = '2 months';



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
	 * Whether legacy individual settings options are still waiting to be cleaned up.
	 */
	public static function is_cleanup_pending() {
		// Bail if settings class is not available
		if ( ! class_exists( 'FluidCheckout_Settings' ) ) { return false; }

		return 'yes' === FluidCheckout_Settings::instance()->get_raw_option( FluidCheckout_Settings::CLEANUP_NOTICE_OPTION, 'no' );
	}



	/**
	 * Whether the Tools page should offer the legacy settings cleanup control.
	 */
	public static function should_show_tools_cleanup_field() {
		// Bail if this is a new install or cleanup is already done
		if ( ! FluidCheckout_Admin::is_legacy_install() || ! self::is_cleanup_pending() ) { return false; }

		return true;
	}



	/**
	 * URL to run legacy settings cleanup from the Tools settings tab.
	 */
	public static function get_cleanup_url() {
		return wp_nonce_url(
			add_query_arg(
				array( 'fc_settings_legacy_cleanup' => '1' ),
				admin_url( 'admin.php?page=fluid-checkout&tab=tools' )
			),
			'fc_settings_legacy_cleanup'
		);
	}



	/**
	 * Add notice.
	 *
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return $notices; }

		// Bail if settings class is not available
		if ( ! class_exists( 'FluidCheckout_Settings' ) ) { return $notices; }

		// Bail if this is a new install or cleanup is already done
		if ( ! FluidCheckout_Admin::is_legacy_install() || ! self::is_cleanup_pending() ) { return $notices; }

		$settings = FluidCheckout_Settings::instance();

		$migration_time = absint( $settings->get_raw_option( FluidCheckout_Settings::MIGRATION_TIME_OPTION, 0 ) );

		// Persist migration time when missing so the delay can be measured
		if ( $migration_time < 1 ) {
			$migration_time = time();
			$settings->update_raw_option( FluidCheckout_Settings::MIGRATION_TIME_OPTION, $migration_time, false );
		}

		// Bail if the delay after migration has not passed yet
		if ( time() < strtotime( '+' . self::NOTICE_DELAY, $migration_time ) ) {
			return $notices;
		}

		$cleanup_url = self::get_cleanup_url();
		$dismiss_url = wp_nonce_url(
			add_query_arg( array( 'fc_settings_legacy_cleanup' => 'dismiss' ), admin_url( 'admin.php?page=fluid-checkout&tab=tools' ) ),
			'fc_settings_legacy_cleanup'
		);

		$description  = '<p>' . esc_html( __( 'Since Fluid Checkout 5.0, settings are saved differently. Your settings have been migrated but legacy options are still stored in the database.', 'fluid-checkout' ) ) . '</p>';
		$description .= '<p>' . esc_html( __( 'Your store has been running for at least 2 months on the new settings so these legacy options can now be cleaned from the database. Please note that after cleanup, if you need to downgrade to previous versions of the plugin it will use default values for all settings.', 'fluid-checkout' ) ) . '</p>';

		$notices[] = array(
			'name'            => self::$plugin_prefix . '_settings_legacy_cleanup',
			'title'           => __( 'Fluid Checkout legacy settings cleanup', 'fluid-checkout' ),
			'description'     => $description,
			'paragraph_wrap'  => false,
			'dismissible'     => false,
			'error'           => false,
			'actions'         => array(
				sprintf( '<a href="%s" class="button button-primary">%s</a>', esc_url( $cleanup_url ), esc_html( __( 'Delete leftover options', 'fluid-checkout' ) ) ),
				sprintf( '<a href="%s" class="button">%s</a>', esc_url( $dismiss_url ), esc_html( __( 'Dismiss', 'fluid-checkout' ) ) ),
			),
		);

		return $notices;
	}

}

FluidCheckout_AdminNotices_SettingsLegacyCleanup::instance();
