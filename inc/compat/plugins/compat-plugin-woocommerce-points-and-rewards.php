<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Points and Rewards (by WooCommerce).
 */
class FluidCheckout_WooCommercePointsAndRewards extends FluidCheckout {

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
		// Bail if use of coupons not enabled
		if ( ! wc_coupons_enabled() ) { return; }

		// Bail if coupon code customizations not enabled
		if ( get_option( 'fc_enable_checkout_coupon_codes', 'yes' ) !== 'yes' ) { return; }

		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		if ( class_exists( 'WC_Points_Rewards' ) ) {
			// Remove messages and inline JS
			remove_action( 'woocommerce_before_checkout_form', array( WC_Points_Rewards::instance()->cart, 'render_earn_points_message' ), 5 );
			remove_action( 'woocommerce_before_checkout_form', array( WC_Points_Rewards::instance()->cart, 'render_redeem_points_message' ), 6 );
			remove_action( 'woocommerce_before_checkout_form', array( WC_Points_Rewards::instance()->cart, 'render_discount_javascript' ), 10 );

			// Checkout substep
			add_action( 'fc_before_substep_coupon_codes', array( $this, 'output_redeem_points_section' ), 10 );
		}
	}



	/**
	 * Renders a message above the cart displaying how many points the customer will receive for completing their purchase
	 *
	 * @since 1.0
	 */
	public function output_earn_points_message() {
		$points_earned = $this->get_points_earned_for_purchase();
		$message = WC_Points_Rewards::instance()->cart->generate_earn_points_message();

		// If message was null then return here, we don't need to continue.
		if( null === $message ) {
			return;
		}

		// Prepare for rendering by wrapping in div.
		$message = '<div class="woocommerce-info wc_points_rewards_earn_points">' . $message . '</div>';

		echo apply_filters( 'wc_points_rewards_earn_points_message', $message, $points_earned );
	}




	/**
	 * Returns the amount of points earned for the purchase, calculated by getting the points earned for each individual
	 * product purchase multiplied by the quantity being ordered.
	 * 
	 * Copied code from the original plugin because the function is `private`.
	 * 
	 * @see WC_Points_Rewards_Discount::get_points_earned_for_purchase();
	 */
	public function get_points_earned_for_purchase() {
		$points_earned = 0;

		foreach ( WC()->cart->get_cart() as $item_key => $item ) {
			$points_earned += apply_filters( 'woocommerce_points_earned_for_cart_item', WC_Points_Rewards_Product::get_points_earned_for_product_purchase( $item['data'] ), $item_key, $item ) * $item['quantity'];
		}

		/*
		 * Reduce by any discounts.  One minor drawback: if the discount includes a discount on tax and/or shipping
		 * it will cost the customer points, but this is a better solution than granting full points for discounted orders.
		 */
		if ( version_compare( WC_VERSION, '2.3', '<' ) ) {
			$discount = WC()->cart->discount_cart + WC()->cart->discount_total;
		} else {
			$discount = ( wc_prices_include_tax() ) ? WC()->cart->discount_cart + WC()->cart->discount_cart_tax : WC()->cart->discount_cart;
		}

		$discount_amount = min( WC_Points_Rewards_Manager::calculate_points( $discount ), $points_earned );

		// Apply a filter that will allow users to manipulate the way discounts affect points earned.
		$points_earned = apply_filters( 'wc_points_rewards_discount_points_modifier', $points_earned - $discount_amount, $points_earned, $discount_amount, $discount );

		// Check if applied coupons have a points modifier and use it to adjust the points earned.
		$coupons = WC()->cart->get_applied_coupons();

		$points_earned = WC_Points_Rewards_Manager::calculate_points_modification_from_coupons( $points_earned, $coupons );

		$points_earned = WC_Points_Rewards_Manager::round_the_points( $points_earned );
		return apply_filters( 'wc_points_rewards_points_earned_for_purchase', $points_earned, WC()->cart );
	}



	/**
	 * Output the redeem points section.
	 * 
	 * @see WC_Points_Rewards_Discount::render_redeem_points_message()
	 */
	public function output_redeem_points_section() {
		// COPIED FROM ORIGINAL PLUGIN FUNCTION
		$existing_discount = WC_Points_Rewards_Discount::get_discount_code();

		/*
		 * Don't display a points message to the user if:
		 * The cart total is fully discounted OR
		 * Coupons are disabled OR
		 * Points have already been applied for a discount.
		 */
		if ( WC_Points_Rewards::instance()->cart->is_fully_discounted() || ! wc_coupons_enabled() || ( ! empty( $existing_discount ) && WC()->cart->has_discount( $existing_discount ) ) ) {
			return;
		}

		// get the total discount available for redeeming points
		$discount_available = WC_Points_Rewards::instance()->cart->get_discount_for_redeeming_points( false, null, true );

		$message = WC_Points_Rewards::instance()->cart->generate_redeem_points_message();

		if ( null === $message ) {
			return;
		}
		// END - COPIED FROM ORIGINAL PLUGIN FUNCTION

		
		$html = '<div class="fc-coupon-codes__coupon wc_points_redeem_earn_points">';
		$html .= '<span class="fc-points-rewards__message">' . $message . '</span>';
		$html .= '<span class="fc-points-rewards__apply-discount">';
		$html .= '<input type="hidden" name="wc_points_rewards_apply_discount_amount" class="wc_points_rewards_apply_discount_amount" />';
		$html .= '<a href="#apply_discount" role="button" class="wc_points_rewards_apply_discount" name="wc_points_rewards_apply_discount">' . __( 'Apply Discount', 'woocommerce-points-and-rewards' ) . '</a>';
		$html .= '</span>';
		$html .= '</div>';
		
		echo $html;

		// // add 'Apply Discount' button
		// // CHANGE: Replace `form` element with a `div` element
		// $message .= '<div class="fc-points-rewards__apply-discount">';
		// $message .= '<input type="hidden" name="wc_points_rewards_apply_discount_amount" class="wc_points_rewards_apply_discount_amount" />';
		// // CHANGE: Change button type to `button` instead of `submit`
		// $message .= '<a href="#apply_discount" role="button" class="wc_points_rewards_apply_discount" name="wc_points_rewards_apply_discount">' . __( 'Apply Discount', 'woocommerce-points-and-rewards' ) . '</a></div>';

		// // wrap with info div
		// // CHANGE: Replace message wrapper class to display as a coupon code
		//  . $message . '</div>';

		// echo apply_filters( 'wc_points_rewards_redeem_points_message', $message, $discount_available );

		// CHANGE: Remove inline JS used to handle the points and rewards form submit
	}

}

FluidCheckout_WooCommercePointsAndRewards::instance();
