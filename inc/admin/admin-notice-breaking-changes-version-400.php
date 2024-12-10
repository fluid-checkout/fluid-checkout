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

		// Define variables
		$release_date = strtotime( '2024-12-10' );

		// Get install date
		// Need to get option directly as the Lite plugin might not be activated at this point
		$install_date = get_option( 'fc_plugin_activation_time' );

		// Bail if first installation was after the release date
		if ( ! $install_date || $install_date > $release_date ) { return $notices; }

		$notices[] = array(
			'name'           => 'breaking_changes_version_400',
			'title'          => __( 'Fluid Checkout Lite 4.0+ – Important changes!', 'fluid-checkout' ),
			'description'    => __( 'With this version of Fluid Checkout we have introduced <strong>important changes that might impact your website</strong> if you have made customizations to our plugins previously to the release of this version. If your website does not have any customizations for Fluid Checkout plugins, it should work in the same way as before.<br><br>If you have any customizations for Fluid Checkout on your website and have not yet migrated them to this version, please read our guide <a href="https://fluidcheckout.com/version-4-customization-migration/">Migrating customizations to Fluid Checkout Lite 4.0+</a>', 'fluid-checkout' ),
			'error'          => true,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', 'https://fluidcheckout.com/version-4-customization-migration/', __( 'Read the migration guide', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_BreakingChanges_Version_400::instance();
