<?php
/**
 * Checkout steps layout: Multi Step
 */
class FluidCheckoutLayout_MultiStepEnhanced extends FluidCheckout {

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

		// Steps display order
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_customer_contact' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_shipping' ), 50 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_payment' ), 100 );

		// Payment
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_checkout_payment', 'woocommerce_checkout_payment', 20 );
		
		// Order Review
		add_action( 'wfc_checkout_order_review', array( $this, 'output_order_review' ), 10 );
		
	}



	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		return array_merge( $classes, array( 'has-wfc-checkout-layout', 'has-wfc-checkout-layout--multi-step has-wfc-checkout-layout--multi-step-enhanced' ) );
	}



	/**
	 * Enqueue scripts
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'wfc-checkout-layout--multi-step', self::$directory_url . 'css/checkout-layout--multi-step'. self::$asset_version . '.css', NULL, NULL );
		wp_enqueue_style( 'wfc-checkout-layout--multi-step-enhanced', self::$directory_url . 'css/checkout-layout--multi-step-enhanced'. self::$asset_version . '.css', NULL, NULL );
		
		wp_enqueue_script( 'wfc-checkout-steps', self::$directory_url . 'js/checkout-steps'. self::$asset_version . '.js', NULL, NULL, true );
		wp_add_inline_script( 'wfc-checkout-steps', 'window.addEventListener("load",function(){CheckoutSteps.init();})' );
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
		<div id="wfc-wrapper" class="wfc-wrapper <?php echo esc_attr( apply_filters( 'wfc_wrapper_classes', '' ) ); ?>">
			<div class="wfc-inside">
				
				<div class="wfc-row wfc-header">
					<div id="wfc-progressbar"><?php echo apply_filters( 'wfc_progressbar_steps_placeholder', '<div class="wfc-step current"></div><div class="wfc-step"></div><div class="wfc-step"></div>' ); ?></div>
				</div>

				<div class="wfc-checkout-steps">
					<?php do_action( 'wfc_checkout_steps', $checkout ); ?>
				</div>

				<div class="wfc-checkout-order-review">
					<?php do_action( 'wfc_checkout_order_review', $checkout ); ?>
				</div>

			</div>
		</div>
		<?php
	}



	/**
	 * Output start tag for a checkout step.
	 */
	public function output_step_start_tag( $step_label ) {
		?>
		<section class="wfc-frame" data-label="<?php echo esc_attr( $step_label ) ?>">
		<?php
	}
	/**
	 * Output end tag for a checkout step.
	 */
	public function output_step_end_tag() {
		?>
		</section>
		<?php
	}



	/**
	 * Output step: Contact Details
	 */
	public function output_step_customer_contact() {
		$this->output_step_start_tag( __( 'Contact Details', 'woocommerce-fluid-checkout' ) );
		do_action( 'woocommerce_checkout_before_customer_details' );
		do_action( 'woocommerce_checkout_billing' );
		echo $this->get_billing_step_actions_html();
		$this->output_step_end_tag();
	}



	/**
	 * Output step: Shipping
	 */
	public function output_step_shipping() {
		$this->output_step_start_tag( __( 'Shipping', 'woocommerce-fluid-checkout' ) );
		do_action( 'woocommerce_checkout_shipping' );
		do_action( 'woocommerce_checkout_after_customer_details' );
		echo $this->get_shipping_step_actions_html();
		$this->output_step_end_tag();
	}



	/**
	 * Output step: Payment
	 */
	public function output_step_payment() {
		$this->output_step_start_tag( __( 'Payment', 'woocommerce-fluid-checkout' ) );
		do_action( 'wfc_checkout_payment' );
		$this->output_step_end_tag();
	}



	public function wfc_checkout_payment() {
		if ( ! is_ajax() ) {
			do_action( 'woocommerce_review_order_before_payment' );
		}
		?>
		<div id="payment" class="woocommerce-checkout-payment">
			<?php if ( WC()->cart->needs_payment() ) : ?>
				<ul class="wc_payment_methods payment_methods methods">
					<?php
					if ( ! empty( $available_gateways ) ) {
						foreach ( $available_gateways as $gateway ) {
							wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
						}
					} else {
						echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ) . '</li>'; // @codingStandardsIgnoreLine
					}
					?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		if ( ! is_ajax() ) {
			do_action( 'woocommerce_review_order_after_payment' );
		}
	}



	/**
	 * Output Order Review
	 */
	public function output_order_review() {
		?>
		<div class="wfc-order-review">

			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
			
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>
			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

		</div>
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

}

FluidCheckoutLayout_MultiStepEnhanced::instance();