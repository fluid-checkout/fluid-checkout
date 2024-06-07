<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: OnAir2 (by QantumThemes).
 */
class FluidCheckout_ThemeCompat_OnAir2 extends FluidCheckout {

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

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add or remove checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Theme's header section
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_theme_header_section' ), 10 );

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
	}



	/**
	 * Add header section from the OnAir2 theme.
	 */
	public function add_theme_header_section() {
		get_template_part( 'phpincludes/part-header-caption-page' );
	}

	/**
	 * Add opening tags for inner container from the Hestia theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div class="qt-container qt-spacer-l">
			<div class="qt-paper qt-paddedcontainer">
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
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {

		// Get theme colors
		$theme_color_accent = get_theme_mod( 'qt_color_secondary', '#64c9d9' );
		$theme_color_text = get_theme_mod( 'qt_textcolor_original', '#000000' );

		// Get derivated color as per theme's design
		if ( function_exists( 'qantumthemes_hex2rgba' ) ) {
			$theme_color_text = qantumthemes_hex2rgba( $theme_color_text, 0.87 );
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '48px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-color' => '#bbb',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--background-color--accent' => $theme_color_accent,

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '20px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '20px',
				'--fluidcheckout--validation-check--horizontal-spacing--password' => '32px',

				// Custom theme variables
				'--fluidcheckout--onair2--text-color' => $theme_color_text,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_OnAir2::instance();
