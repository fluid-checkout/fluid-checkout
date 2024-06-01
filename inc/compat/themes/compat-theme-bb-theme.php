<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Beaver Builder Theme (by Fastline Media).
 */
class FluidCheckout_ThemeCompat_BeaverBuilderTheme extends FluidCheckout {

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

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

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
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
	}



	/**
	 * Add opening tags for inner container from the Hestia theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div class="fl-content-full container">
			<div class="row">
				<div class="fl-content col-md-12">
				<?php
	}

	/**
	 * Add closing tags for inner container from the Hestia theme.
	 */
	public function add_inner_container_closing_tags() {
				?>
				</div>
			</div>
		</div>
		<?php
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if theme function isn't available
		if ( ! method_exists( 'FLCustomizer', 'get_mods' ) ) { return $attributes; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Get theme settings from the Customizer
		$mods = FLCustomizer::get_mods();

		// Bail if fixed header is disabled in the theme
		if ( empty( $mods['fl-fixed-header'] ) || 'hidden' === $mods['fl-fixed-header'] ) { return $attributes; }

		// Get theme breakpoint for the sticky header
		if ( get_theme_mod( 'fl-medium-breakpoint' ) ) {
			$medium_breakpoint = get_theme_mod( 'fl-medium-breakpoint' );
		} else {
			$medium_breakpoint = 992;
		}

		// Maybe change the relative ID based on the theme's header option
		switch ( $mods['fl-fixed-header'] ) {
			case 'fixed':
				$attributes['data-sticky-relative-to'] = '{ "sm": { "breakpointInitial":' . $medium_breakpoint . ', "breakpointFinal": 100000, "selector": ".fl-fixed-header .fl-page-header" } }';
				break;
			case 'fadein':
				$attributes['data-sticky-relative-to'] = '{ "sm": { "breakpointInitial": ' . $medium_breakpoint . ', "breakpointFinal": 100000, "selector": ".fl-page-header-fixed" } }';
				break;
			case 'shrink':
				$attributes['data-sticky-relative-to'] = '{ "sm": { "breakpointInitial": ' . $medium_breakpoint . ', "breakpointFinal": 100000, "selector": ".fl-shrink-header-enabled .fl-page-header" } }';
				break;
		}

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme's class methods aren't available
		if ( ! method_exists( 'FLCustomizer', '_get_default_mods' ) ) { return $css_variables; }
		if ( ! method_exists( 'FLCustomizer', 'get_mods' ) ) { return $css_variables; }
		if ( ! method_exists( 'FLColor', 'hex' ) ) { return $css_variables; }
		if ( ! method_exists( 'FLColor', 'foreground' ) ) { return $css_variables; }

		// Get default color values from the theme to use as fallbacks
		$defaults = FLCustomizer::_get_default_mods();

		// Get current theme colors from the Customizer
		$mods = FLCustomizer::get_mods();

		// Get currently active colors
		$accent_color = FLColor::hex( array( $mods['fl-accent'], $defaults['fl-accent'] ) );
		$accent_color_hover = FLColor::hex( array( $mods['fl-accent-hover'], $defaults['fl-accent-hover'] ) );

		// Get button colors from the theme
		if ( 'custom' === $mods['fl-button-style'] ) {
			// Get colors based on Customizer settings if enabled
			$button_background_color = FLColor::hex( array( $mods['fl-button-background-color'], $defaults['fl-button-background-color'] ) );
			$button_background_color_hover = FLColor::hex( array( $mods['fl-button-background-hover-color'], $defaults['fl-button-background-hover-color'] ) );
			$button_border_color = FLColor::hex( array( $mods['fl-button-border-color'], $defaults['fl-button-border-color'] ) );
			$button_border_color_hover = FLColor::hex( array( $mods['fl-button-border-hover-color'], $defaults['fl-button-border-hover-color'] ) );
			$button_text_color = FLColor::hex( array( $mods['fl-button-color'], $defaults['fl-button-color'] ) );
			$button_text_color_hover = FLColor::hex( array( $mods['fl-button-hover-color'], $defaults['fl-button-hover-color'] ) );
		} else {
			// Get default colors
			$button_background_color = $accent_color;
			$button_background_color_hover = $accent_color_hover;
			$button_text_color = FLColor::foreground( $accent_color );
			$button_text_color_hover = FLColor::foreground( $accent_color_hover );

			// Get border colors by replicating theme's LESS function to darken a color
			$button_border_color = $this->adjust_color_brightness( $accent_color, -0.12 );
			$button_border_color_hover = $this->adjust_color_brightness( $accent_color_hover, -0.12 );
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '34px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--border-color' => '#e6e6e6',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--background-color--accent' => $accent_color,

				// Primary button color
				'--fluidcheckout--button--primary--border-color' => $button_border_color,
				'--fluidcheckout--button--primary--background-color' => $button_background_color,
				'--fluidcheckout--button--primary--text-color' => $button_text_color,
				'--fluidcheckout--button--primary--border-color--hover' => $button_border_color_hover,
				'--fluidcheckout--button--primary--background-color--hover' => $button_background_color_hover,
				'--fluidcheckout--button--primary--text-color--hover' => $button_text_color_hover,

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => $button_border_color,
				'--fluidcheckout--button--secondary--background-color' => $button_background_color,
				'--fluidcheckout--button--secondary--text-color' => $button_text_color,
				'--fluidcheckout--button--secondary--border-color--hover' => $button_border_color_hover,
				'--fluidcheckout--button--secondary--background-color--hover' => $button_background_color_hover,
				'--fluidcheckout--button--secondary--text-color--hover' => $button_text_color_hover,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Increases or decreases the brightness of a color by a percentage of the current brightness.
	 * Replicates the Less `lighten()` and `darken()` functions used in the Beaver Builder theme.
	 *
	 * @param  string  $hexCode
	 * @param  float  $steps  A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
	 */
	public function adjust_color_brightness( $hex, $steps ) {
		// Bail if not a hex color
		if ( '#' !== $hex[0] ) { return $hex; }

		// Steps should be between -1 and 1
		$steps = max( -1, min( 1, $steps ) );

		// Get RGB values
		$rgb = str_split( str_replace( '#', '', $hex ), 2 );
		$rgb = array_map( 'hexdec', $rgb );

		// Adjust each RGB value
		foreach ( $rgb as $key => $value ) {
			$rgb[ $key ] = max( 0, min( 255, $value + ( $steps * 255 ) ) );
		}

		// Regenerate the adjusted hex code
		$color = sprintf( '#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2] );

		return $color;
	}

}

FluidCheckout_ThemeCompat_BeaverBuilderTheme::instance();
