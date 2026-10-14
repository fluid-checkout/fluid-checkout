<?php
/**
 * Fluid Checkout Settings Page redirect stub for WooCommerce > Settings.
 *
 * @deprecated  Will be removed in version 6.0. Settings live under WP Admin > Fluid Checkout.
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_WC_FluidCheckoutTab', false ) ) {
	return new FluidCheckout_Settings_WC_FluidCheckoutTab();
}

/**
 * FluidCheckout_Settings_WC_FluidCheckoutTab.
 *
 * @deprecated  Will be removed in version 6.0.
 */
class FluidCheckout_Settings_WC_FluidCheckoutTab extends WC_Settings_Page {

	/**
	 * Option key that permanently hides this WooCommerce Settings tab.
	 */
	const HIDE_TAB_OPTION = 'fc_hide_wc_settings_tab';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'fc_checkout';
		$this->label = __( 'Fluid Checkout', 'fluid-checkout' );

		parent::__construct();

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Hide tab opt-out
		add_action( 'admin_init', array( $this, 'maybe_hide_wc_settings_tab' ), 5 );
	}



	/**
	 * Permanently hide the Fluid Checkout tab from WooCommerce > Settings when requested.
	 */
	public function maybe_hide_wc_settings_tab() {
		// Bail if not a hide request
		if ( ! isset( $_GET[ 'fc_hide_wc_settings_tab' ] ) || '1' !== sanitize_key( wp_unslash( $_GET[ 'fc_hide_wc_settings_tab' ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Bail if user cannot manage WooCommerce
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }

		check_admin_referer( 'fc_hide_wc_settings_tab' );

		update_option( self::HIDE_TAB_OPTION, 'yes', false );

		wp_safe_redirect( FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( 'dashboard' ) );
		exit;
	}



	/**
	 * Get sections.
	 * Sections are displayed as tabs on the Fluid Checkout settings page instead.
	 *
	 * @return array
	 */
	public function get_sections() {
		return array();
	}



	/**
	 * Output a notice that the settings have moved to the Fluid Checkout settings page.
	 *
	 * @deprecated  Will be removed in version 6.0.
	 */
	public function output() {
		global $current_section;

		// Hide the save button as there are no settings on this page
		$GLOBALS[ 'hide_save_button' ] = true;

		// Get the settings page URL for the current section
		$settings_page = FluidCheckout_Admin_Settings_Page::instance();
		$settings_url = $settings_page->get_settings_url( $settings_page->get_tab_for_section( (string) $current_section ) );
		$hide_url = wp_nonce_url(
			add_query_arg( array( 'fc_hide_wc_settings_tab' => '1' ), admin_url( 'admin.php?page=wc-settings&tab=fc_checkout' ) ),
			'fc_hide_wc_settings_tab'
		);
		?>
		<div class="notice notice-info inline fc-settings-moved-notice">
			<p><?php echo esc_html( __( 'These settings have been moved to WP Admin > Fluid Checkout > Settings.', 'fluid-checkout' ) ); ?></p>
			<p class="description"><?php echo esc_html( __( 'This WooCommerce Settings tab is deprecated and will be removed in Fluid Checkout 6.0.', 'fluid-checkout' ) ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>"><?php echo esc_html( __( 'Go to settings page', 'fluid-checkout' ) ); ?></a>
				<a class="button" href="<?php echo esc_url( $hide_url ); ?>"><?php echo esc_html( __( 'Hide this tab permanently', 'fluid-checkout' ) ); ?></a>
			</p>
		</div>
		<?php
	}



	/**
	 * Save settings.
	 * Intentionally empty as settings are saved on the Fluid Checkout settings page.
	 */
	public function save() {}



	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section name.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		return apply_filters( 'fc_admin_settings', array(), $current_section );
	}

}

return new FluidCheckout_Settings_WC_FluidCheckoutTab();
