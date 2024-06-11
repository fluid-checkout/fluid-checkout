<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Mr. Tailor (by Get Bowtied).
*/
class FluidCheckout_ThemeCompat_MrTailor extends FluidCheckout {

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

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tag' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tag' ), 10 );
	}



	/**
	 * Add opening tag for inner container from the theme.
	 */
	public function add_inner_container_opening_tag() {
		?>
		<div class="small-12 columns">
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

		return $class . ' row';
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme function is not available
		if ( ! method_exists( 'MrTailor_Opt', 'getOption' ) ) { return $attributes; }

		// Bail if sticky header option is disabled in the theme
		if ( ! MrTailor_Opt::getOption( 'sticky_header' ) ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.site-header-sticky.sticky';

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
				'--fluidcheckout--field--height' => '45px',
				'--fluidcheckout--field--padding-left' => '20px',
				'--fluidcheckout--field--border-radius' => '3px',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '32px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '32px',
				'--fluidcheckout--validation-check--horizontal-spacing--password' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_MrTailor::instance();
