<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Hestia (by ThemeIsle).
 */
class FluidCheckout_ThemeCompat_Hestia extends FluidCheckout {

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
		// Late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Checkout template hooks
		$this->checkout_template_hooks();

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// General CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// CSS variables on edit address page
		add_action( 'fc_css_variables', array( $this, 'add_css_variables_edit_address' ), 20 );
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
		<div class="main <?php echo $this->maybe_add_inner_container_class(); ?>">
			<div class="blog-post">
				<div class="container">
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
	 * Maybe add the "main-raised" class from Hestia to the inner container of the page content.
	 */
	public function maybe_add_inner_container_class() {
		$hestia_general_layout = get_theme_mod( 'hestia_general_layout', apply_filters( 'hestia_boxed_layout_default', 1 ) );

		// Return the class if the Boxed Layout option is not enabled in the Hestia theme
		if ( ! isset( $hestia_general_layout ) || (bool) $hestia_general_layout !== true ) { return; }

		return 'main-raised';
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
				'--fluidcheckout--field--height' => '30.5px',
				'--fluidcheckout--field--padding-left' => '7px',
				'--fluidcheckout--field--background-color--accent' => '#9c27b0',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Add CSS variables to the edit address page.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables_edit_address( $css_variables ) {
		// Bail if not on account address edit page
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return $css_variables; }

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '36px', 
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.navbar-fixed-top';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Hestia::instance();
