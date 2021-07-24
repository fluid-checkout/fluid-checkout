<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin review request notice.
 */
class FluidCheckout_AdminNotices_ReviewRequest extends FluidCheckout {

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
		add_action( 'fc_admin_notices', array( $this, 'add_review_request_seven_days_notice' ), 10 );
		add_action( 'fc_admin_notices', array( $this, 'add_review_request_hundredth_order_notice' ), 10 );
	}



	/**
	 * Add plugin review request notice.
	 */
	public static function add_review_request_seven_days_notice( $notices = array() ) {
		$notices[] = array(
			'name' => 'review_request_7_days',
			'title' => __( 'Thanks for choosing Fluid Checkout, you rock!', 'fluid-checkout' ),
			'description' => __( 'You have been using Fluid Checkout for a while. How do you like it so far? <br>Please give us a quick rating, it works as a boost for us to keep working on the plugin :)', 'fluid-checkout' ),
			'actions' => array(
				sprintf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', esc_url( 'https://wordpress.org/support/plugin/fluid-checkout/reviews/' ), __( 'Rate Us Now', 'fluid-checkout' ) ),
				sprintf( '<a href="%s" class="button button-secondary" target="_blank">%s</a>', esc_url( 'https://wordpress.org/support/plugin/fluid-checkout/' ), __( 'I Need Help!', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}

	

	/**
	 * Add plugin review request notice.
	 */
	public static function add_review_request_hundredth_order_notice( $notices = array() ) {
		$notices[] = array(
			'name' => 'review_request_100_orders',
			'title' => __( 'Congratulations on your 100th order with Fluid Checkout! 🎉', 'fluid-checkout' ),
			'description' => __( 'You have just reached 100 orders placed using Fluid Checkout! How does that feel?<br>Please give us a quick rating, it works as a boost for us to keep working on the plugin :)', 'fluid-checkout' ),
			'actions' => array(
				sprintf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', esc_url( 'https://wordpress.org/support/plugin/fluid-checkout/reviews/' ), __( 'Rate Us Now', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_ReviewRequest::instance();
