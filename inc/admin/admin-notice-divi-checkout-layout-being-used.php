<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notice: Divi Builder checkout layout being used.
 */
class FluidCheckout_AdminNotices_Divi_CheckoutLayoutBeingUsed extends FluidCheckout {

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
	 * Check if component is activated on a single install or network wide.
	 */
	public function is_component_activated() {
		// Theme slug to compare to
		$theme_slug = 'Divi';

		// Get current theme and child theme slugs
		$current_theme_slug = get_template();
		$current_child_theme_slug = get_stylesheet();

		return ( $theme_slug === $current_theme_slug || $theme_slug === $current_child_theme_slug );
	}



	/**
	 * Add notice.
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_notice( $notices = array() ) {
		// Bail if function is not available
		if ( ! function_exists( 'wc_get_page_id' ) ) { return $notices; }

		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'manage_options' ) ) { return $notices; }

		// Bail if component is not activated
		if ( ! $this->is_component_activated() ) { return $notices; }

		// Get checkout page ID
		$checkout_page_id = wc_get_page_id( 'checkout' );

		// Bail if checkout page is not set
		if ( ! $checkout_page_id ) { return $notices; }

		// Get checkout page contents
		$post_content = get_post_field( 'post_content', $checkout_page_id );

		// Define shortcodes to look for
		$divi_builder_shortcodes = array(
			'et_pb_wc_checkout_billing',
			'et_pb_wc_checkout_shipping',
			'et_pb_wc_checkout_order_details',
			'et_pb_wc_checkout_payment_info',
			'et_pb_wc_checkout_additional_info',
		);

		// Check whether the checkout page has any of the shortcodes
		$checkout_page_has_divi_builder_shortcodes = false;
		foreach ( $divi_builder_shortcodes as $tag ) {
			if ( strpos( $post_content, '[' . $tag ) !== false ) {
				$checkout_page_has_divi_builder_shortcodes = true;
				break;
			}
		}

		// Bail if checkout page does not have any of the shortcodes
		if ( ! $checkout_page_has_divi_builder_shortcodes ) { return $notices; }

		// Get woocommerce checkout page URL
		$checkout_page_url = wc_get_checkout_url();

		// Get checkout page edit URL for the Divi Builder editor
		$checkout_page_edit_url = add_query_arg( array(
			'et_fb' => 1,
			'PageSpeed' => 'off',
		), $checkout_page_url );

		$notices[] = array(
			'name'           => 'divi_checkout_layout_feature_enabled',
			'title'          => __( 'Fluid Checkout is not compatible with the Divi Builder checkout layout', 'fluid-checkout' ),
			'description'    => __( '<p>Fluid Checkout needs the classic WooCommerce shortcode-based checkout form to work. When using the Divi Builder to customize the look and feel of the checkout page, the classic shortcodes are replaced with custom widgets from the Divi Builder for each part of the checkout page. These custom widgets are not compatible with Fluid Checkout.</p><p>Please edit your checkout page replacing the Divi Builder widgets with the a text widget containing the shortcode <code>[woocommerce_checkout]</code>.</p>', 'fluid-checkout' ),
			'dismissable'    => false,
			'error'          => true,
			'actions'        => array(
				sprintf( '<a href="%s" class="button button-primary">%s</a>', $checkout_page_edit_url, __( 'Edit the checkout page', 'fluid-checkout' ) ),
				sprintf( '<a href="%s" class="button button-secondary" target="_blank">%s</a>', 'https://fluidcheckout.com/docs/compat-theme-divi/', __( 'Read the documentation.', 'fluid-checkout' ) ),
			),
		);

		return $notices;
	}
	
}

FluidCheckout_AdminNotices_Divi_CheckoutLayoutBeingUsed::instance();
