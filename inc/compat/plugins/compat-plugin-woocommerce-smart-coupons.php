<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Smart Coupons (by StoreApps).
 */
class FluidCheckout_WooCommerceSmartCoupons extends FluidCheckout {

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

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Custom AJAX endpoint to normalize notices on checkout
		add_action( 'wc_ajax_fc_sc_apply_coupon', array( $this, 'ajax_apply_coupon' ) );
		add_action( 'wc_ajax_nopriv_fc_sc_apply_coupon', array( $this, 'ajax_apply_coupon' ) );
		add_action( 'wc_ajax_fc_sc_remove_coupon', array( $this, 'ajax_remove_coupon' ) );
		add_action( 'wc_ajax_nopriv_fc_sc_remove_coupon', array( $this, 'ajax_remove_coupon' ) );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Checkout hooks
		$this->checkout_hooks();
	}

	/**
	 * Add or remove checkout hooks.
	 */
	public function checkout_hooks() {
		// Bail if plugin WooCommerce Smart Coupons class is not available
		$class_name = 'WC_SC_Display_Coupons';
		if ( ! class_exists( $class_name ) ) { return; }

		// Make sure class object is loaded
		$class_object = $class_name::get_instance();

		// Bail if class object is not loaded
		if ( ! is_object( $class_object ) ) { return; }

		// Define show available coupons method
		$method_names = array(
			'show_available_coupons_on_classic_checkout',
			'show_available_coupons_before_checkout_form',
		);

		// Get show available coupons method
		$show_available_coupons_method = '';
		foreach ( $method_names as $method_name ) {
			if ( is_callable( array( $class_object, $method_name ) ) ) {
				$show_available_coupons_method = $method_name;
				break;
			}
		}

		// Remove all show coupons hooks from default positions
		foreach ( $method_names as $method_name ) {
			remove_action( 'woocommerce_before_checkout_form', array( $class_object, $method_name ), 11 );
			remove_action( 'woocommerce_checkout_before_customer_details', array( $class_object, $method_name ), 11 );
		}

		// Get position
		$position_hook = $this->get_position_to_display_available_coupons_on_checkout();

		// Add show available coupons hook to selected position
		add_action( $position_hook[ 'hook' ], array( $class_object, $show_available_coupons_method ), $position_hook[ 'priority' ] );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Register script
		wp_register_script( 'fc-compat-woocommerce-smart-coupons', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-smart-coupons/checkout-woocommerce-smart-coupons' ), array( 'jquery', 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-woocommerce-smart-coupons', 'window.addEventListener("load",function(){FCSmartCouponsCheckout.init(fcSettings.SmartCouponsCheckoutSettings);});' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'fc-compat-woocommerce-smart-coupons' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if plugin WooCommerce Smart Coupons class is not available
		$class_name = 'WC_SC_Display_Coupons';
		if ( ! class_exists( $class_name ) ) { return; }

		// Bail if not on checkout page or fragment
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Enqueue assets
		$this->enqueue_assets();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {

		$settings[ 'SmartCouponsCheckoutSettings' ] = array(
			'applyNonce'  => wp_create_nonce( 'fc-sc-apply-coupon' ),
			'removeNonce' => wp_create_nonce( 'fc-sc-remove-coupon' ),
		);

		return $settings;
	}





	/**
	 * AJAX apply a coupon code and return notices as JSON.
	 */
	public function ajax_apply_coupon() {
		// Verify nonce
		check_ajax_referer( 'fc-sc-apply-coupon', 'security' );

		// Get data
		$coupon_code = sanitize_text_field( wp_unslash( $_REQUEST['coupon_code'] ?? '' ) );
		$reference_id = sanitize_text_field( wp_unslash( $_REQUEST['reference_id'] ?? '' ) );

		// Apply coupon
		if ( ! empty( $coupon_code ) ) {
			WC()->cart->apply_coupon( wc_format_coupon_code( $coupon_code ) );
		} else {
			wc_add_notice( WC_Coupon::get_generic_coupon_error( WC_Coupon::E_WC_COUPON_PLEASE_ENTER ), 'error' );
		}

		// Get notices
		ob_start();
		wc_print_notices();
		$message = ob_get_clean();

		// Check if error
		$is_error = false !== strpos( $message, 'woocommerce-error' ) || false !== strpos( $message, 'is-error' );

		// Return JSON
		wp_send_json(
			array(
				'result'       => $is_error ? 'error' : 'success',
				'coupon_code'  => $coupon_code,
				'reference_id' => $reference_id,
				'message'      => $message,
			)
		);
	}

	/**
	 * AJAX remove a coupon code and return notices as JSON.
	 */
	public function ajax_remove_coupon() {
		// Verify nonce
		check_ajax_referer( 'fc-sc-remove-coupon', 'security' );

		// Get data
		$coupon_code = sanitize_text_field( wp_unslash( $_REQUEST['coupon_code'] ?? '' ) );
		$reference_id = sanitize_text_field( wp_unslash( $_REQUEST['reference_id'] ?? '' ) );

		// Remove coupon
		if ( ! empty( $coupon_code ) ) {
			WC()->cart->remove_coupon( wc_format_coupon_code( $coupon_code ) );
			wc_add_notice( __( 'Coupon has been removed.', 'woocommerce' ) );
		}
		else {
			wc_add_notice( __( 'Sorry there was a problem removing this coupon.', 'woocommerce' ), 'error' );
		}

		// Get notices
		ob_start();
		wc_print_notices();
		$message = ob_get_clean();

		// Check if error
		$is_error = false !== strpos( $message, 'woocommerce-error' ) || false !== strpos( $message, 'is-error' );

		// Return JSON
		wp_send_json(
			array(
				'result'       => $is_error ? 'error' : 'success',
				'coupon_code'  => $coupon_code,
				'reference_id' => $reference_id,
				'message'      => $message,
			)
		);
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {
		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'WooCommerce Smart Coupons', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_woocommerce_smart_coupons_options',
			),
		);

		$settings_new = array_merge( $settings_new, apply_filters( 'fc_integrations_woocommerce_smart_coupons_settings',
			array(
				array(	
					'title'           => __( 'Position on checkout page', 'fluid-checkout' ),
					'desc'            => __( 'Choose where to display the available coupons section on the checkout page.', 'fluid-checkout' ),
					'id'              => 'fc_integration_woocommerce_smart_coupons_position_checkout',
					'type'            => 'select',
					'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_woocommerce_smart_coupons_position_checkout' ),
					'autoload'        => false,
					'options'         => array(
						'do_not_show'           => __( 'Do not show', 'fluid-checkout' ),
						'before_progress_bar'   => __( 'Before the progress bar', 'fluid-checkout' ),
						'after_progress_bar'    => __( 'After the progress bar', 'fluid-checkout' ),
						'before_steps'          => __( 'Before the checkout steps', 'fluid-checkout' ),
						'before_coupon_code'    => __( 'Before coupon codes section', 'fluid-checkout' ),
					),
				),
			),
		) );
			
		$settings_new[] = array(
			'type' => 'sectionend',
			'id'    => 'fc_integrations_woocommerce_smart_coupons_options',
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Get position to display available coupons on the checkout page.
	 *
	 * @return  array|null  Position hook and priority, or null to not show the section.
	 */
	public function get_position_to_display_available_coupons_on_checkout() {
		// Get option
		$position = FluidCheckout_Settings::instance()->get_option( 'fc_integration_woocommerce_smart_coupons_position_checkout' );

		// Bail if position is set to "do not show", return as `null`
		if ( 'do_not_show' === $position ) { return null; }

		// Define position hook mapping
		$position_hook_map = array(
			'before_progress_bar'   => array( 'hook' => 'woocommerce_before_checkout_form', 'priority' => 3 ),
			'after_progress_bar'    => array( 'hook' => 'fc_checkout_before', 'priority' => 3 ),
			'before_steps'          => array( 'hook' => 'fc_checkout_before_steps', 'priority' => 5 ),
			'before_coupon_code'    => array( 'hook' => 'fc_before_substep_coupon_codes', 'priority' => 5 ),
		);

		// Maybe set default position
		if ( ! in_array( $position, array_keys( $position_hook_map ) ) ) {
			$position = FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_woocommerce_smart_coupons_position_checkout' );
		}

		// Get hook
		$position_hook = $position_hook_map[ $position ];

		// Return position
		return $position_hook;
	}

}

FluidCheckout_WooCommerceSmartCoupons::instance();
