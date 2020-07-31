<?php
/**
 * Checkout steps layout: Multi Step
 */
class FluidCheckoutLayout_MultiStep extends FluidCheckout {

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
		// Checkout Form Template
		add_filter( 'wfc_checkout_form', array( $this, 'output_checkout_form' ) );

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );
		
		// Wrapper
		$wrapper_start_hook_args = apply_filters( 'wfc_wrapper_start_hook_args', array( 'hook' => 'woocommerce_before_checkout_form', 'priority' => 10 ) );
		$wrapper_end_hook_args = apply_filters( 'wfc_wrapper_end_hook_args', array( 'hook' => 'woocommerce_after_checkout_form', 'priority' => 10 ) );
		add_action( $wrapper_start_hook_args['hook'], array( $this, 'output_wfc_wrapper_start_tag' ), $wrapper_start_hook_args['priority'] );
		add_action( $wrapper_end_hook_args['hook'], array( $this, 'output_wfc_wrapper_end_tag' ), $wrapper_end_hook_args['priority'] );
		
		// Billing Step
		$billing_start_hook_args = apply_filters( 'wfc_billing_start_hook_args', array( 'hook' => 'woocommerce_checkout_billing', 'priority' => 5 ) );
		$billing_end_hook_args = apply_filters( 'wfc_billing_end_hook_args', array( 'hook' => 'woocommerce_checkout_billing', 'priority' => 9999 ) );
		add_action( $billing_start_hook_args['hook'], array( $this, 'output_wfc_billing_start_tag' ), $billing_start_hook_args['priority'] );
		add_action( $billing_end_hook_args['hook'], array( $this, 'output_wfc_billing_end_tag' ), $billing_end_hook_args['priority'] );
		
		// Shipping Step
		$shipping_start_hook_args = apply_filters( 'wfc_shipping_start_hook_args', array( 'hook' => 'woocommerce_checkout_shipping', 'priority' => 5 ) );
		$shipping_end_hook_args = apply_filters( 'wfc_shipping_end_hook_args', array( 'hook' => 'woocommerce_checkout_shipping', 'priority' => 9999 ) );
		add_action( $shipping_start_hook_args['hook'], array( $this, 'output_wfc_shipping_start_tag' ), $shipping_start_hook_args['priority'] );
		add_action( $shipping_end_hook_args['hook'], array( $this, 'output_wfc_shipping_end_tag' ), $shipping_end_hook_args['priority'] );
		
		// Payment Step
		$payment_start_hook_args = apply_filters( 'wfc_payment_start_hook_args', array( 'hook' => 'woocommerce_checkout_before_order_review_heading', 'priority' => 5 ) );
		$payment_end_hook_args = apply_filters( 'wfc_payment_end_hook_args', array( 'hook' => 'woocommerce_checkout_after_order_review', 'priority' => 9999 ) );
		add_action( $payment_start_hook_args['hook'], array( $this, 'output_wfc_payment_start_tag' ), $payment_start_hook_args['priority'] );
		add_action( $payment_end_hook_args['hook'], array( $this, 'output_wfc_payment_end_tag' ), $payment_end_hook_args['priority'] );
		add_filter( 'woocommerce_order_button_html', array( $this, 'get_payment_step_actions_html' ), 20 );
	}



	/**
	 * Outputs the checkout form.
	 * 
	 * This method outputs the code inside the `<form></form>` tags
	 * on WooCommerce's checkout forms and contains customizations
	 * which need to be updated whenever there is a new version of
	 * WooCommerce's original files.
	 * 
	 * @see wp-content/plugins/woocommerce/templates/checkout/form-checkout.php
	 */
	function output_checkout_form( $checkout ) {
		?>
			
			<div class="col2-set">

				<?php if ( $checkout->get_checkout_fields() ) : ?>
				<div class="col-1">
					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

					<?php do_action( 'woocommerce_checkout_billing' ); ?>

					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			
					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
				</div>
				<?php endif; ?>
		


				<div class="col-2">
					<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
				
					<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
					
					<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
					
					<div id="order_review" class="woocommerce-checkout-review-order">
						<?php do_action( 'woocommerce_checkout_order_review' ); ?>
					</div>
					
					<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				</div>
			</div>
			
		<?php
	}



	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		return array_merge( $classes, array( 'has-wfc-checkout-layout', 'has-wfc-checkout-layout--multi-step' ) );
	}



	/**
	 * Enqueue scripts
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'wfc-checkout-layout--multi-step', self::$directory_url . 'css/checkout-layout--multi-step'. self::$asset_version . '.css', NULL, NULL );
		
		wp_enqueue_script( 'wfc-checkout-steps', self::$directory_url . 'js/checkout-steps'. self::$asset_version . '.js', NULL, NULL, true );
		wp_add_inline_script( 'wfc-checkout-steps', 'window.addEventListener("load",function(){CheckoutSteps.init();})' );
	}



	/**
	 * Output start tag for checkout steps wrapper.
	 */
	public function output_wfc_wrapper_start_tag( $checkout ) {
		// If checkout registration is disabled and not logged in, the user cannot checkout.
		if ( $checkout && ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) ) { return; }

		?>
		<div id="wfc-wrapper" class="wfc-wrapper slider-wrapper <?php echo esc_attr( apply_filters( 'wfc_wrapper_classes', '' ) ); ?>">
			<div class="wfc-inside">
				<div class="wfc-row wfc-header">
					<div id="wfc-progressbar"><?php echo apply_filters( 'wfc_progressbar_steps_placeholder', '<div class="wfc-step current"></div><div class="wfc-step"></div><div class="wfc-step"></div>' ); ?></div>
				</div>
		<?php
	}



	/**
	 * Output end tag for checkout steps wrapper.
	 */
	public function output_wfc_wrapper_end_tag() {
		?>
			</div><!-- .wfc-inside -->
		</div><!-- #wfc-wrapper -->
		<?php
	}



	/**
	 * Output start tag for billing step.
	 */
	public function output_wfc_billing_start_tag() {
		?>
		<section class="wfc-frame" data-label="<?php esc_attr_e( 'Billing', 'woocommerce-fluid-checkout' ) ?>">
		<?php
	}



	/**
	 * Output end tag for billing step.
	 */
	public function output_wfc_billing_end_tag() {
			echo $this->get_billing_step_actions_html();
		?>
		</section>
		<?php
	}



	/**
	 * Output start tag for shipping step.
	 */
	public function output_wfc_shipping_start_tag() {
		?>
		<section class="wfc-frame" data-label="<?php esc_attr_e( 'Shipping', 'woocommerce-fluid-checkout' ) ?>">
		<?php
	}



	/**
	 * Output end tag for shipping step.
	 */
	public function output_wfc_shipping_end_tag() {
			echo $this->get_shipping_step_actions_html();
		?>
		</section>
		<?php
	}



	/**
	 * Add back button html to place order button on checkout.
	 */
	public function get_billing_step_actions_html() {
		$actions_html = '<div class="wfc-actions"><button class="wfc-next button alt">' . __( 'Proceed to Shipping', 'woocommerce-fluid-checkout' ) . '</button></div>';
		return apply_filters( 'wfc_billing_step_actions_html', $actions_html );
	}



	/**
	 * Add back button html to place order button on checkout.
	 */
	public function get_shipping_step_actions_html() {
		$actions_html = '<div class="wfc-actions"><button class="wfc-prev">' . _x( 'Back', 'Previous step button', 'woocommerce-fluid-checkout' ) . '</button> <button class="wfc-next button alt">' . __( 'Proceed to Payment', 'woocommerce-fluid-checkout' ) . '</button></div>';
		return apply_filters( 'wfc_shipping_step_actions_html', $actions_html );
	}



	/**
	 * Add back button html to place order button on checkout.
	 * @param [String] $button_html Place Order button html.
	 */
	public function get_payment_step_actions_html( $button_html ) {
		$actions_html = '<div class="wfc-actions"><button class="wfc-prev">' . _x( 'Back', 'Previous step button', 'woocommerce-fluid-checkout' ) . '</button> ' . $button_html . '</div>';
		return apply_filters( 'wfc_payment_step_actions_html', $actions_html, $button_html );
	}



	/**
	 * Output start tag for payment step.
	 */
	public function output_wfc_payment_start_tag() {
		?>
		<section class="wfc-frame" data-label="<?php esc_attr_e( 'Payment', 'woocommerce-fluid-checkout' ) ?>">
		<?php
	}



	/**
	 * Output end tag for payment step.
	 */
	public function output_wfc_payment_end_tag() {
		?>
		</section>
		<?php
	}

}

FluidCheckoutLayout_MultiStep::instance();