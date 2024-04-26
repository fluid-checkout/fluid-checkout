<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: The Hanger (by Get Bowtied).
 */
class FluidCheckout_ThemeCompat_TheHanger extends FluidCheckout {

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

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Missing header part from the theme
		add_action( 'fc_checkout_before_main_section_wrapper', array( $this, 'add_header_part' ), 10 );

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
	}



	/**
	 * Add second part of the header from Hestia theme.
	 */
	public function add_header_part() {
		// Bail if theme's class is not available
		if ( ! class_exists( 'Hestia_Header_Layout_Manager' ) ) { return; }

		// Bail if theme's class method is not available
		if ( ! method_exists( 'Hestia_Header_Layout_Manager', 'post_page_header' ) ) { return; }

		$layout_manager = new Hestia_Header_Layout_Manager();
		$layout_manager->post_page_header();
	}



	/**
	 * Add opening tags for inner container from the Hestia theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div class="small-12 columns">
			<div class="site-content">
			<?php
	}

	/**
	 * Add closing tags for inner container from the Hestia theme.
	 */
	public function add_inner_container_closing_tags() {
			?>
			</div>
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

		return $class . ' row small-collapse';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme function is not available
		if ( ! method_exists( 'GBT_Opt', 'getOption' ) ) { return $attributes; }

		// Bail if sticky header is disabled
		if ( 1 !== GBT_Opt::getOption( 'header_sticky_visibility' ) ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.sticky_header_placeholder';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Default theme's accent color
		$accent_color = '#C4B583';

		// Get theme's color value if exists
		if ( method_exists( 'GBT_Opt', 'getOption' ) ) {
			$accent_color = GBT_Opt::getOption( 'accent_color' );
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '48px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-color' => '#777',
				'--fluidcheckout--field--background-color--accent' => $accent_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_TheHanger::instance();
