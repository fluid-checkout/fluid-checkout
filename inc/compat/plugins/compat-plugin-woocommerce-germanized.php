<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Germanized for WooCommerce (by vendidero).
 */
class FluidCheckout_WooCommerceGermanized extends FluidCheckout {

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

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 1600, 3 ); // Priority needs to be higher than that used by Germanized (1500)

		// Admin settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10, 2 );

		// Place order fragments
		// Remove place order section fragment because Germanized already updates it
		remove_filter( 'woocommerce_update_order_review_fragments', array( FluidCheckout_Steps::instance(), 'add_place_order_fragment' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {

		if ( function_exists( 'wc_gzd_get_hook_priority' ) ) {

			// Remove payment title heading
			remove_action( 'woocommerce_review_order_before_payment', 'woocommerce_gzd_template_checkout_payment_title', 10 );

			// Order summary products
			remove_action( 'woocommerce_review_order_before_cart_contents', 'woocommerce_gzd_template_checkout_table_content_replacement' );
			remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_table_product_hide_filter_removal' );
			add_action( 'woocommerce_review_order_before_cart_contents', array( $this,'do_action_woocommerce_gzd_review_order_before_cart_contents' ), 10 );

			// Remove extraneous payment section from order summary
			remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 10 );

			// Place order position
			$place_order_position = get_option( 'fc_integration_woocommerce_germanized_place_order_position', 'order_summary_after_total' );
			if ( 'payment_step' === $place_order_position ) {
				remove_action( 'woocommerce_checkout_order_review', 'woocommerce_gzd_template_order_submit', wc_gzd_get_hook_priority( 'checkout_order_submit' ) );
				remove_action( 'woocommerce_checkout_after_order_review', 'woocommerce_gzd_template_order_submit', 30 );
				remove_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_remove_filter', 1500 );
				remove_action( 'woocommerce_review_order_after_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );
				remove_action( 'woocommerce_gzd_review_order_before_submit', 'woocommerce_gzd_template_set_order_button_show_filter', 1500 );
			}
			
			// Legal checkboxes position
			$legal_checkboxes_position = 'payment_step' === $place_order_position ? 'before_place_order' : get_option( 'fc_integration_woocommerce_germanized_legal_checkboxes_position', 'order_summary_before_products' );
			if ( 'order_summary_before_products' === $legal_checkboxes_position ) {
				remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
				remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', wc_gzd_get_hook_priority( 'checkout_set_terms' ) );
				add_action( 'woocommerce_checkout_before_order_review', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
				add_action( 'woocommerce_checkout_before_order_review', 'woocommerce_gzd_template_checkout_set_terms_manually', 20 );
			}
			else if( 'before_place_order' ) {
				remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
				remove_action( 'woocommerce_review_order_after_payment', 'woocommerce_gzd_template_checkout_set_terms_manually', wc_gzd_get_hook_priority( 'checkout_set_terms' ) );
				add_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_render_checkout_checkboxes', 10 );
				add_action( 'woocommerce_review_order_before_submit', 'woocommerce_gzd_template_checkout_set_terms_manually', 20 );
			}
		}

	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		global $woocommerce;
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = $woocommerce->template_url; };

		// Get plugin path
		$plugin_path  = self::$directory_path . 'templates/compat/plugins/woocommerce-germanized/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;

			// Look for template file in the theme
			if ( apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
				$_template = locate_template( array(
					$template_path . $template_name,
					$template_name,
				) );
			}
		}

		// Use default template
		if ( ! $_template ) {
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		// Bail if settings display are explicitly enabled
		if ( true !== apply_filters( 'fc_integration_woocommerce_germanized_settings', false ) ) { return $settings; }

		$settings[] = array(
			'title'          => __( 'Germanized for WooCommerce', 'fluid-checkout' ),
			'desc'           => __( 'Define the position to display the place order button. <br/><span style="color:#f00;"><strong>Disclaimer:</strong> by changing these settings, I confirm that I understand the legal implications of changing the position of the legal checkboxes on my checkout page.</span>', 'fluid-checkout' ),
			'id'             => 'fc_integration_woocommerce_germanized_place_order_position',
			'type'           => 'select',
			'options'        => array(
				'order_summary_after_total'      => _x( 'Order summary (after totals)', 'Place order position', 'fluid-checkout' ),
				'payment_step'                   => _x( 'Payment step', 'Place order position', 'fluid-checkout' ),
			),
			'default'        => 'order_summary_after_total',
			'autoload'       => false,
		);

		$settings[] = array(
			'desc'           => __( 'Define the position to display the legal checkboxes. <br/>The legal checkboxes will be displayed in the payment section if the place order button is set to display in the payment section.<br/><span style="color:#f00;"><strong>Disclaimer:</strong> by changing these settings, I confirm that I understand the legal implications of changing the position of the legal checkboxes on my checkout page.</span>', 'fluid-checkout' ),
			'id'             => 'fc_integration_woocommerce_germanized_legal_checkboxes_position',
			'type'           => 'select',
			'options'        => array(
				'order_summary_before_products'  => _x( 'Order summary (before products)', 'Legal checkboxes position', 'fluid-checkout' ),
				'before_place_order'             => _x( 'Before place order button', 'Legal checkboxes position', 'fluid-checkout' ),
			),
			'default'        => 'order_summary_before_products',
			'autoload'       => false,
		);

		return $settings;
	}



	/**
	 * Execute actions from the Germanized template `review-order-product-table.php` for compatibility.
	 */
	public function do_action_woocommerce_gzd_review_order_before_cart_contents() {
		do_action( 'woocommerce_gzd_review_order_before_cart_contents' );
	}

}

FluidCheckout_WooCommerceGermanized::instance();
