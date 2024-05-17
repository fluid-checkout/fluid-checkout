<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Kentha (by QantumThemes).
 */
class FluidCheckout_ThemeCompat_Kentha extends FluidCheckout {

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

		// Body attributes
		add_filter( 'fc_checkout_body_custom_attributes', array( $this, 'add_body_attributes' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Dark mode
		add_filter( 'fc_enable_dark_mode_styles', '__return_true', 10 );

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

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
	}



	/**
	 * Add custom attributes to the body element.
	 *
	 * @param  array  $custom_attributes   Body attributes.
	 */
	public function add_body_attributes( $custom_attributes ) {
		// Add body element id
		$custom_attributes[ 'id' ] = 'qtBody';

		return $custom_attributes;
	}



	/**
	 * Add opening tags for inner container from the Hestia theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div class="qt-main">
			<?php // Background element from the theme ?>
			<?php get_template_part( 'phpincludes/part-background' ); ?>
			<div class="qt-container qt-main-contents">
				<div class="qt-the-content qt-paper qt-paddedcontent qt-card">
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

		$attributes['data-sticky-relative-to'] = '{ "sm": { "breakpointInitial": 1201, "breakpointFinal": 100000, "selector": ".qt-menubar" } }';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme colors
		$primary_color = get_theme_mod( 'kentha_color_accent', '#00ced0' );
		$primary_color_hover = get_theme_mod( 'kentha_color_accent_hover', '#00fcff' );
		$text_color = get_theme_mod( 'kentha_textcolor_on_buttons', '#fff' );

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '48px',
				'--fluidcheckout--field--padding-left' => '24px',
				'--fluidcheckout--field--background-color--accent' => $primary_color,
				'--fluidcheckout--field--font-size' => '16px',

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => $primary_color,
				'--fluidcheckout--button--primary--background-color' => $primary_color,
				'--fluidcheckout--button--primary--text-color' => $text_color,
				'--fluidcheckout--button--primary--border-color--hover' => $primary_color_hover,
				'--fluidcheckout--button--primary--background-color--hover' => $primary_color_hover,
				'--fluidcheckout--button--primary--text-color--hover' => $text_color,

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => $primary_color,
				'--fluidcheckout--button--secondary--background-color' => $primary_color,
				'--fluidcheckout--button--secondary--text-color' => $text_color,
				'--fluidcheckout--button--secondary--border-color--hover' => $primary_color_hover,
				'--fluidcheckout--button--secondary--background-color--hover' => $primary_color_hover,
				'--fluidcheckout--button--secondary--text-color--hover' => $text_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Kentha::instance();
