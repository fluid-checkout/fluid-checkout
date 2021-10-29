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
		// Styles
		add_action( 'wp_head', array( $this, 'maybe_output_theme_options_css' ), 10 );
		add_action( 'wp_head', array( $this, 'maybe_output_header_css' ), 10 );

		// Page header
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Settings
		add_filter( 'fc_advanced_settings', array( $this, 'add_settings' ), 10 );
	}



	/**
	 * Maybe output the theme options and custom CSS to the checkout page.
	 */
	public function maybe_output_theme_options_css() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) { return; }

		// Bail if using the theme's header and footer
		if ( ! FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return; }

		// Bail if required functions are not available
		if ( ! function_exists( 'us_get_theme_options_css' ) ) { return; }

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



	/**
	 * Maybe output custom header CSS to the checkout page.
	 */
	public function maybe_output_header_css() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) { return; }
		
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return; }

		// Custom spacing
		$header_spacing = get_option( 'fc_compat_theme_impreza_header_spacing' );
		if ( ! empty( $header_spacing ) && intval( $header_spacing ) > 0 || '0' == $header_spacing ) {
			$header_spacing = intval( $header_spacing );
			?>
			<style id="fc-compat-theme-impreza-header"><?php echo 'body:not(.has-checkout-header).theme-Impreza div.woocommerce{padding-top: '.$header_spacing.'px;}'; ?></style>
			<?php
		}
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#page-header';

		return $attributes;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {
		// Define positions for new settings
		$index = count( $settings ) - 1;

		// Define setting to insert
		$insert_settings = array(
			array(
				'title'           => __( 'Theme Impreza', 'fluid-checkout' ),
				'desc'            => __( 'Spacing for site header at the checkout page (in pixels)', 'fluid-checkout' ),
				'desc_tip'        => __( 'Only applicable when using the Impreza theme header at the checkout page.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_impreza_header_spacing',
				'placeholder'     => '120',
				'type'            => 'number',
				'autoload'        => false,
			),
		);

		// Get token position
		$position_index = count( $settings ) - 1;
		for ( $index = 0; $index < count( $settings ) - 1; $index++ ) {
			$args = $settings[ $index ];

			if ( array_key_exists( 'id', $args ) && $args[ 'id' ] == 'fc_hide_site_header_footer_at_checkout' ) {
				$position_index = $index + 1;
			}
		}

		// Insert at token position
		$new_settings  = array_slice( $settings, 0, $position_index );
		$new_settings = array_merge( $new_settings, $insert_settings );
		$new_settings = array_merge( $new_settings, array_slice( $settings, $position_index, count( $settings ) ) );

		return $new_settings;
	}

}

FluidCheckout_ThemeCompat_Impreza::instance();
