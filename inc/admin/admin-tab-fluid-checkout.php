<?php
/**
 * Fluid Checkout Settings Page.
 *
 * @package fluid-checkout
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_Checkout', false ) ) {
	return new WC_Settings_FluidCheckout_Checkout();
}

/**
 * WC_Settings_FluidCheckout_Checkout.
 */
class WC_Settings_FluidCheckout_Checkout extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'fc_checkout';
		$this->label = __( 'Fluid Checkout', 'fluid-checkout' );

		parent::__construct();
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
	 */
	public function output() {
		global $current_section;

		// Hide the save button as there are no settings on this page
		$GLOBALS[ 'hide_save_button' ] = true;

		// Get the settings page URL for the current section
		$settings_page = FluidCheckout_Admin_Settings_Page::instance();
		$settings_url = $settings_page->get_settings_url( $settings_page->get_tab_for_section( (string) $current_section ) );
		?>
		<div class="notice notice-info inline fc-settings-moved-notice">
			<p><?php echo esc_html( __( 'These settings have been moved to WP Admin > Fluid Checkout > Settings.', 'fluid-checkout' ) ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>"><?php echo esc_html( __( 'Go to settings page', 'fluid-checkout' ) ); ?></a></p>
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
		return apply_filters( 'woocommerce_get_settings_' . $this->id, array(), $current_section );
	}

}

return new WC_Settings_FluidCheckout_Checkout();
