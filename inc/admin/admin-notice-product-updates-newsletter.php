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
			'name'           => 'fc_version_200',
			'error'          => true, // Not technically an `error`, but it needs to get attention. This line should be removed with version 2.0.
			'title'          => __( 'Fluid Checkout Lite 2.0 will be released soon and your website might get impacted', 'fluid-checkout' ),
			'description'    => __( 'Important changes are coming soon with Fluid Checkout Lite 2.0. We encourage everyone using it to sign up to <strong>receive important updates</strong> about new versions.', 'fluid-checkout' ),
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', esc_url( 'https://fluidcheckout.com/product-update-signup/' ), __( 'Get important updates', 'fluid-checkout' ) ),
				sprintf( '<a href="%s" class="button button-secondary" target="_blank">%s</a>', esc_url( 'https://fluidcheckout.com/product-update-signup/' ), __( 'More information', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_ProductUpdateNewsletter::instance();
