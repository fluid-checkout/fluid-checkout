<?php
/**
 * Checkout steps layout: Default
 */
class FluidCheckoutLayout_Default extends FluidCheckout {

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
	}


	/**
	 * Outputs the checkout form.
	 * 
	 * This method is be a copy of all code inside the `<form></form>`
	 * tags of WooCommerce's original template file and needs
	 * to be updated whenever there is a new version.
	 * 
	 * @see wp-content/plugins/woocommerce/templates/checkout/form-checkout.php
	 */
	function output_checkout_form( $checkout ) {
		if ( $checkout->get_checkout_fields() ) : ?>
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
			
			<div class="col2-set" id="customer_details">
				<div class="col-1">
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
				</div>
		
				<div class="col-2">
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				</div>
			</div>
			
			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			
			<?php endif; ?>
			
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			
			<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
			
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
			
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>
		
		<?php do_action( 'woocommerce_checkout_after_order_review' );
	}

	

}

FluidCheckoutLayout_Default::instance();