<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: breaking changes of version 4.0.
 */
class FluidCheckout_AdminNotices_BreakingChanges_Version_400 extends FluidCheckout {

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

		$notices[] = array(
			'name'           => 'breaking_changes_version_400',
			'title'          => __( 'Fluid Checkout - Important changes coming in the next major version!', 'fluid-checkout' ),
			'description'    => __( 'Fluid Checkout Lite 4.0 will be released soon with <strong>important changes that might impact your website</strong> if you have made customizations to our plugins. If your website does not have any customizations for Fluid Checkout plugins, it should work in the same way as before to the next major versions.<br><br>If you have any customizations for Fluid Checkout on your website, please read our guide <a href="https://fluidcheckout.com/version-4-customization-migration/">Migrating customizations to Fluid Checkout Lite 4.0+</a>', 'fluid-checkout' ),
			'error'          => true,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', 'https://fluidcheckout.com/version-4-customization-migration/', __( 'Read the migration guide', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_BreakingChanges_Version_400::instance();
