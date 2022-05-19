<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: product updates newsletter.
 */
class FluidCheckout_AdminNotices_ProductUpdateNewsletter extends FluidCheckout {

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
	public static function add_notice( $notices = array() ) {
		// Get install date
		$install_date = get_option( 'fc_plugin_activation_time' );
		$past_date = strtotime( '-7 days' );

		// Bail if 7 days have not passed since installation
		if ( $past_date < $install_date ) { return; }

		$notices[] = array(
			'name' => 'fc_version_200',
			'title' => __( 'Important changes coming with Fluid Checkout 2.0', 'fluid-checkout' ),
			'description' => __( 'You have been using Fluid Checkout for a while. How do you like it so far? <br>Please give us a quick rating, it works as a boost for us to keep working on the plugin :)', 'fluid-checkout' ),
			'actions' => array(
				sprintf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', esc_url( 'https://fluidcheckout.com/product-update-signup/' ), __( 'Signup', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_ProductUpdateNewsletter::instance();
