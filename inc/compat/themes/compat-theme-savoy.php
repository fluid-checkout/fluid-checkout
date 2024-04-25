<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Savoy (by NordicMade).
 */
class FluidCheckout_ThemeCompat_Savoy extends FluidCheckout {

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
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Site header sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );

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
		<div class="nm-page-default-col col-xs-12">
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
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' nm-row';
	}



	/**
	 * Change the relative selector for sticky elements.
	 *
	 * @param   array  $attributes  The element HTML attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		global $nm_theme_options;

		// Bail if fixed header setting is not available in the theme settings
		if ( ! isset( $nm_theme_options['header_fixed'] ) ) { return $attributes; }

		// Bail if fixed header isn't enabled in the theme settings
		if ( ! $nm_theme_options['header_fixed'] ) { return $attributes; }
	
		$attributes['data-sticky-relative-to'] = '.nm-header';
	
		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		global $nm_theme_options;

		// Defaul color value
		$dropdown_background_color = "#282828";

		if ( isset( $nm_theme_options['dropdown_menu_background_color'] ) && ! empty( $nm_theme_options['dropdown_menu_background_color'] ) ) {
			$dropdown_background_color = $nm_theme_options['dropdown_menu_background_color'];
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '39.54px',
				'--fluidcheckout--field--padding-left' => '10px',
				'--fluidcheckout--field--border-color' => '#e1e1e1',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--background-color--accent' => $dropdown_background_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Savoy::instance();
