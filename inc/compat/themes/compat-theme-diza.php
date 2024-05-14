<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Diza (by Thembay).
 */
class FluidCheckout_ThemeCompat_Diza extends FluidCheckout {

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
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Checkout template hooks
		$this->checkout_template_hooks();

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Dequeue
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_scripts' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		remove_filter( 'woocommerce_cart_item_name', 'diza_woocommerce_cart_item_name', 10, 3 );
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
		<div id="main-container" class="container">
			<div class="row">
				<div class="main-page col-12">
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
	 * Dequeue theme scripts on checkout page that interfere with Fluid Checkout scripts.
	 */
	public function maybe_dequeue_scripts() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		wp_dequeue_script( 'diza-woocommerce' );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'diza_tbay_get_config' ) ) { return $css_variables; }

		// Get colors from the theme
		$main_color = diza_tbay_get_config( 'main_color' );
		$accent_color = diza_tbay_get_config( 'main_color_second' );

		// If colors are not set, use the default value
		if ( ! $main_color ) {
			$main_color = '#075cc9';
		}

		if ( ! $accent_color ) {
			$accent_color = '#52d5e6';
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '42px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--background-color--accent' => $main_color,

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '30px',

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => $main_color,
				'--fluidcheckout--button--primary--background-color' => $main_color,
				'--fluidcheckout--button--primary--text-color' => '#fff',
				'--fluidcheckout--button--primary--border-color--hover' => $accent_color,
				'--fluidcheckout--button--primary--background-color--hover' => $accent_color,
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',

				// Secondary button color
				'--fluidcheckout--button--secondary--border-color' => $main_color,
				'--fluidcheckout--button--secondary--background-color' => $main_color,
				'--fluidcheckout--button--secondary--text-color' => '#fff',
				'--fluidcheckout--button--secondary--border-color--hover' => $accent_color,
				'--fluidcheckout--button--secondary--background-color--hover' => $accent_color,
				'--fluidcheckout--button--secondary--text-color--hover' => '#fff',

				// Custom variable for the primary color from the theme
				'--fluidcheckout--diza--primary-color' => $main_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Diza::instance();
