<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: YITH WooCommerce Uploads Premium (by YITH).
 */
class FluidCheckout_YithWooCommerceUploadsPremium extends FluidCheckout {

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
		add_filter( 'yith_ywau_can_show_upload_link', array( $this, 'change_yith_ywau_can_show_upload_link' ), 10 );
	}



	/**
	 * Maybe set to display the upload link on checkout page and fragments.
	 *
	 * @param   bool  $can_show  Whether to show the upload links on the page.
	 */
	public function change_yith_ywau_can_show_upload_link( $can_show ) {
		// Bail if YITH class is not available
		if ( ! class_exists( 'YITH_WooCommerce_Additional_Uploads_Premium' ) ) { return $can_show; }

		// Checkout page or fragment
		if ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) {
			return YITH_WooCommerce_Additional_Uploads_Premium::get_instance()->allow_on_checkout_page;
		}

		// Return unchanged value if none of the above criteria is met
		return $can_show;
	}

}

FluidCheckout_YithWooCommerceUploadsPremium::instance();
