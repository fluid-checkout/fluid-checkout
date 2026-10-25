<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: promote the redesigned Fluid Checkout settings page.
 */
class FluidCheckout_AdminNotices_NewSettingsPage extends FluidCheckout {

	/**
	 * Dismiss option suffix for {@see FluidCheckout_AdminNotices::is_dismissed()}.
	 */
	const NOTICE_NAME = 'new_settings_page';

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
		add_filter( 'fc_admin_notices', array( $this, 'add_notice' ), 10 );
	}



	/**
	 * Add notice.
	 *
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return $notices; }

		// Bail if settings page class is not available
		if ( ! class_exists( 'FluidCheckout_Admin_Settings_Page' ) ) { return $notices; }

		// Bail if the plugin was installed with the new settings experience
		if ( ! $this->should_show_for_install_date() ) { return $notices; }

		$settings_page = FluidCheckout_Admin_Settings_Page::instance();

		$notices[] = array(
			'name'          => self::NOTICE_NAME,
			'title'         => __( 'New settings page 🎉', 'fluid-checkout' ),
			'description'   => __( 'Fluid Checkout has a redesigned settings experience with guided setup, live page previews, and easier navigation. Open settings to explore the new layout and discover new ways to configure your checkout.', 'fluid-checkout' ),
			'dismiss_label' => __( 'Dismiss', 'fluid-checkout' ),
			'visible'       => ! $settings_page->is_settings_page(),
			'actions'       => array(
				sprintf(
					'<a href="%s" class="button button-primary">%s</a>',
					esc_url( $this->get_go_to_settings_url() ),
					esc_html__( 'Go to settings', 'fluid-checkout' )
				),
			),
		);

		return $notices;
	}



	/**
	 * Whether this notice should show for the current install activation time.
	 */
	public function should_show_for_install_date() {
		return FluidCheckout_Admin::is_legacy_install();
	}



	/**
	 * Settings page URL that dismisses this notice on first visit.
	 */
	public function get_go_to_settings_url() {
		return wp_nonce_url(
			add_query_arg(
				array(
					'fc_action' => 'dismiss_notice',
					'fc_notice' => self::NOTICE_NAME,
				),
				admin_url(
					'admin.php?page=' . FluidCheckout_Admin_Settings_Page::PAGE_SLUG . '&tab=' . FluidCheckout_Admin_Settings_Page::DEFAULT_TAB
				)
			),
			'dismiss-notice'
		);
	}

}

FluidCheckout_AdminNotices_NewSettingsPage::instance();
