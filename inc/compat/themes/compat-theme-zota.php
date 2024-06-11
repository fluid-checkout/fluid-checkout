<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Zota (by Thembay).
 */
class FluidCheckout_ThemeCompat_Zota extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}



	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Checkout template hooks
		$this->checkout_template_hooks();

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Remove unnecessary theme's WooCommerce scripts
		$this->remove_action_for_class( 'wp_enqueue_scripts', array( 'Zota_WooCommerce', 'woocommerce_scripts' ), 20 );
	}



	/**
	 * Add checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
	}



	/**
	 * Add opening tags for inner container from the Hestia theme.
	 */
	public function add_inner_container_opening_tags() {
		?>
		<div id="main-container" class="container">
			<div class="row">
				<div id="main-content" class="main-page col-12">
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

		$attributes['data-sticky-relative-to'] = '{ "xs": { "breakpointInitial": 0, "breakpointFinal": 1199, "selector": ".topbar-device-mobile" } }';

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
				'--fluidcheckout--field--height' => '48px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-radius' => '4px',
				'--fluidcheckout--field--background-color--accent' => 'var(--tb-theme-color)',

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => 'var(--tb-theme-color)',
				'--fluidcheckout--button--primary--background-color' => 'var(--tb-theme-color)',
				'--fluidcheckout--button--primary--text-color' => '#fff',
				'--fluidcheckout--button--primary--border-color--hover' => 'var(--tb-theme-color-hover)',
				'--fluidcheckout--button--primary--background-color--hover' => 'var(--tb-theme-color-hover)',
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => 'var(--tb-theme-color)',
				'--fluidcheckout--button--secondary--background-color' => 'var(--tb-theme-color)',
				'--fluidcheckout--button--secondary--text-color' => '#fff',
				'--fluidcheckout--button--secondary--border-color--hover' => 'var(--tb-theme-color-hover)',
				'--fluidcheckout--button--secondary--background-color--hover' => 'var(--tb-theme-color-hover)',
				'--fluidcheckout--button--secondary--text-color--hover' => '#fff',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Zota::instance();
