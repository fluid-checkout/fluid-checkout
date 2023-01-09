<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: ask review.
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
		add_action( 'fc_admin_notices', array( $this, 'add_notice' ), 10 );
	}



	/**
	 * Add plugin review request notice.
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return $notices; }

		// Get install date
		$install_date = get_option( 'fc_plugin_activation_time' );
		$past_date = strtotime( '-7 days' );

		// Bail if 7 days have not passed since installation
		if ( $past_date < $install_date ) { return $notices; }

		$notices[] = array(
			'name'           => 'review_request_timed',
			'title'          => __( 'Thanks for choosing Fluid Checkout!', 'fluid-checkout' ),
			'description'    => __( 'You have been using the plugin for a while now. How do you like it so far? <br>Please give us a quick rating, we appreciate every bit of feedback and it encourages us to keep improving the plugin :)', 'fluid-checkout' ),
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/fluid-checkout/reviews/', __( 'Rate the plugin', 'fluid-checkout' ) ),
				sprintf( '<a href="%s" class="button button-secondary" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/fluid-checkout/', __( 'I need help!', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_ReviewRequest::instance();
