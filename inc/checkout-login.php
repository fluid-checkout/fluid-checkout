<?php
defined( 'ABSPATH' ) || exit;

/**
 * Feature for AJAX login on the checkout page.
 */
class FluidCheckout_Login extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Check whether the feature is enabled or not.
	 */
	public function is_feature_enabled() {
		// Bail if feature is disabled
		if ( true !== apply_filters( 'fc_enable_checkout_ajax_login', true ) ) { return false; }

		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Bail if feature is not enabled
		if ( ! $this->is_feature_enabled() ) { return; }

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Actions
		add_action( 'wc_ajax_fc_checkout_login', array( $this, 'maybe_process_login' ), 10 );
		add_action( 'wc_ajax_nopriv_fc_checkout_login', array( $this, 'maybe_process_login' ), 10 );

		// Messages
		add_action( 'woocommerce_login_form_start', array( $this, 'output_login_messages_container' ), 10 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Enqueue
		remove_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		remove_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Actions
		remove_action( 'wc_ajax_fc_checkout_login', array( $this, 'maybe_process_login' ), 10 );
		remove_action( 'wc_ajax_nopriv_fc_checkout_login', array( $this, 'maybe_process_login' ), 10 );

		// Messages
		remove_action( 'woocommerce_login_form_start', array( $this, 'output_login_messages_container' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-checkout-login', FluidCheckout_Enqueue::instance()->get_script_url( 'js/checkout-login' ), array( 'jquery', 'fc-utils', 'fc-collapsible-block' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-login', 'window.addEventListener("load",function(){CheckoutLogin.init(fcSettings.checkoutLogin);})' );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'fc-checkout-login' );
		wp_enqueue_style( 'fc-checkout-login' );
	}

	/**
	 * Maybe enqueue assets on the checkout page.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add settings
		$settings[ 'checkoutLogin' ] = apply_filters( 'fc_checkout_login_script_settings', array(
			'checkoutLoginNonce' => wp_create_nonce( 'fc-checkout-login' ),
		) );

		return $settings;
	}



	/**
	 * AJAX Maybe login.
	 * COPIED AND ADAPTED FROM: WC_Form_Handler::process_login().
	 */
	public function maybe_process_login() {
		// CHANGE: Modify the nonce check.
		check_ajax_referer( 'fc-checkout-login', 'security' );

		try {
			// CHANGE: Replace with data received from request.
			$creds = array(
				'user_login'     => isset( $_REQUEST[ 'username' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'username' ] ?? '' ) ) : '',
				'user_password'  => isset( $_REQUEST[ 'password' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'password' ] ?? '' ) ) : '',
				'remember'       => isset( $_REQUEST[ 'rememberme' ] ) ? filter_var( wp_unslash( $_REQUEST[ 'rememberme' ] ?? '' ), FILTER_VALIDATE_BOOLEAN ) : false,
			);

			$validation_error = new WP_Error();
			$validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password'] );

			if ( $validation_error->get_error_code() ) {
				throw new Exception( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $validation_error->get_error_message() );
			}

			if ( empty( $creds['user_login'] ) ) {
				throw new Exception( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . __( 'Username is required.', 'woocommerce' ) );
			}

			// On multisite, ensure user exists on current site, if not add them before allowing login.
			if ( is_multisite() ) {
				$user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

				if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
					add_user_to_blog( get_current_blog_id(), $user_data->ID, 'customer' );
				}
			}

			// Perform the login.
			$user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );

			if ( is_wp_error( $user ) ) {
				throw new Exception( $user->get_error_message() );
			}
			else {

				if ( ! empty( $_POST['redirect'] ) ) {
					$redirect = wp_unslash( $_POST['redirect'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				}
				elseif ( wc_get_raw_referer() ) {
					$redirect = wc_get_raw_referer();
				}
				else {
					$redirect = wc_get_page_permalink( 'myaccount' );
				}

				// CHANGE: Replace redirect with JSON response.
				wp_send_json(
					array(
						'result' => 'success',
					)
				);
			}
		}
		catch ( Exception $e ) {
			// CHANGE: Replace `wc_add_notice` with JSON response.
			do_action( 'woocommerce_login_failed' );
			wp_send_json(
				array(
					'result'   => 'error',
					'message'  => apply_filters( 'login_errors', $e->getMessage() ),
				)
			);
		}
	}



	/**
	 * Output the login messages container element.
	 */
	public function output_login_messages_container() {
		echo '<div class="fc-login-messages"></div>';
	}

}

FluidCheckout_Login::instance();
