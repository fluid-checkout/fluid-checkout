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
	 * Days to wait after migration before showing the cleanup notice.
	 */
	const NOTICE_DELAY_DAYS = 14;



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
	 *
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return $notices; }

		// Bail if settings class is not available
		if ( ! class_exists( 'FluidCheckout_Settings' ) ) { return $notices; }

		$settings = FluidCheckout_Settings::instance();

		// Bail if cleanup is not pending
		if ( 'yes' !== $settings->get_raw_option( FluidCheckout_Settings::CLEANUP_NOTICE_OPTION, 'no' ) ) {
			return $notices;
		}

		$migration_time = absint( $settings->get_raw_option( FluidCheckout_Settings::MIGRATION_TIME_OPTION, 0 ) );

		// Persist migration time when missing so the delay can be measured
		if ( $migration_time < 1 ) {
			$migration_time = time();
			$settings->update_raw_option( FluidCheckout_Settings::MIGRATION_TIME_OPTION, $migration_time, false );
		}

		// Bail if the delay after migration has not passed yet
		if ( time() < ( $migration_time + ( DAY_IN_SECONDS * self::NOTICE_DELAY_DAYS ) ) ) {
			return $notices;
		}

		$cleanup_url = wp_nonce_url(
			add_query_arg( array( 'fc_settings_legacy_cleanup' => '1' ), admin_url( 'admin.php?page=fluid-checkout&tab=tools' ) ),
			'fc_settings_legacy_cleanup'
		);
		$dismiss_url = wp_nonce_url(
			add_query_arg( array( 'fc_settings_legacy_cleanup' => 'dismiss' ), admin_url( 'admin.php?page=fluid-checkout&tab=tools' ) ),
			'fc_settings_legacy_cleanup'
		);

		$description  = '<p>' . esc_html( __( 'Since Fluid Checkout 5.0, settings are saved differently. Legacy options are still stored in the database to allow downgrades to previous versions if needed.', 'fluid-checkout' ) ) . '</p>';
		$description .= '<p>' . esc_html( __( 'Your store has been running for about 2 weeks on the new settings so you could clean up legacy options stored in the database. Please note that after cleanup, if you need to downgrade to previous versions of the plugin it will use default values for all settings.', 'fluid-checkout' ) ) . '</p>';

		$notices[] = array(
			'name'            => self::$plugin_prefix . '_settings_legacy_cleanup',
			'title'           => __( 'Fluid Checkout settings cleanup', 'fluid-checkout' ),
			'description'     => $description,
			'paragraph_wrap'  => false,
			'dismissable'     => false,
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
