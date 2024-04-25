<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Amphibious (by TemplatePocket).
 */
class FluidCheckout_ThemeCompat_Amphibious extends FluidCheckout {

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
	 * Add opening tags for inner container from the theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div class="container">
			<div class="row">
				<div class="content-area col">
				<?php
	}

	/**
	 * Add closing tags for inner container from the theme.
	 */
	public function add_inner_container_closing_tags() {
				?>
				</div>
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

		return $class . ' site-content-inside';
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
				'--fluidcheckout--field--height' => '38.3px',
				'--fluidcheckout--field--border-radius' => '2px',
				'--fluidcheckout--field--border-color' => 'var( --fluidcheckout--field--border-color, #ebebeb )',
				'--fluidcheckout--field--padding-left' => '10px',
				'--fluidcheckout--field--background-color--accent' => '#0073aa',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Amphibious::instance();
