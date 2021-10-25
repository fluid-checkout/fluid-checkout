<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Impreza (by UpSolution).
 */
class FluidCheckout_ThemeCompat_Impreza extends FluidCheckout {

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
		add_action( 'wp_head', array( $this, 'maybe_output_theme_options_css' ), 10 );
	}



	/**
	 * Maybe output the theme options and custom CSS to the checkout page.
	 */
	public function maybe_output_theme_options_css() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) { return; }

		// Bail if use of theme header is enabled
		if ( 'yes' !== get_option( 'fc_hide_site_header_footer_at_checkout', 'yes' ) ) { return; }

		// Theme Options CSS
		if ( defined( 'US_DEV' ) OR ! us_get_option( 'optimize_assets', 0 ) ) {
			?>
			<style id="us-theme-options-css"><?php echo us_get_theme_options_css() ?></style>
			<?php
		}

		// Custom CSS from Theme Options
		if ( ! us_get_option( 'optimize_assets', 0 ) AND $us_custom_css = us_get_option( 'custom_css', '' ) ) {
			?>
			<style id="us-custom-css"><?php echo us_minify_css( $us_custom_css ) ?></style>
			<?php
		}
	}

}

FluidCheckout_ThemeCompat_Impreza::instance();
