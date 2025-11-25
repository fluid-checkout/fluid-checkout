<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: gift-cards-coupon-input
 */
class FluidCheckout_GiftCardsCouponInput extends FluidCheckout {

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
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Bail if WooCommerce Gift Cards plugin is not active
		if ( ! function_exists( 'WC_GC' ) ) { return; }

		// Extend Fluid Checkout's coupon input AJAX endpoint to handle gift cards
		add_action( 'wc_ajax_fc_add_coupon_code', array( $this, 'maybe_apply_gift_card' ), 9 );
	}

	/**
	 * Extend the coupon input AJAX endpoint to handle gift cards.
	 * Checks if code matches gift card pattern and processes it before Fluid Checkout handles it.
	 *
	 * @return void
	 */
	public function maybe_apply_gift_card() {
		check_ajax_referer( 'fc-add-coupon-code', 'security' );

		if ( empty( $_REQUEST['coupon_code'] ) ) { return; }

		$coupon_code = wp_unslash( $_REQUEST['coupon_code'] );
		$pattern     = apply_filters( 'woocommerce_gc_coupon_input_pattern', '/(?>[a-zA-Z0-9]{4}\-){3}[a-zA-Z0-9]{4}/', $coupon_code );
		
		// Check if code matches gift card pattern
		if ( ! preg_match( $pattern, $coupon_code, $matches ) || empty( $matches ) ) {
			return; // Let Fluid Checkout handle as regular coupon
		}

		// Process as gift card (reusing original plugin's logic)
		$giftcard_code = array_pop( $matches );
		$results       = WC_GC()->db->giftcards->query( array( 'return' => 'objects', 'code' => $giftcard_code, 'limit' => 1 ) );
		$giftcard_data = count( $results ) ? array_shift( $results ) : false;

		if ( ! $giftcard_data ) {
			wc_add_notice( __( 'Gift Card not found.', 'woocommerce-gift-cards' ), 'error' );
		} else {
			$giftcard = new WC_GC_Gift_Card( $giftcard_data );
			try {
				if ( get_current_user_id() && apply_filters( 'woocommerce_gc_auto_redeem', false ) ) {
					$giftcard->redeem( get_current_user_id() );
				} else {
					WC_GC()->giftcards->apply_giftcard_to_session( $giftcard );
				}
				wc_add_notice( __( 'Gift Card code applied successfully!', 'woocommerce-gift-cards' ) );
			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}

		// Return JSON response
		ob_start();
		wc_print_notices();
		$message = ob_get_clean();

		wp_send_json(
			array(
				'result'       => false !== strpos( $message, 'woocommerce-error' ) || false !== strpos( $message, 'is-error' ) ? 'error' : 'success',
				'coupon_code'  => $coupon_code,
				'reference_id' => sanitize_text_field( wp_unslash( $_REQUEST['reference_id'] ?? '' ) ),
				'message'      => $message,
			)
		);
	}

}

FluidCheckout_GiftCardsCouponInput::instance();
