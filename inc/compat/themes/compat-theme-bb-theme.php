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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

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
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get default color values from the theme to use as fallbacks
		$defaults = FLCustomizer::_get_default_mods();

		// Get current theme colors from the customizer
		$mods = FLCustomizer::get_mods();

		// Get currently active colors
		$accent_color = FLColor::hex( array( $mods['fl-accent'], $defaults['fl-accent'] ) );

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
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_BeaverBuilderTheme::instance();
