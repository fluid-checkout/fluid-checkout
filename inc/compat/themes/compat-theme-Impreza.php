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
		// Checkout template hooks
		$this->checkout_template_hooks();

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Styles
		add_action( 'wp_head', array( $this, 'maybe_output_theme_options_css' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tag' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tag' ), 10 );
	}



	/**
	 * Add opening tag for inner container from the theme.
	 */
	public function add_inner_container_opening_tag() {
		?>
		<div class="l-section-h i-cf">
		<?php
	}

	/**
	 * Add closing tag for inner container from the theme.
	 */
	public function add_inner_container_closing_tag() {
		?>
		</div>
		<?php
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
		$settings[ 'utils' ][ 'scrollOffsetSelector' ] = '#page-header';

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



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' l-section height_medium';
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '44.8px',
				'--fluidcheckout--field--padding-left' => '12.8px',
				'--fluidcheckout--field--border-width' => '0px',
				'--fluidcheckout--field--background-color' => 'var(--color-content-bg-alt)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Impreza::instance();
