<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Phlox Pro (by averta).
 */
class FluidCheckout_ThemeCompat_PhloxPro extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Dequeue
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_scripts' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' aux-container aux-fold';
	}



	/**
	 * Dequeue theme scripts unnecessary on checkout page and that interfere with Fluid Checkout scripts.
	 */
	public function maybe_dequeue_scripts() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }
		
		wp_dequeue_script( 'auxin-plugins' );
		wp_dequeue_script( 'auxin-scripts' );
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
				'--fluidcheckout--field--height' => '55.2px',
				'--fluidcheckout--field--padding-left' => '1.1em',
				'--fluidcheckout--field--border-color' => '#bbb',
				'--fluidcheckout--field--background-color--accent' => '#5897fb',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--password' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_PhloxPro::instance();
