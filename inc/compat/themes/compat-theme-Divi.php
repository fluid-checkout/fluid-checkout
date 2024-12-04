<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Divi (by Elegant Themes).
 */
class FluidCheckout_ThemeCompat_Divi extends FluidCheckout {

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
		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Use theme's logo
		add_action( 'fc_checkout_header_logo', array( $this, 'output_checkout_header_logo' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
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
		$settings[ 'utils' ][ 'scrollOffsetSelector' ] = '#main-header';

		return $settings;
	}



	/**
	 * Output the theme logo on the plugin's checkout header.
	 */
	public function output_checkout_header_logo() {
		if ( function_exists( 'et_get_option' ) ) {
			$template_directory_uri   = get_template_directory_uri();
			$logo = ( $user_logo = et_get_option( 'divi_logo' ) ) && ! empty( $user_logo )
			? $user_logo
			: $template_directory_uri . '/images/logo.png';

			// Get logo image size based on attachment URL.
			$logo_size   = et_get_attachment_size_by_url( $logo );
			$logo_width  = ( ! empty( $logo_size ) && is_numeric( $logo_size[0] ) )
					? $logo_size[0]
					: '93'; // 93 is the width of the default logo.
			$logo_height = ( ! empty( $logo_size ) && is_numeric( $logo_size[1] ) )
					? $logo_size[1]
					: '43'; // 43 is the height of the default logo.

			ob_start();
			?>
				<a href="<?php echo esc_url( apply_filters( 'fc_checkout_header_logo_home_url', home_url( '/' ) ) ); ?>">
					<img src="<?php echo esc_attr( $logo ); ?>" width="<?php echo esc_attr( $logo_width ); ?>" height="<?php echo esc_attr( $logo_height ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" id="logo" data-height-percentage="<?php echo esc_attr( et_get_option( 'logo_height', '54' ) ); ?>" />
				</a>
			<?php
			$logo_container = ob_get_clean();
			echo $logo_container;
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

		$attributes['data-sticky-relative-to'] = '{ "sm": { "breakpointInitial": 981, "breakpointFinal": 100000, "selector": "#main-header" } }';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'et_get_option' ) ) { return $css_variables; }

		$theme_accent_color = et_get_option( 'accent_color', '#2ea3f2' );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '55.79px',
				'--fluidcheckout--field--padding-left' => '16px',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--background-color--accent' => $theme_accent_color,
				'--fluidcheckout--field--font-size' => '14px',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing' => '10px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '30px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Divi::instance();
