<?php
/**
 * Checkout steps layout: Multi Step Enhanced
 */
class FluidCheckoutLayout_MultiStepEnhanced extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
        // Load dependency: FluidCheckoutLayout_MultiStep
        require_once self::$directory_path . 'inc/layouts/multi-step/checkout-layout.php';

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );
		
		// Template loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 30, 3 );

		// Steps display order
		remove_action( 'wfc_checkout_steps', array( FluidCheckoutLayout_MultiStep::instance(), 'output_step_billing' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_contact' ), 10 );

		// Payment
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		remove_action( 'wfc_checkout_payment', array( FluidCheckoutLayout_MultiStep::instance(), 'output_order_review' ), 10 );
		remove_action( 'wfc_checkout_payment', array( FluidCheckoutLayout_MultiStep::instance(), 'output_checkout_place_order' ), 30 );
		remove_action( 'woocommerce_order_button_html', array( FluidCheckoutLayout_MultiStep::instance(), 'get_payment_step_actions_html' ), 20 );
		
		// Order Review
		add_action( 'wfc_checkout_after_steps', array( $this, 'output_checkout_order_review_wrapper' ), 10 );
		add_action( 'wfc_checkout_order_review_wrapper', array( FluidCheckoutLayout_MultiStep::instance(), 'output_order_review' ), 10 );
		add_action( 'wfc_checkout_order_review_wrapper', array( FluidCheckoutLayout_MultiStep::instance(), 'output_checkout_place_order' ), 30 );
		
	}



	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		return array_merge( $classes, array( 'has-wfc-checkout-layout--multi-step-enhanced' ) );
	}



	/**
	 * Enqueue scripts
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'wfc-checkout-layout--multi-step-enhanced', self::$directory_url . 'css/checkout-multi-step--enhanced'. self::$asset_version . '.css', NULL, NULL );
	}



	/*
	 * Locate template files from this checkout layout.
	 * @since 1.1.0
	 */
	public function locate_template( $template, $template_name, $template_path ) {
	 
		global $woocommerce;
	 
		$_template = $template;
	 
		if ( ! $template_path ) $template_path = $woocommerce->template_url;
	 
		// Get plugin path
		$plugin_path  = self::$directory_path . 'inc/layouts/multi-step-enhanced/templates/';
	 
		// Look within passed path within the theme
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name
			)
		);
	 
		// Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}
	 
		// Use default template
		if ( ! $template ){
			$template = $_template;
		}
	 
		// Return what we found
		return $template;
	}



	/**
	 * Output order review section wrapper
	 */
	public function output_checkout_order_review_wrapper() {
		?>
		<div class="wfc-checkout-order-review-wrapper">
			<?php do_action( 'wfc_checkout_order_review_wrapper' ) ?>
		</div>
		<?php
	}









	/**
	 * Checkout Steps
	 */



	/**
	 * Output step: Contact
	 */
	public function output_step_contact() {
		FluidCheckoutLayout_MultiStep::instance()->output_step_start_tag( apply_filters( 'wfc_contact_step_title', __( 'Contact', 'woocommerce-fluid-checkout' ) ) );
		do_action( 'woocommerce_checkout_before_customer_details' );

		// Define contact fields
		$contact_fields = apply_filters( 'wfc_checkout_contact_step_field_ids', array(
			'billing_email',
			'billing_full_name',
			'billing_first_name',
			'billing_last_name',
			'billing_phone',
		) );

		wc_get_template(
			'checkout/form-contact.php',
			array(
				'checkout'          => WC()->checkout(),
				'display_fields'    => $contact_fields,
				'section_title'  	=> apply_filters( 'wfc_checkout_contact_details_step_section_title', __( 'Contact details', 'woocommerce-fluid-checkout' ) ),
			)
		);

		echo $this->get_contact_step_actions_html();
		FluidCheckoutLayout_MultiStep::instance()->output_step_end_tag();
	}



	/**
	 * Add back button html to checkout contact step.
	 */
	public function get_contact_step_actions_html() {
		$actions_html = '<div class="wfc-actions"><button class="wfc-next button alt">' . __( 'Proceed to Shipping', 'woocommerce-fluid-checkout' ) . '</button></div>';
		return apply_filters( 'wfc_contact_step_actions_html', $actions_html );
	}

	/**
	 * END - Checkout Steps
	 */

}

FluidCheckoutLayout_MultiStepEnhanced::instance();