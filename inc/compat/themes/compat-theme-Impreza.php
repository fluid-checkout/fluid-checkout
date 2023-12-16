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
		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Styles
		add_action( 'wp_head', array( $this, 'maybe_output_theme_options_css' ), 10 );
		add_action( 'wp_head', array( $this, 'maybe_output_header_css' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {

		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Theme Impreza', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_impreza_options',
			),

			array(
				'title'           => __( 'Header', 'fluid-checkout' ),
				'desc'            => __( 'Spacing for site header at the checkout page (in pixels)', 'fluid-checkout' ),
				'desc_tip'        => __( 'Only applicable when using the Impreza theme header at the checkout page.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_impreza_header_spacing',
				'type'            => 'number',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_impreza_header_spacing' ),
				'placeholder'     => '120',
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_impreza_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $settings; }

		// Add settings
		$settings[ 'checkoutSteps' ][ 'scrollOffsetSelector' ] = '#page-header';

		return $settings;
	}



	/**
	 * Maybe output the theme options and custom CSS to the checkout page.
	 */
	public function maybe_output_theme_options_css() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) { return; }

		// Bail if using theme header and footer
		if ( ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

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
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }
		
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Custom spacing
		$header_spacing = FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_impreza_header_spacing' );
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
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#page-header';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Impreza::instance();
