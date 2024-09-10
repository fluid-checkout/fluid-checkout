<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Porto (by P-THEMES).
 */
class FluidCheckout_ThemeCompat_Porto extends FluidCheckout {

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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// General CSS variables
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
		<div class="container">
			<div class="row main-content-wrap">
				<div class="main-content col-lg-12">
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
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if plugin function isn't available
		if ( ! function_exists( 'porto_get_meta_value' ) ) { return $attributes; }

		// Get theme settings
		global $porto_settings;

		// Bail if theme settings are not available
		if ( empty( $porto_settings ) ) { return $attributes; }

		// Get sticky header settings for the current page
		$sticky_header_page = porto_get_meta_value( 'sticky_header' );

		// Bail if sticky header is disabled on the current page
		if ( 'no' === $sticky_header_page ) { return $attributes; }

		$show_sticky_header = false;
		// Check if sticky header is enabled on the current page
		if ( 'yes' === $sticky_header_page ) {
			$show_sticky_header = true;
		// Otherwise, check if it's enabled globally
		} elseif ( $porto_settings['enable-sticky-header'] ) {
			$show_sticky_header = true;
		}

		// Bail if sticky header is disabled
		if ( ! $show_sticky_header ) { return $attributes; }

		// Desktop settings
		$desktop_settings = '"md": { "breakpointInitial": 992, "breakpointFinal": 10000, "selector": "#header.sticky-header .header-main.sticky" }';

		// Tablet settings (enable if not set or set to 'yes')
		$tablet_settings = '';
		if ( ! isset( $porto_settings['sticky-header-tablet'] ) || 'yes' === $porto_settings['sticky-header-tablet'] ) {
			$tablet_settings = '"sm": { "breakpointInitial": 481, "breakpointFinal": 991, "selector": "#header.sticky-header .header-main.sticky" }';
		}

		// Mobile settings (enable if not set or set to 'yes')
		$mobile_settings = '';
		if ( ! isset( $porto_settings['sticky-header-mobile'] ) || 'yes' === $porto_settings['sticky-header-mobile'] ) {
			$mobile_settings = '"xs": { "breakpointInitial": 0, "breakpointFinal": 480, "selector": "#header.sticky-header .header-main.sticky" }';
		}

		// Only keep non-empty values
		$settings = '';
		$settings = array_filter( array( $mobile_settings, $tablet_settings, $desktop_settings ), function( $value ) {
			return ! empty( $value );
		} );

		// Concatenate values with a comma
		$settings = implode( ', ', $settings );

		// Add the settings to the data attribute
		$attributes['data-sticky-relative-to'] = "{ {$settings} }";

		return $attributes;
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
				'--fluidcheckout--field--height' => '48.41px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--background-color--accent' => 'var(--porto-primary-color)',
				'--fluidcheckout--field--border-color' => 'var(--porto-input-bc)',
				'--fluidcheckout--field--font-size' => '0.85rem',

				// Button color styles - primary
				'--fluidcheckout--button--primary--border-color' => 'var(--porto-heading-color)',
				'--fluidcheckout--button--primary--background-color' => 'var(--porto-heading-color)',
				'--fluidcheckout--button--primary--text-color' => 'var(--porto-body-bg)',
				'--fluidcheckout--button--primary--border-color--hover' => 'var(--porto-heading-light-8)',
				'--fluidcheckout--button--primary--background-color--hover' => 'var(--porto-heading-light-8)',
				'--fluidcheckout--button--primary--text-color--hover' => 'var(--porto-body-bg)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Porto::instance();
