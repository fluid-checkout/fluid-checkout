<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout layout and steps
 */
class FluidCheckout_Steps extends FluidCheckout {

	/**
	 * Holds configuration for each checkout step.
	 *
	 * $checkout_steps[]                     array       Defines the checkout steps to be displayed.
	 *      ['step_id']                      string      ID of the checkout step, it will be sanitized with `sanitize_title()`.
	 *      ['step_title']                   string      The checkout step title visible to the user.
	 *      ['priority']                     int         Defines the order the checkout step will be displayed.
	 *      ['next_step_button_classes']     array       Array of CSS classes to add to the "Next step" button.
	 *      ['render_next_step_button']      bool        Whether to display a "Next Step" button at the end of the step. Defaults to `true`.
	 *      ['render_callback']              callable    Function name or callable array to display the contents of the checkout step.
	 *      ['render_condition_callback']    callable    (optional) Function name or callable array to determine if the step should be rendered. If a callback is not provided the checkout step will be displayed.
	 *      ['is_complete_callback']         callable    (optional) Function name or callable array to determine if all required date for the step has been provided. Defaults to `false`, considering the step as 'incomplete' if a callback is not provided.
	 *
	 * @var array
	 **/
	private $checkout_steps   = array();



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
		
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// Custom styles
		add_filter( 'wp_head', array( $this, 'add_custom_styles' ), 10 );
		add_action( 'fc_output_custom_styles', array( $this, 'output_checkout_header_custom_styles' ), 10 );
		add_action( 'fc_output_custom_styles', array( $this, 'output_checkout_page_custom_styles' ), 10 );

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_order_details_styles' ), 10 );

		// Checkout Header
		add_action( 'fc_checkout_header', array( $this, 'output_checkout_header' ), 1 );
		add_action( 'fc_checkout_header_cart_link', array( $this, 'output_checkout_header_cart_link' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_header_cart_link_fragment' ), 10 );

		// Container class
		add_filter( 'fc_content_section_class', array( $this, 'fc_content_section_class' ), 10 );

		// Checkout steps
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_checkout_progress_bar' ), 4 ); // Display before the checkout/cart notices
		add_action( 'wp', array( $this, 'register_default_checkout_steps' ), 10 ); // Register checkout steps for frontend requests
		add_action( 'admin_init', array( $this, 'register_default_checkout_steps' ), 10 ); // Register checkout steps for AJAX requests
		add_action( 'fc_checkout_steps', array( $this, 'output_checkout_steps' ), 10 );
		add_action( 'fc_checkout_after', array( $this, 'output_checkout_sidebar_wrapper' ), 10 );

		// Notices
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_checkout_notices_wrapper_start_tag' ), 5 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'output_checkout_notices_wrapper_end_tag' ), 100 );

		// Contact
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
		add_filter( 'woocommerce_registration_error_email_exists', array( $this, 'change_message_registration_error_email_exists' ), 10 );
		add_action( 'fc_output_step_contact', array( $this, 'output_substep_contact_login' ), 10 );
		add_action( 'fc_output_step_contact', array( $this, 'output_substep_contact' ), 20 );
		add_action( 'wp_footer', array( $this, 'output_login_form_flyout' ), 10 );
		add_action( 'woocommerce_login_form_end', array( $this, 'output_woocommerce_login_form_redirect_hidden_field'), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_contact_text_fragment' ), 10 );

		// Account creation
		add_action( 'fc_checkout_after_contact_fields', array( $this, 'output_form_account_creation' ), 10 );

		// Shipping
		add_filter( 'option_woocommerce_ship_to_destination', array( $this, 'change_woocommerce_ship_to_destination' ), 100, 2 );
		add_action( 'fc_output_step_shipping', array( $this, 'output_substep_shipping_address' ), 10 );
		add_action( 'fc_output_step_shipping', array( $this, 'output_substep_shipping_method' ), 20 );
		add_action( 'fc_before_checkout_shipping_address_wrapper', array( $this, 'output_ship_to_different_address_hidden_field' ), 10 );
		add_filter( 'woocommerce_ship_to_different_address_checked', array( $this, 'set_ship_to_different_address_true' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_address_fields_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_address_text_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_methods_fields_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_methods_text_fragment' ), 10 );

		// Billing Address
		add_action( 'fc_output_step_billing', array( $this, 'output_substep_billing_address' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_billing_address_fields_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_billing_address_text_fragment' ), 10 );

		// Billing Same as Shipping
		add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'output_billing_same_as_shipping_field' ), 100 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'maybe_set_billing_address_same_as_shipping' ), 10 );
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'maybe_set_billing_address_same_as_shipping_on_process_checkout' ), 10 );

		// Payment
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		remove_action( 'woocommerce_checkout_after_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'fc_checkout_payment', 'woocommerce_checkout_payment', 20 );
		add_action( 'fc_output_step_payment', array( $this, 'output_substep_payment' ), 80 );
		add_action( 'fc_output_step_payment', array( $this, 'output_order_review' ), 90 );
		add_action( 'fc_output_step_payment', array( $this, 'output_checkout_place_order' ), 100, 2 );
		add_action( 'fc_checkout_after_order_review_inside', array( $this, 'output_checkout_place_order_for_sidebar' ), 1 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_place_order_fragment' ), 10 );
		add_action( 'woocommerce_order_button_html', array( $this, 'add_place_order_button_wrapper' ), 10 );
		add_filter( 'woocommerce_gateway_icon', array( $this, 'change_payment_gateway_icon_html' ), 10, 2 );

		// Order Review
		add_action( 'fc_checkout_order_review_section', array( $this, 'output_order_review_for_sidebar' ), 10 );
		add_action( 'fc_review_order_shipping', array( $this, 'maybe_output_order_review_shipping_method_chosen' ), 30 );

		// Persisted data
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'update_customer_persisted_data' ), 10 );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'change_default_checkout_field_value_from_session_or_posted_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'unset_session_customer_persisted_data_order_processed' ), 10 );
		add_action( 'wp_login', array( $this, 'unset_all_session_customer_persisted_data' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Unhook WooCommerce functions
		remove_action( 'woocommerce_checkout_billing', array( WC()->checkout, 'checkout_form_billing' ), 10 );
		remove_action( 'woocommerce_checkout_shipping', array( WC()->checkout, 'checkout_form_shipping' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Bail if no additional order fields are present
		$all_fields = WC()->checkout()->get_checkout_fields();
		
		// Prepare the hooks related to the additional order notes substep.
		if ( in_array( 'order', array_keys( $all_fields ) ) ) {
			// Get additional order fields
			$additional_order_fields = WC()->checkout()->get_checkout_fields( 'order' );
			$order_notes_substep_position = 'fc_output_step_shipping';
			
			// Bail if no additional order fields are present
			if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && is_array( $additional_order_fields ) && count( $additional_order_fields ) > 0 ) {
				
				// Maybe change output to the billing step
				if ( ! WC()->cart->needs_shipping() ) {
					$order_notes_substep_position = 'fc_output_step_billing';
				}

				// Add hooks
				add_action( $order_notes_substep_position, array( $this, 'output_substep_order_notes' ), 100 );
				add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_order_notes_text_fragment' ), 10 );

			}
		}
		
		// Run order notes hooks for better compatibility with plugins that rely on them,
		// because they originally run regardless of the order notes fields existence.
		if ( ! in_array( 'order', array_keys( $all_fields ) ) || ! apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) {
			$order_notes_substep_position = apply_filters( 'fc_do_order_notes_hooks_position', 'fc_checkout_after_step_shipping_fields' );
			$order_notes_substep_priority = apply_filters( 'fc_do_order_notes_hooks_priority', 100 );
			add_action( $order_notes_substep_position, array( $this, 'do_order_notes_hooks' ), $order_notes_substep_priority );
		}
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		$add_classes = array(
			'has-fluid-checkout',
			'has-checkout-layout--' . $this->get_checkout_layout(),
		);

		// Add extra class if using the our checkout header, otherwise if using the theme's header don't add this class
		if ( $this->get_hide_site_header_footer_at_checkout() ) {
			$add_classes[] = 'has-checkout-header';
		}

		// Add extra class if displaying the `must-log-in` notice
		$checkout = WC()->checkout();
		if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
			$add_classes[] = 'has-checkout-must-login-notice';
		}

		return array_merge( $classes, $add_classes );
	}



	/**
	 * Output custom styles to the checkout page.
	 */
	public function add_custom_styles() {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return; }

		// Baii if no custom styles were added
		if ( ! has_action( 'fc_output_custom_styles' ) ) { return; }
		?>
		<style id="fc-custom-styles">
			<?php do_action( 'fc_output_custom_styles' ); ?>
		</style>
		<?php
	}

	/**
	 * Output the custom styles for the checkout header background color.
	 */
	public function output_checkout_header_custom_styles() {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return; }
		
		// Get header background color
		$header_background_color = trim( get_option( 'fc_checkout_header_background_color', '' ) );

		// Bail if color is empty
		if ( empty( $header_background_color ) ) { return; }

		echo 'header.fc-checkout-header{background-color:'. $header_background_color .'}';
	}

	/**
	 * Output the custom styles for the checkout page background color.
	 */
	public function output_checkout_page_custom_styles() {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return; }
		
		// Get header background color
		$page_background_color = trim( get_option( 'fc_checkout_page_background_color', '' ) );

		// Bail if color is empty
		if ( empty( $page_background_color ) ) { return; }

		echo 'body.has-fluid-checkout{background-color:'. $page_background_color .'}';
	}



	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() ){ return; }

		// Styles
		wp_enqueue_style( 'fc-checkout-layout', self::$directory_url . 'css/checkout-layout'. self::$asset_version . '.css', NULL, NULL );

		// Checkout steps scripts
		wp_enqueue_script( 'fc-checkout-steps', self::$directory_url . 'js/checkout-steps'. self::$asset_version . '.js', array( 'jquery', 'wc-checkout' ), NULL, true );
		wp_add_inline_script( 'fc-checkout-steps', 'window.addEventListener("load",function(){CheckoutSteps.init();})' );
	}



	/**
	 * Enqueue scripts.
	 */
	public function enqueue_order_details_styles() {
		// Bail if not on order details pages
		if ( ! is_order_received_page() && ! is_wc_endpoint_url( 'view-order' ) ) { return; }

		wp_enqueue_style( 'fc-order-details', self::$directory_url . 'css/order-details'. self::$asset_version . '.css', NULL, NULL );
	}



	/**
	 * Get option for hiding the site's original header and footer at the checkout page.
	 *
	 * @return  boolean  True if should hide the site's original header and footer at the checkout page, false otherwise.
	 */
	public function get_hide_site_header_footer_at_checkout() {
		// Bail if WooCommerce class not available
		if ( ! function_exists( 'WC' ) ) { return false; }

		// Get checkout object.
		$checkout = WC()->checkout();

		// Check if checkout page is showing the checkout form, then check the settings to show theme header or plugin header
		return ( ! ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) ) && 'yes' === get_option( 'fc_hide_site_header_footer_at_checkout', 'yes' );
	}



	/**
	 * Return the list of values accepted for checkout layout.
	 *
	 * @return  array  List of values accepted for checkout layout.
	 */
	public function get_allowed_checkout_layouts() {
		return apply_filters( 'fc_allowed_checkout_layouts', array(
			'multi-step' => __( 'Multi-step', 'fluid-checkout' ),
			'single-step' => __( 'Single step', 'fluid-checkout' ),
		) );
	}



	/**
	 * Get the current checkout layout value.
	 *
	 * @return  string  The name of the currently selected checkout layout option. Defaults to `multi-step`.
	 */
	public function get_checkout_layout() {
		$allowed_values = array_keys( $this->get_allowed_checkout_layouts() );
		$current_value = get_option( 'fc_checkout_layout' );
		$default_value = 'multi-step';

		// Set layout to default value if value not set or not allowed
		if ( ! in_array( $current_value, $allowed_values ) ) {
			$current_value = $default_value;
		}

		return apply_filters( 'fc_get_checkout_layout', $current_value );
	}

	/**
	 * Check if the current checkout layout is set to `multi-step`.
	 *
	 * @return  boolean  `true` if the current checkout layout option value is set to `multi-step`, `false` otherwise.
	 */
	public function is_checkout_layout_multistep() {
		return apply_filters( 'fc_is_checkout_layout_multistep', $this->get_checkout_layout() === 'multi-step' );
	}





	/**
	 * Checkout Header.
	 */



	/**
	 * Output the checkout header.
	 */
	public function output_checkout_header() {
		// Only display our checkout header if the site header is hidden
		if ( ! $this->get_hide_site_header_footer_at_checkout() ) { return; }

		wc_get_template(
			'fc/checkout/checkout-header.php',
			array( 'checkout' => WC()->checkout() )
		);
	}

	/**
	 * Output the checkout header.
	 */
	public function output_checkout_notices_wrapper_start_tag() {
		?>
		<div class="fc-checkout-notices">
		<?php
	}

	/**
	 * Output the checkout header.
	 */
	public function output_checkout_notices_wrapper_end_tag() {
		?>
		</div>
		<?php
	}



	/**
	 * Output the cart link for the checkout header.
	 */
	public function output_checkout_header_cart_link() {
		ob_start();
		wc_cart_totals_order_total_html();
		$link_label_html = str_replace( 'includes_tax', 'includes_tax screen-reader-text', ob_get_clean() );
		?>
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="fc-checkout__cart-link" aria-description="<?php echo esc_attr( __( 'Click to go to the order summary', 'fluid-checkout' ) ); ?>" data-flyout-toggle data-flyout-target="[data-flyout-order-review]"><span class="screen-reader-text"><?php echo esc_html( __( 'Cart total:', 'fluid-checkout' ) ); ?></span> <?php echo $link_label_html; // WPCS: XSS ok. ?></a>
		<?php
	}

	/**
	 * Get html for the cart link for the checkout header.
	 */
	public function get_checkout_header_cart_link() {
		ob_start();
		$this->output_checkout_header_cart_link();
		return ob_get_clean();
	}

	/**
	 * Add cart link for the checkout header as a checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_checkout_header_cart_link_fragment( $fragments ) {
		$html = $this->get_checkout_header_cart_link();
		$fragments['.fc-checkout__cart-link'] = $html;
		return $fragments;
	}

	/**
	 * Output a redirect hidden field to the WooCommerce login form to redirect the user to the checkout or previous page.
	 */
	public function output_woocommerce_login_form_redirect_hidden_field() {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() ){ return; }

		$raw_referrer_url = wc_get_raw_referer() ? wc_get_raw_referer() : wc_get_page_permalink( 'myaccount' );
		$referrer_url = ( is_checkout() || ( array_key_exists( '_redirect', $_GET ) && $_GET[ '_redirect' ] == 'checkout' ) ) ? wc_get_checkout_url() : $raw_referrer_url;

		echo '<input type="hidden" name="redirect" value="' . wp_validate_redirect( $referrer_url, wc_get_page_permalink( 'myaccount' ) ) . '" />';
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function fc_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( $this->get_hide_site_header_footer_at_checkout() ) { return $class; }

		return $class . ' fc-container';
	}





	/**
	 * Checkout Steps.
	 */



	/**
	 * User to sort checkout fields based on priority with uasort.
	 *
	 * @since 1.2.0
	 * @param array $a First step to compare.
	 * @param array $b Second step to compare.
	 * @return int
	 */
	public function checkout_step_priority_uasort_comparison( $a, $b ) {
		/*
		 * We are not guaranteed to get a priority setting.
		 * So don't compare if they don't exist.
		 */
		if ( ! isset( $a['priority'], $b['priority'] ) ) {
			return 0;
		}

		return wc_uasort_comparison( $a['priority'], $b['priority'] );
	}



	/**
	 * Check if a checkout step is registered with the `step_id`.
	 *
	 * @param   string  $step_id  ID of the checkout step.
	 *
	 * @return  boolean           `true` if a checkout step is registered with the `step_id`, `false` otherwise.
	 */
	public function has_checkout_step( $step_id ) {
		// Look for a step with the same id
		foreach ( $this->get_checkout_steps() as $step_args ) {
			if ( $step_args[ 'step_id' ] == sanitize_title( $step_id ) ) {
				return true;
			}
		}

		return false;
	}



	/**
	 * Get the registered checkout steps.
	 *
	 * @return  array  An array of the registered checkout steps. For more details of what is expected see the documentation of the private property `$checkout_steps` of this class.
	 */
	public function get_checkout_steps() {
		return $this->checkout_steps;
	}



	/**
	 * Get the checkout steps for the passed step id.
	 *
	 * @param   string  $step_id  ID of the step.
	 *
	 * @return  mixed             An array with only one value for the step args. The index is preserved from the registered checkout steps list. If not found, returns `false`.
	 */
	public function get_checkout_step( $step_id ) {
		// Look for a step with the same id
		foreach ( $this->get_checkout_steps() as $key => $step_args ) {
			if ( $step_args[ 'step_id' ] == sanitize_title( $step_id ) ) {
				return array( $key, $step_args );
			}
		}

		return false;
	}



	/**
	 * Get the list checkout steps considered complete, those which all required data has been provided.
	 *
	 * @return  array  List of checkout steps which all required data has been provided. The index is preserved from the registered checkout steps list.
	 */
	public function get_complete_steps() {
		$_checkout_steps = $this->get_checkout_steps();
		$complete_steps = array();

		for ( $step_index = 0; $step_index < count( $_checkout_steps ); $step_index++ ) {
			$step_args = $_checkout_steps[ $step_index ];
			$step_id = $step_args[ 'step_id' ];
			$is_complete_callback = array_key_exists( 'is_complete_callback', $step_args ) ? $step_args[ 'is_complete_callback' ] : '__return_false'; // Default step status to 'incomplete'.

			// Add complete steps to the list
			if ( $is_complete_callback && is_callable( $is_complete_callback ) && call_user_func( $is_complete_callback ) ) {
				$complete_steps[ $step_index ] = $step_args;
			}
		}

		// Remove steps after the current steps
		$current_step = $this->get_current_step();
		$current_step_index = array_keys( $current_step )[0];
		foreach ( $complete_steps as $step_index => $step_args ) {
			if ( $step_index >= $current_step_index ) {
				unset( $complete_steps[ $step_index ] );
			}
		}

		return $complete_steps;
	}



	/**
	 * Get the list checkout steps considered incomplete, those missing required data.
	 *
	 * @return  array  List of checkout steps with required data missing. The index is preserved from the registered checkout steps list.
	 */
	public function get_incomplete_steps() {
		$_checkout_steps = $this->get_checkout_steps();
		$incomplete_steps = array();

		for ( $step_index = 0; $step_index < count( $_checkout_steps ); $step_index++ ) {
			$step_args = $_checkout_steps[ $step_index ];
			$step_id = $step_args[ 'step_id' ];
			$is_complete_callback = array_key_exists( 'is_complete_callback', $step_args ) ? $step_args[ 'is_complete_callback' ] : '__return_false'; // Default step status to 'incomplete'.

			// Add incomplete steps to the list
			if ( $is_complete_callback && is_callable( $is_complete_callback ) && ! call_user_func( $is_complete_callback ) ) {
				$incomplete_steps[ $step_index ] = $step_args;
			}
		}

		return $incomplete_steps;
	}



	/**
	 * Get the step arguments for the step ID passed.
	 *
	 * @param   string  $step_id  ID of the step.
	 *
	 * @return  array             Array with arguments of the step.
	 */
	public function get_step( $step_id ) {
		$_checkout_steps = $this->get_checkout_steps();

		foreach ( $_checkout_steps as $step_index => $step_args ) {
			if ( $step_id == $step_args[ 'step_id' ] ) {
				return $step_args;
			}
		}

		return false;
	}

	/**
	 * Get the step arguments for the step next to the step ID passed.
	 *
	 * @param   string  $step_id  ID of the step.
	 *
	 * @return  array             Array with arguments of the next step.
	 */
	public function get_next_step( $step_id ) {
		$_checkout_steps = $this->get_checkout_steps();

		foreach ( $_checkout_steps as $step_index => $step_args ) {
			if ( $step_id == $step_args[ 'step_id' ] ) {
				$next_step_index = $step_index + 1;
				$next_step_args = array_key_exists( $next_step_index, $_checkout_steps ) ? $_checkout_steps[ $next_step_index ] : false;
				return $next_step_args;
			}
		}

		return false;
	}

	/**
	 * Get the current checkout step. The first checkout step which is considered incomplete.
	 *
	 * @return  array  An array with only one value, the first checkout step which is considered incomplete, for `false` if not step is found. The index is preserved from the registered checkout steps list.
	 */
	public function get_current_step() {
		$_checkout_steps = $this->get_checkout_steps();

		for ( $step_index = 0; $step_index < count( $_checkout_steps ); $step_index++ ) {
			$step_args = $_checkout_steps[ $step_index ];
			$step_id = $step_args[ 'step_id' ];
			$is_complete_callback = array_key_exists( 'is_complete_callback', $step_args ) ? $step_args[ 'is_complete_callback' ] : '__return_false'; // Default step status to 'incomplete'.

			// Return first incomplete step
			if ( $is_complete_callback && is_callable( $is_complete_callback ) && ! call_user_func( $is_complete_callback ) ) {
				return array( $step_index => $step_args );
			}
		}

		return false;
	}



	/**
	 * Determine if the step is the current step.
	 *
	 * @param   string  $step_id  Id of the step to check for the "current step" status.
	 *
	 * @return  boolean  `true` if the step is the current step, `false` otherwise.
	 */
	public function is_current_step( $step_id ) {
		// Get checkout current step
		$current_step = $this->get_current_step();
		$current_step_index = ( array_keys( $current_step )[0] ); // First and only value in the array, the key is preserved from the registered checkout steps list
		$current_step_id = $current_step[ $current_step_index ][ 'step_id' ];

		return ( $step_id == $current_step_id );
	}



	/**
	 * Determine if the step is completed.
	 *
	 * @param   string  $step_id  Id of the step to check for the "complete" status.
	 *
	 * @return  boolean  `true` if the step is considered complete, `false` otherwise. Defaults to `false`.
	 */
	public function is_step_complete( $step_id ) {
		$complete_steps = $this->get_complete_steps();

		// Return `true` if step id is found in the complete steps list
		foreach ( $complete_steps as $step_args ) {
			if ( $step_id == $step_args[ 'step_id' ] ) { return true; }
		}

		return false;
	}

	/**
	 * Determine if the step before the checked step is completed.
	 *
	 * @param   string  $step_id  Id of the step to use as a reference to check for the "complete" status of the previous step.
	 *
	 * @return  boolean  `true` if the step is considered complete, `false` otherwise. Defaults to `false`.
	 */
	public function is_prev_step_complete( $step_id ) {
		$complete_steps = $this->get_complete_steps();

		// Return `true` if previous step id is found in the complete steps list
		foreach ( $complete_steps as $step_index => $step_args ) {
			if ( $step_id == $step_args[ 'step_id' ] ) {
				$next_step_index = $step_index - 1;
				if ( array_key_exists( $next_step_index, $complete_steps ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determine if the step after the checked step is completed.
	 *
	 * @param   string  $step_id  Id of the step to use as a reference to check for the "complete" status of the next step.
	 *
	 * @return  boolean  `true` if the step is considered complete, `false` otherwise. Defaults to `false`.
	 */
	public function is_next_step_complete( $step_id ) {
		$complete_steps = $this->get_complete_steps();

		// Return `true` if next step id is found in the complete steps list
		foreach ( $complete_steps as $step_index => $step_args ) {
			
			// Get next step args
			$next_step_index = $step_index + 1;
			$next_step_args = array_key_exists( $next_step_index, $complete_steps ) ? $complete_steps[ $next_step_index ] : false;
			
			// Maybe skip `shipping` step
			if ( is_array( $next_step_args ) && 'shipping' == $next_step_args[ 'step_id' ] && ! WC()->cart->needs_shipping() ) {
				$next_step_index++;
			}

			if ( $step_id == $step_args[ 'step_id' ] ) {
				if ( array_key_exists( $next_step_index, $complete_steps ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the label for the proceed to next step button.
	 *
	 * @param   string  $step_id  ID of the step.
	 */
	public function get_next_step_button_label( $step_id ) {
		$next_step_args = $this->get_next_step( $step_id );
		/** translators: Next checkout step title */
		return sprintf( __( 'Proceed to %s', 'fluid-checkout' ), $next_step_args[ 'step_title' ] );
	}



	/**
	 * Register a new checkout step.
	 *
	 * @param   array  $step_args   Arguments of the checkout step. For more details of what is expected see the documentation of the property `$checkout_steps` of this class.
	 *
	 * @return  boolean             `true` if the step was successfully registered, `false` otherwise. See the PHP log files to troubleshoot the error.
	 */
	public function register_checkout_step( $step_args ) {

		// Check for required step arguments
		$required_args = array( 'step_id', 'step_title', 'priority', 'render_callback' );
		if ( count( array_intersect( $required_args, array_keys( $step_args ) ) ) !== count( $required_args ) ) {
			trigger_error( "One of the required checkout step arguments (step_id, step_title, priority, render_callback) was not provided. Skipping step." . ( array_key_exists( 'step_id', $step_args ) ? " Step id `{$step_args[ 'step_id' ]}`." : '' ), E_USER_WARNING );
			return false;
		}

		// Allow developers to change args for checkout steps at registration
		$step_args = apply_filters( 'fc_register_checkout_step_args', $step_args );

		// Sanitize step id
		$step_args[ 'step_id' ] = sanitize_title( $step_args[ 'step_id' ] );
		$step_id = $step_args[ 'step_id' ];

		// Sanitize value for `render_next_step_button` flag and set default value if needed
		$step_args[ 'render_next_step_button' ] = array_key_exists( 'render_next_step_button', $step_args ) && $step_args[ 'render_next_step_button' ] === false ? false : true;

		// Sanitize "next step" button classes
		$step_args[ 'next_step_button_classes' ] = array_key_exists( 'next_step_button_classes', $step_args ) && is_array( $step_args[ 'next_step_button_classes' ] ) ? $step_args[ 'next_step_button_classes' ] : array();
		foreach ( $step_args[ 'next_step_button_classes' ] as $key => $class ) {
			$step_args[ 'next_step_button_classes' ][ $key ] = sanitize_html_class( $class );
		}

		// Check for duplicate step_id
		if ( $this->has_checkout_step( $step_id ) ) {
			trigger_error( "A checkout step with `step_id = {$step_id}` already exists. Skipping step.", E_USER_WARNING );
			return false;
		}

		// Add step to the list
		$_checkout_steps = $this->get_checkout_steps();
		$_checkout_steps[] = $step_args;

		// Sort steps based on priority.
		uasort( $_checkout_steps, array( $this, 'checkout_step_priority_uasort_comparison' ) );
		$_checkout_steps = array_values( $_checkout_steps );

		// Update registered checkout steps
		$this->checkout_steps = $_checkout_steps;

		return true;
	}

	/**
	 * Deregister a checkout step.
	 *
	 * @param   string  $step_id  ID of the checkout step.
	 *
	 * @return  boolean           `true` if the step was successfully unregistered, `false` otherwise.
	 */
	public function unregister_checkout_step( $step_id ) {
		// Bail if checkout step is not registered
		if ( ! $this->has_checkout_step( $step_id ) ) { return false; }
		
		// Look for a step with the same id
		$step_index = false;
		foreach ( $this->get_checkout_steps() as $key => $step_args ) {
			if ( $step_args[ 'step_id' ] == sanitize_title( $step_id ) ) {
				$step_index = $key;
			}
		}

		// Add step to the list
		$_checkout_steps = $this->get_checkout_steps();
		unset( $_checkout_steps[ $step_index ] );

		// Sort steps based on priority.
		uasort( $_checkout_steps, array( $this, 'checkout_step_priority_uasort_comparison' ) );
		$_checkout_steps = array_values( $_checkout_steps );

		// Update registered checkout steps
		$this->checkout_steps = $_checkout_steps;

		return true;
	}



	/**
	 * Register the default checkout steps supported by this plugin.
	 */
	public function register_default_checkout_steps() {
		// Bail if has already registered steps
		if ( count( $this->checkout_steps ) > 0 ) { return; }
		
		// Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// CONTACT
		$this->register_checkout_step( array(
			'step_id' => 'contact',
			'step_title' => _x( 'Contact', 'Checkout step title', 'fluid-checkout' ),
			'priority' => 10,
			'render_callback' => array( $this, 'output_step_contact' ),
			'is_complete_callback' => array( $this, 'is_step_complete_contact' ),
		) );

		// SHIPPING
		if ( WC()->cart->needs_shipping() ) {
			$this->register_checkout_step( array(
				'step_id' => 'shipping',
				'step_title' => _x( 'Shipping', 'Checkout step title', 'fluid-checkout' ),
				'priority' => 20,
				'render_callback' => array( $this, 'output_step_shipping' ),
				'render_condition_callback' => array( WC()->cart, 'needs_shipping' ),
				'is_complete_callback' => array( $this, 'is_step_complete_shipping' ),
			) );
		}

		// BILLING
		$this->register_checkout_step( array(
			'step_id' => 'billing',
			'step_title' => _x( 'Billing', 'Checkout step title', 'fluid-checkout' ),
			'priority' => 30,
			'render_callback' => array( $this, 'output_step_billing' ),
			'is_complete_callback' => array( $this, 'is_step_complete_billing' ),
		) );

		// PAYMENT
		$this->register_checkout_step( array(
			'step_id' => 'payment',
			'step_title' => _x( 'Payment', 'Checkout step title', 'fluid-checkout' ),
			'priority' => 100,
			'render_callback' => array( $this, 'output_step_payment' ),
			'is_complete_callback' => '__return_false', // Payment step is only complete when the order has been placed and the payment has been accepted, during the checkout process it will always be considered 'incomplete'.
			'render_next_step_button' => false,
		) );

		do_action( 'fc_register_steps' );
	}



	/**
	 * Output the contents of each registered checkout step.
	 */
	public function output_checkout_steps() {
		foreach ( $this->get_checkout_steps() as $step_index => $step_args ) {
			$step_id = $step_args[ 'step_id' ];
			$render_callback = array_key_exists( 'render_callback', $step_args ) ? $step_args[ 'render_callback' ] : null;
			$render_conditional_callback = array_key_exists( 'render_condition_callback', $step_args ) ? $step_args[ 'render_condition_callback' ] : null;

			// Skip step if step `render` function not callable
			if ( ! $render_callback || ! is_callable( $render_callback ) ) {
				trigger_error( "The output function for the checkout step with `step_id = {$step_id}` is not callable. Skipping step render.", E_USER_WARNING );
				continue;
			}

			// Skip step if `render` conditional is not met
			if ( $render_conditional_callback && is_callable( $render_conditional_callback ) && ! call_user_func( $render_conditional_callback ) ) { continue; }

			// Output the step
			$this->output_step_start_tag( $step_args, $step_index );
			call_user_func( $render_callback );
			$this->output_step_end_tag( $step_args, $step_index );
		}
	}




	/**
	 * Checkout Progress Bar
	 */



	/**
	 * Output the checkout progress bar.
	 */
	public function output_checkout_progress_bar() {
		// Bail if not multi-step checkout layout
		if ( ! $this->is_checkout_layout_multistep() ) { return; }

		$_checkout_steps = $this->get_checkout_steps();

		// Get step count
		$steps_count = count( $_checkout_steps );

		// Get checkout current step
		$current_step = $this->get_current_step();
		$current_step_index = ( array_keys( $current_step )[0] ); // First and only value in the array, the key is preserved from the registered checkout steps list
		$current_step_id = $current_step[ $current_step_index ][ 'step_id' ];
		$current_step_number = $current_step_index + 1;

		// Get step count html
		$steps_count_label_html = apply_filters(
			'fc_steps_count_html',
			sprintf(
				/* translators: %1$s is replaced with html for "current checkout step number", %2$s is replaced with html for "total number of checkout steps". */
				esc_html( __( 'Step %1$s of %2$s', 'fluid-checkout' ) ),
				'<span class="fc-progress-bar__current-step" data-step-count-current>' . esc_html( $current_step_number ) . '</span>',
				'<span class="fc-progress-bar__total-steps" data-step-count-total>' . esc_html( $steps_count ) . '</span>'
			),
			$_checkout_steps,
			$current_step
		);

		// Attributes
		$progress_bar_attributes = array(
			'class' => 'fc-progress-bar',
			'data-progress-bar' => true,

		);
		$progress_bar_inner_attributes = array(
			'class' => 'fc-progress-bar__inner',
		);

		// Sticky state attributes
		if ( get_option( 'fc_enable_checkout_sticky_progress_bar', 'yes' ) === 'yes' ) {
			$progress_bar_attributes = array_merge( $progress_bar_attributes, array(
				'data-sticky-states' => true,
				'data-sticky-relative-to' => '.fc-checkout-header',
				'data-sticky-container' => 'div.woocommerce',
			) );

			$progress_bar_inner_attributes = array_merge( $progress_bar_inner_attributes, array(
				'data-sticky-states-inner' => true,
			) );
		}

		// Filter attributes
		$progress_bar_attributes = apply_filters( 'fc_checkout_progress_bar_attributes', $progress_bar_attributes );
		$progress_bar_inner_attributes = apply_filters( 'fc_checkout_progress_bar_inner_attributes', $progress_bar_inner_attributes );

		// Convert attributes to string
		$progress_bar_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $progress_bar_attributes ), $progress_bar_attributes ) );
		$progress_bar_attributes_inner_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $progress_bar_inner_attributes ), $progress_bar_inner_attributes ) );
		?>
		<div <?php echo $progress_bar_attributes_str; // WPCS: XSS ok. ?>>
			<div <?php echo $progress_bar_attributes_inner_str; // WPCS: XSS ok. ?>>

				<div class="fc-progress-bar__count" data-step-count-text><?php echo $steps_count_label_html; // WPCS: XSS ok. ?></div>
				<div class="fc-progress-bar__bars" data-progress-bar data-step-count="<?php echo esc_attr( $steps_count ); ?>">
					<?php
					foreach ( $_checkout_steps as $step_index => $step_args ) :
						$step_bar_class = $step_index < $current_step_index ? 'is-complete' : ( $step_index == $current_step_index ? 'is-current' : '' );
						?>
						<span class="fc-progress-bar__bar <?php echo esc_attr( $step_bar_class ); ?>" data-step-id="<?php echo esc_attr( $step_args[ 'step_id' ] ); ?>" data-step-index="<?php echo esc_attr( $step_index ); ?>"></span>
					<?php
					endforeach;
					?>
				</div>

			</div>
		</div>
		<?php
	}



	/**
	 * Output checkout step start tag.
	 *
	 * @param   array  $step_args   Arguments of the checkout step. For more details of what is expected see the documentation of the property `$checkout_steps` of this class.
	 * @param   array  $step_index  Position of the checkout step in the steps order, uses zero-based index,`0` is the first step.
	 */
	public function output_step_start_tag( $step_args, $step_index ) {
		$step_id = $step_args[ 'step_id' ];
		$step_title = apply_filters( "fc_step_title_{$step_id}", $step_args[ 'step_title' ] );
		$step_title_id = 'fc-step__title--' . $step_args[ 'step_id' ];

		$step_attributes = array(
			'class' => 'fc-checkout-step',
			'data-step-id' => ! empty( $step_id ) && $step_id != null ? $step_id : '',
			'data-step-label' => $step_title,
			'aria-label' => $step_title,
			'data-step-index' => $step_index,
			'data-step-complete' => $this->is_step_complete( $step_id ),
			'data-step-current' => $this->is_current_step( $step_id ),
		);

		// Maybe add class for previous step completed
		if ( $this->is_prev_step_complete( $step_id ) ) {
			$step_attributes['class'] .= ' fc-checkout-step--prev-step-complete';
		}

		// Maybe add class for next step completed
		if ( $this->is_next_step_complete( $step_id ) ) {
			$step_attributes['class'] .= ' fc-checkout-step--next-step-complete';
		}
		else {
			$step_attributes['class'] .= ' fc-checkout-step--next-step-incomplete';
		}

		$step_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $step_attributes ), $step_attributes ) );
		echo '<section ' . $step_attributes_str . '>'; // WPCS: XSS ok.
		echo '<h2 id="' . esc_attr( $step_title_id ) . '" class="fc-step__title screen-reader-text">' . wp_kses( $step_title, array( 'span' => array( 'class' => array() ), 'i' => array( 'class' => array() ) ) ) . '</h2>';
	}

	/**
	 * Output checkout step end tag.
	 *
	 * @param   array  $step_args   Arguments of the checkout step. For more details of what is expected see the documentation of the property `$checkout_steps` of this class.
	 * @param   array  $step_index  Position of the checkout step in the steps order, uses zero-based index,`0` is the first step.
	 */
	public function output_step_end_tag( $step_args, $step_index ) {
		// Maybe output the "Next step" button
		if ( $this->is_checkout_layout_multistep() && array_key_exists( 'render_next_step_button', $step_args ) && $step_args[ 'render_next_step_button' ] ) :
			$button_label = apply_filters( 'fc_next_step_button_label', $this->get_next_step_button_label( $step_args[ 'step_id' ] ), $step_args[ 'step_id' ] );

			$button_attributes = array(
				'class' => implode( ' ', array_merge( array( 'fc-step__next-step' ), apply_filters( 'fc_next_step_button_classes', array( 'button' ) ), $step_args[ 'next_step_button_classes' ] ) ),
				'data-step-next' => true,
			);
			$button_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $button_attributes ), $button_attributes ) );
			?>
			<div class="fc-step__actions">
				<button type="button" <?php echo $button_attributes_str; // WPCS: XSS ok. ?>><?php echo esc_html( $button_label ); ?></button>
			</div>
			<?php
		endif;

		echo '</section>';
	}



	/**
	 * Get the checkout substep title html.
	 *
	 * @param   string  $substep_id     Id of the substep.
	 * @param   string  $substep_title  Title of the substep.
	 */
	public function get_substep_title_html( $substep_id, $substep_title ) {
		$html = '';
		$substep_title = apply_filters( "fc_substep_title_{$substep_id}", $substep_title );
		
		if ( ! empty( $substep_title ) ) {
			$html = '<h3 class="fc-step__substep-title fc-step__substep-title--' . esc_attr( $substep_id ) . '">' . wp_kses( $substep_title, array( 'span' => array( 'class' => array() ), 'i' => array( 'class' => array() ) ) ) . '</h3>';
		}

		return $html;
	}



	/**
	 * Output checkout substep start tag.
	 *
	 * @param   string  $step_id                Id of the step in which the substep will be rendered.
	 * @param   string  $substep_id             Id of the substep.
	 * @param   string  $substep_title          Title of the substep.
	 * @param   array   $additional_attributes  Additional HTML attributes to add to the substep element.
	 */
	public function output_substep_start_tag( $step_id, $substep_id, $substep_title, $additional_attributes = array() ) {
		$substep_attributes = array_merge( $additional_attributes, array(
			'class' => array_key_exists( 'class', $additional_attributes ) ? 'fc-step__substep ' . $additional_attributes['class'] : 'fc-step__substep',
			'data-substep-id' => $substep_id,
		) );
		$substep_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_attributes ), $substep_attributes ) );
		?>
		<div <?php echo $substep_attributes_str; // WPCS: XSS ok. ?>>
			<?php
			echo $this->get_substep_title_html( $substep_id, $substep_title ); // WPCS: XSS ok.
			do_action( "fc_before_substep_{$substep_id}" , $step_id, $substep_id );
	}

	/**
	 * Output checkout substep end tag.
	 *
	 * @param   string  $step_id        Id of the step in which the substep will be rendered.
	 * @param   string  $substep_id     Id of the substep.
	 * @param   string  $substep_title  Title of the substep.
	 */
	public function output_substep_end_tag( $step_id, $substep_id, $substep_title, $output_edit_buttons = true ) {
			do_action( "fc_after_substep_{$substep_id}" , $step_id, $substep_id, $output_edit_buttons );
			?>

			<?php if ( $output_edit_buttons && $this->is_checkout_layout_multistep() ) : ?>
				<a tabindex="0" role="button" class="fc-step__substep-edit" data-step-edit aria-label="<?php echo sprintf( __( 'Change: %s', 'fluid-checkout' ), $substep_title ); ?>"><?php echo esc_html( apply_filters( 'fc_substep_change_button_label', _x( 'Change', 'Checkout substep change link label', 'fluid-checkout' ) ) ); ?></a>
				<button class="fc-step__substep-save <?php echo esc_attr( apply_filters( 'fc_substep_save_button_classes', 'button' ) ); ?>" data-step-save><?php echo esc_html( apply_filters( 'fc_substep_save_button_label', _x( 'Save changes', 'Checkout substep save link label', 'fluid-checkout' ) ) ); ?></button>
			<?php endif; ?>

		</div>
		<?php
	}



	/**
	 * Output checkout substep start tag.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 * @param   string  $substep_id  Id of the substep.
	 */
	public function output_substep_fields_start_tag( $step_id, $substep_id, $collapsible = true ) {
		$substep_attributes = array(
			'id' => 'fc-substep__fields--' . $substep_id,
			'class' => 'fc-step__substep-fields fc-substep__fields--' . $substep_id,
			'data-substep-id' => $substep_id,
		);

		$substep_inner_attributes = array(
			'class' => 'fc-step__substep-fields-inner',
		);

		// Add collapsible-block attributes for multistep layout
		if ( $collapsible && $this->is_checkout_layout_multistep() ) {
			$is_step_complete = $this->is_step_complete( $step_id );

			$substep_attributes = array_merge( $substep_attributes, array(
				'data-collapsible' => true,
				'data-collapsible-content' => true,
				'data-autofocus' => true,
				'data-collapsible-initial-state' => $is_step_complete ? 'collapsed' : 'expanded',
			) );

			$substep_inner_attributes = array(
				'class' => $substep_inner_attributes[ 'class' ] . ' collapsible-content__inner',
			);
		}

		$substep_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_attributes ), $substep_attributes ) );
		$substep_inner_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_inner_attributes ), $substep_inner_attributes ) );
		?>
		<div <?php echo $substep_attributes_str; // WPCS: XSS ok. ?>>
			<div <?php echo $substep_inner_attributes_str; // WPCS: XSS ok. ?>>
			<?php
	}

	/**
	 * Output checkout substep end tag.
	 */
	public function output_substep_fields_end_tag() {
			?>
			</div>
		</div>
		<?php
	}



	/**
	 * Output checkout substep start tag.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 * @param   string  $substep_id  Id of the substep.
	 */
	public function output_substep_text_start_tag( $step_id, $substep_id ) {
		$is_step_complete = $this->is_step_complete( $step_id );
		$substep_attributes = array(
			'id' => 'fc-substep__text--' . $substep_id,
			'class' => 'fc-step__substep-text',
			'data-substep-id' => $substep_id,
			'data-collapsible' => true,
			'data-collapsible-content' => true,
			'data-collapsible-initial-state' => $is_step_complete ? 'expanded' : 'collapsed',
		);

		$substep_inner_attributes = array(
			'class' => 'collapsible-content__inner',
		);

		$substep_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_attributes ), $substep_attributes ) );
		$substep_inner_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_inner_attributes ), $substep_inner_attributes ) );
		?>
		<div <?php echo $substep_attributes_str; // WPCS: XSS ok. ?>>
			<div <?php echo $substep_inner_attributes_str; // WPCS: XSS ok. ?>>
			<?php
	}

	/**
	 * Output checkout substep end tag.
	 */
	public function output_substep_text_end_tag() {
			?>
			</div>
		</div>
		<?php
	}



	/**
	 * Output checkout expansible form section start tag.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 * @param   string  $section_id  Id of the substep.
	 */
	public function output_expansible_form_section_start_tag( $section_id, $toggle_label, $args = array() ) {
		$section_id_esc = esc_attr( $section_id );

		// Initial state
		$initial_state = array_key_exists( 'initial_state', $args ) && $args['initial_state'] === 'expanded' ? 'expanded' : 'collapsed';

		// Section attributes
		$section_attributes = array( 'class' => 'fc-expansible-form-section' );

		// Merge section attributes
		if ( array_key_exists( 'section_attributes', $args ) && is_array( $args['section_attributes'] ) ) {
			$section_class_esc = esc_attr( $section_attributes['class'] );

			$section_attributes = array_merge( $section_attributes, $args['section_attributes'] );

			// Merge class attribute
			$section_attributes['class'] = array_key_exists( 'class', $args['section_attributes'] ) ? $section_class_esc . ' ' . esc_attr( $args['section_attributes']['class'] ) : $section_class_esc;
		}

		// Section toggle attributes
		$section_toggle_attributes = array(
			'id' => 'fc-expansible-form-section__toggle--' . $section_id_esc,
			'class' => 'fc-expansible-form-section__toggle fc-expansible-form-section__toggle--' . $section_id_esc . ' ' . ( $initial_state === 'expanded' ? 'is-collapsed' : 'is-expanded' ), // Toggle is collapsed when the section is set to expanded
			'data-collapsible' => true,
			'data-collapsible-content' => true,
			'data-collapsible-initial-state' => $initial_state === 'expanded' ? 'collapsed' : 'expanded', // Toggle is collapsed when the section is set to expanded
		);

		// Section toggle inner attributes
		$section_toggle_inner_attributes = array(
			'class' => 'collapsible-content__inner',
		);

		// Toggle element attributes
		$toggle_attributes = array(
			'href' => '#fc-expansible-form-section__content--' . $section_id_esc,
			'class' => 'expansible-section__toggle-plus expansible-section__toggle-plus--' . $section_id_esc,
			'data-collapsible-handler' => true,
			'data-collapsible-targets' => implode( ',', array(
				'fc-expansible-form-section__toggle--' . $section_id_esc,
				'fc-expansible-form-section__content--' . $section_id_esc,
			) ),
		);

		// Section content attributes
		$section_content_attributes = array(
			'id' => 'fc-expansible-form-section__content--' . $section_id_esc,
			'class' => 'fc-expansible-form-section__content fc-expansible-form-section__content--' . $section_id_esc . ' ' . ( $initial_state === 'expanded' ? 'is-expanded' : 'is-collapsed' ),
			'data-collapsible' => true,
			'data-collapsible-content' => true,
			'data-collapsible-initial-state' => $initial_state,
		);

		// Section content inner attributes
		$section_content_inner_attributes = array(
			'class' => 'collapsible-content__inner',
		);

		$section_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $section_attributes ), $section_attributes ) );
		$section_toggle_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $section_toggle_attributes ), $section_toggle_attributes ) );
		$section_toggle_inner_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $section_toggle_inner_attributes ), $section_toggle_inner_attributes ) );
		$toggle_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $toggle_attributes ), $toggle_attributes ) );
		$section_content_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $section_content_attributes ), $section_content_attributes ) );
		$section_content_inner_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $section_content_inner_attributes ), $section_content_inner_attributes ) );
		?>
		<div <?php echo $section_attributes_str; // WPCS: XSS ok. ?>>
			<div <?php echo $section_toggle_attributes_str; // WPCS: XSS ok. ?>>
				<div <?php echo $section_content_inner_attributes_str; // WPCS: XSS ok. ?>>
					<a <?php echo $toggle_attributes_str; // WPCS: XSS ok. ?>>
						<?php echo esc_html( $toggle_label ); ?>
					</a>
				</div>
			</div>

			<div <?php echo $section_content_attributes_str; // WPCS: XSS ok. ?>>
				<div <?php echo $section_content_inner_attributes_str; // WPCS: XSS ok. ?>>
				<?php
	}

	/**
	 * Output checkout expansible form section end tag.
	 */
	public function output_expansible_form_section_end_tag() {
				?>
				</div>
			</div>
		</div>
		<?php
	}





	/**
	 * Checkout Step: Contact
	 */



	/**
	 * Output contact step.
	 */
	public function output_step_contact() {
		do_action( 'fc_output_step_contact', 'contact' );
	}

	/**
	 * Output contact substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_contact( $step_id ) {
		$substep_id = 'contact';
		$substep_title = __( 'My contact', 'fluid-checkout' );
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title, apply_filters( "fc_substep_{$substep_id}_attributes", array() ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_step_contact_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_contact();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id, $substep_title, true );
	}

	/**
	 * Output contact step fields.
	 */
	public function output_step_contact_fields() {
		do_action( 'woocommerce_checkout_before_customer_details' );

		wc_get_template(
			'fc/checkout/form-contact.php',
			array(
				'checkout'			=> WC()->checkout(),
				'display_fields'	=> $this->get_contact_step_display_field_ids(),
			)
		);
	}



	/**
	 * Get contact substep in text format for when the step is completed.
	 */
	public function get_substep_text_contact() {
		$customer = WC()->customer;
		$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--contact">';
		$html .= '<div class="fc-step__substep-text-line">' . esc_html( $customer->get_billing_email() ) . '</div>';

		// Maybe add notice for account creation
		if ( get_option( 'fc_show_account_creation_notice_checkout_contact_step_text', 'true' ) === 'true' ) {
			$parsed_posted_data = $this->get_parsed_posted_data();
			if ( array_key_exists( 'createaccount', $parsed_posted_data ) && $parsed_posted_data[ 'createaccount' ] == '1' ) {
				$html .= '<div class="fc-step__substep-text-line"><em>' . esc_html( __( 'An account will be created with the information provided.', 'fluid-checkout' ) ) . '</em></div>';
			}
		}

		$html .= '</div>';

		return apply_filters( 'fc_substep_contact_text', $html );
	}

	/**
	 * Add Contact text format as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_contact_text_fragment( $fragments ) {
		$html = $this->get_substep_text_contact();
		$fragments['.fc-step__substep-text-content--contact'] = $html;
		return $fragments;
	}

	/**
	 * Output contact substep in text format for when the step is completed.
	 */
	public function output_substep_text_contact() {
		echo $this->get_substep_text_contact();
	}



	/**
	 * Determines if all required data for the contact step has been provided.
	 *
	 * @return  boolean  `true` if the user has provided all the required data for this step, `false` otherwise. Defaults to `false`.
	 */
	public function is_step_complete_contact() {
		$checkout = WC()->checkout();
		$is_step_complete = true;

		// Check required data
		$fields = $checkout->get_checkout_fields( 'billing' );
		$contact_display_field_keys = $this->get_contact_step_display_field_ids();

		foreach ( $contact_display_field_keys as $field_key ) {
			$field = array_key_exists( $field_key, $fields ) ? $fields[ $field_key ] : array();
			if ( array_key_exists( 'required', $field ) && $field[ 'required' ] === true && ! $checkout->get_value( $field_key ) ) {
				$is_step_complete = false;
				break;
			}
		}

		return apply_filters( 'fc_is_step_complete_contact', $is_step_complete );
	}



	/**
	 * Output account creation form fields.
	 */
	public function output_form_account_creation() {
		wc_get_template(
			'fc/checkout/form-account-creation.php',
			array(
				'checkout'			=> WC()->checkout(),
			)
		);
	}



	/**
	 * Return list of checkout fields for contact step.
	 */
	public function get_contact_step_display_field_ids() {
		return apply_filters( 'fc_checkout_contact_step_field_ids', array(
			'billing_email',
		) );
	}



	/**
	 * Output the login form flyout block for the checkout page.
	 */
	public function output_login_form_flyout() {
		// Bail if user already logged in or login at checkout is disabled
		if ( ! is_checkout() || is_user_logged_in() || 'yes' !== get_option( 'woocommerce_enable_checkout_login_reminder' ) ) { return; };

		wc_get_template(
			'fc/checkout/form-contact-login-modal.php',
			array(
				'checkout'			=> WC()->checkout(),
			)
		);
	}

	/**
	 * Output contact step fields.
	 */
	public function output_substep_contact_login_button() {
		// Do not output if login at checkout is disabled
		if ( 'yes' !== get_option( 'woocommerce_enable_checkout_login_reminder' ) ) { return; }

		wc_get_template(
			'fc/checkout/form-contact-login.php',
			array(
				'checkout'			=> WC()->checkout(),
			)
		);
	}

	/**
	 * Output contact substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_contact_login( $step_id ) {
		// Bail if user already logged in
		if ( is_user_logged_in() ) { return; };

		$substep_id = 'contact_login';
		$this->output_substep_start_tag( $step_id, $substep_id, null, apply_filters( "fc_substep_{$substep_id}_attributes", array() ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_contact_login_button();
		$this->output_substep_fields_end_tag();

		$this->output_substep_end_tag( $step_id, $substep_id, '', false );
	}



	/**
	 * Change the error message for existing email while creating a new account at the checkout page.
	 *
	 * @param   string  $message_html  Error message for email existent while creating a new account.
	 */
	public function change_message_registration_error_email_exists( $message_html ) {
		// Bail if not at the checkout page
		if ( ! is_checkout() ) { return $message_html; }

		$message_html = str_replace( '<a href="#" class="showlogin', '<a href="#" data-flyout-toggle data-flyout-target="[data-flyout-checkout-login]" class="', $message_html );
		return $message_html;
	}





	/**
	 * Checkout Step: Shipping.
	 */



	/**
	 * Output shipping step.
	 */
	public function output_step_shipping() {
		do_action( 'fc_output_step_shipping', 'shipping' );
	}

	/**
	 * Output shipping address substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_shipping_address( $step_id ) {
		$substep_id = 'shipping_address';
		$substep_title = __( 'Shipping to', 'fluid-checkout' );
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title, apply_filters( "fc_substep_{$substep_id}_attributes", array() ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_shipping_address_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_shipping_address();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id, $substep_title, true );
	}

	/**
	 * Output shipping method substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_shipping_method( $step_id ) {
		$substep_id = 'shipping_method';
		$substep_title = __( 'Shipping method', 'fluid-checkout' );
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title, apply_filters( "fc_substep_{$substep_id}_attributes", array() ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_shipping_methods_available();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_shipping_method();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id, $substep_title, true );
	}

	/**
	 * Output order notes substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_order_notes( $step_id ) {
		$substep_id = 'order_notes';
		$substep_title = __( 'Additional notes', 'fluid-checkout' );
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title, apply_filters( "fc_substep_{$substep_id}_attributes", array() ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_additional_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_order_notes();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id, $substep_title, true );
	}

	/**
	 * Run additional order notes hooks, for when the order notes fields are disabled.
	 */
	public function do_order_notes_hooks() {
		do_action( 'woocommerce_before_order_notes', WC()->checkout() );
		do_action( 'woocommerce_after_order_notes', WC()->checkout() );
	}



	/**
	 * Get list of shipping fields ignored at the shipping address substep as they were moved to another substep.
	 *
	 * @return  array  List of shipping fields to ignore at shipping address substep.
	 */
	public function get_shipping_address_ignored_shipping_field_ids() {
		$shipping_ignored_field_ids = $this->get_contact_step_display_field_ids();
		return $shipping_ignored_field_ids;
	}



	/**
	 * Output shipping address step fields.
	 */
	public function output_substep_shipping_address_fields() {
		$checkout = WC()->checkout();
		
		// Filter out shipping fields moved to another step
		$shipping_fields = $checkout->get_checkout_fields( 'shipping' );
		$shipping_fields = array_filter( $shipping_fields, function( $key ) {
			return ! in_array( $key, $this->get_shipping_address_ignored_shipping_field_ids() );
		}, ARRAY_FILTER_USE_KEY );

		wc_get_template(
			'checkout/form-shipping.php',
			array(
				'checkout'          => $checkout,
				'display_fields'	=> array_keys( $shipping_fields ),
			)
		);
	}

	/**
	 * Get shipping address step fields html.
	 */
	public function get_substep_shipping_address_fields() {
		ob_start();
		$this->output_substep_shipping_address_fields();
		return ob_get_clean();
	}

	/**
	 * Add shipping address fields as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_address_fields_fragment( $fragments ) {
		$html = $this->get_substep_shipping_address_fields();
		$fragments['.woocommerce-shipping-fields'] = $html;
		return $fragments;
	}



	/**
	 * Output shipping address substep in text format for when the step is completed.
	 */
	public function get_substep_text_shipping_address() {
		$customer = WC()->customer;

		$address_data = array(
			'first_name' => $customer->get_shipping_first_name(),
			'last_name' => $customer->get_shipping_last_name(),
			'company' => $customer->get_shipping_company(),
			'address_1' => $customer->get_shipping_address_1(),
			'address_2' => $customer->get_shipping_address_2(),
			'city' => $customer->get_shipping_city(),
			'state' => $customer->get_shipping_state(),
			'country' => $customer->get_shipping_country(),
			'postcode' => $customer->get_shipping_postcode(),
		);

		$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--shipping-address">';
		$html .= '<div class="fc-step__substep-text-line">' . WC()->countries->get_formatted_address( $address_data ) . '</div>'; // WPCS: XSS ok.
		$html .= '</div>';

		return apply_filters( 'fc_substep_shipping_address_text', $html );
	}

	/**
	 * Add shipping address text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_address_text_fragment( $fragments ) {
		$html = $this->get_substep_text_shipping_address();
		$fragments['.fc-step__substep-text-content--shipping-address'] = $html;
		return $fragments;
	}

	/**
	 * Output shipping address available.
	 *
	 * @access public
	 */
	public function output_substep_text_shipping_address() {
		echo $this->get_substep_text_shipping_address();
	}



	/**
	 * Get shipping method substep in text format for when the step is completed.
	 */
	public function get_substep_text_shipping_method() {
		$packages = WC()->shipping()->get_packages();

		$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--shipping-method">';

		foreach ( $packages as $i => $package ) {
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;
			$chosen_method_label = $method ? wc_cart_totals_shipping_method_label( $method ) : __( 'Not selected yet.', 'fluid-checkout' );

			// TODO: Maybe handle multiple packages
			// $package_name = apply_filters( 'woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'woocommerce' ), ( $i + 1 ) ) : _x( 'Shipping', 'shipping packages', 'woocommerce' ), $i, $package );

			$html .= '<div class="fc-step__substep-text-line">' . wp_kses( $chosen_method_label, array( 'span' => array( 'class' => '' ), 'bdi' => array(), 'strong' => array() ) ) . '</div>';
		}

		$html .= '</div>';

		return apply_filters( 'fc_substep_shipping_methods_text', $html );
	}

	/**
	 * Add shipping methods text format as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_methods_text_fragment( $fragments ) {
		$html = $this->get_substep_text_shipping_method();
		$fragments['.fc-step__substep-text-content--shipping-method'] = $html;
		return $fragments;
	}

	/**
	 * Output shipping method substep in text format for when the step is completed.
	 */
	public function output_substep_text_shipping_method() {
		echo $this->get_substep_text_shipping_method();
	}



	/**
	 * Output order notes substep in text format for when the step is completed.
	 */
	public function get_substep_text_order_notes() {
		$order_notes = $this->get_checkout_field_value_from_session( 'order_comments' );

		$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--order-notes">';

		// The order notes value
		if ( ! empty( $order_notes ) ) {
			$html .= '<div class="fc-step__substep-text-line">' . esc_html( $order_notes ) . '</div>';
		}
		// "No order notes" notice.
		else {
			$html .= '<div class="fc-step__substep-text-line">' . esc_html( apply_filters( 'fc_no_order_notes_order_review_notice', _x( 'None.', 'Notice for no order notes provided', 'fluid-checkout' ) ) ) . '</div>';
		}

		$html .= '</div>';

		return apply_filters( 'fc_substep_order_notes_text', $html );
	}

	/**
	 * Add order notes text format as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_order_notes_text_fragment( $fragments ) {
		$html = $this->get_substep_text_order_notes();
		$fragments['.fc-step__substep-text-content--order-notes'] = $html;
		return $fragments;
	}

	/**
	 * Output order notes substep in text format for when the step is completed.
	 */
	public function output_substep_text_order_notes() {
		echo $this->get_substep_text_order_notes();
	}



	/**
	 * Determines if all required data for the shipping step has been provided.
	 *
	 * @return  boolean  `true` if the user has provided all the required data for this step, `false` otherwise. Defaults to `false`.
	 */
	public function is_step_complete_shipping() {
		$checkout = WC()->checkout();
		$is_step_complete = true;

		// Check required data for shipping address
		if ( WC()->cart->needs_shipping_address() ) {
			$fields = $checkout->get_checkout_fields( 'shipping' );
			foreach ( $fields as $field_key => $field ) {
				if ( array_key_exists( 'required', $field ) && $field[ 'required' ] === true && ! $checkout->get_value( $field_key ) ) {
					$is_step_complete = false;
					break;
				}
			}
		}

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( ! $chosen_method || empty( $chosen_method ) ) {
				$is_step_complete = false;
				break;
			}
		}

		return apply_filters( 'fc_is_step_complete_shipping', $is_step_complete );
	}



	/**
	 * Output "ship to different address" hidden field.
	 */
	public function output_ship_to_different_address_hidden_field() {
		?>
		<input type="hidden" name="ship_to_different_address" value="1" />
		<?php
	}

	/**
	 * Set to always ship to shipping address.
	 */
	public function set_ship_to_different_address_true() {
		return 1;
	}



	/**
	 * Get shipping methods available markup.
	 *
	 * @access public
	 */
	public function get_shipping_methods_available() {
		ob_start();

		$packages = WC()->shipping->get_packages();

		do_action( 'fc_shipping_methods_before_packages' );

		echo '<div class="fc-shipping-method__packages">';

		$first_item = true;
		foreach ( $packages as $i => $package ) {
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$product_names = array();

			if ( sizeof( $packages ) > 1 ) {
				foreach ( $package['contents'] as $item_id => $values ) {
					$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
				}
				$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
			}

			wc_get_template( 'fc/cart/shipping-methods-available.php', array(
				'package'					=> $package,
				'available_methods'			=> $package['rates'],
				'show_package_details'		=> sizeof( $packages ) > 1,
				'show_shipping_calculator'	=> is_cart() && $first_item,
				'package_details'			=> implode( ', ', $product_names ),
				/* translators: %d: shipping package number */
				'package_name'              => apply_filters( 'woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'woocommerce' ), ( $i + 1 ) ) : _x( 'Shipping', 'shipping packages', 'woocommerce' ), $i, $package ),
				'package_index'				=> $i,
				'chosen_method'				=> $chosen_method,
			) );

			$first_item = false;
		}

		echo '</div>';

		do_action( 'fc_shipping_methods_after_packages' );

		return ob_get_clean();
	}

	/**
	 * Add shipping methods available as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_methods_fields_fragment( $fragments ) {
		$html = $this->get_shipping_methods_available();
		$fragments['.fc-shipping-method__packages'] = $html;
		return $fragments;
	}

	/**
	 * Output shipping methods available.
	 *
	 * @access public
	 */
	public function output_shipping_methods_available() {
		echo $this->get_shipping_methods_available();
	}

	/**
	 * Change shipping methods full label including price with markup necessary for displaying price as a separate element.
	 *
	 * @param object|string $method Either the name of the method's class, or an instance of the method's class.
	 *
	 * @return string $label Shipping rate label.
	 */
	public function get_cart_shipping_methods_label( $method ) {
		$label     = sprintf( apply_filters( 'fc_shipping_method_option_label_markup', '<span class="shipping-method__option-text">%s</span>' ), $method->get_label() );
		$has_cost  = 0 < $method->cost;
		$hide_cost = ! $has_cost && in_array( $method->get_method_id(), array( 'free_shipping', 'local_pickup' ), true );

		// Maybe add shipping method description
		$method_description = apply_filters( 'fc_shipping_method_option_description', '', $method );
		$method_description_markup = ! empty( $method_description ) ? apply_filters( 'fc_shipping_method_option_description_markup', ' <span class="shipping-method__option-description">%s</span>' ) : '';
		$label .= sprintf( $method_description_markup, $method_description );

		if ( $has_cost && ! $hide_cost ) {

			if ( WC()->cart->display_prices_including_tax() ) {

				$method_costs = wc_price( $method->cost + $method->get_shipping_tax() );
				if ( $method->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
					$method_costs .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}

				$label .= sprintf( apply_filters( 'fc_shipping_method_option_price_markup', ' <span class="shipping-method__option-price">%s</span>' ), $method_costs );

			} else {

				$method_costs = wc_price( $method->cost + $method->get_shipping_tax() );
				if ( $method->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
					$method_costs .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}

				$label .= sprintf( apply_filters( 'fc_shipping_method_option_price_markup', ' <span class="shipping-method__option-price">%s</span>' ), $method_costs );

			}
		}

		return $label;
	}



	/**
	 * Output step: Additional Information fields.
	 */
	public function output_additional_fields() {
		wc_get_template(
			'fc/checkout/form-additional-fields.php',
			array(
				'checkout' => WC()->checkout(),
			)
		);
	}




	/**
	 * Change WooCommerce option `woocommerce_ship_to_destination` to always return ship to `shipping` address.
	 *
	 * @param mixed  $value  Value of the option. If stored serialized, it will be
	 *                       unserialized prior to being returned.
	 * @param string $option Option name.
	 */
	public function change_woocommerce_ship_to_destination( $value, $option ) {
		return 'shipping';
	}





	/**
	 * Checkout step: Billing.
	 */


	/**
	 * Output billing step.
	 */
	public function output_step_billing() {
		do_action( 'fc_output_step_billing', 'billing' );
	}



	/**
	 * Output billing address substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_billing_address( $step_id ) {
		$substep_id = 'billing_address';
		$substep_title = __( 'Billing to', 'fluid-checkout' );
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title, apply_filters( "fc_substep_{$substep_id}_attributes", array() ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_billing_address_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_billing_address();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id, $substep_title, true );
	}



	/**
	 * Get list of billing fields ignored at the billing address substep as they were moved to another substep.
	 *
	 * @return  array  List of billing fields to ignore at billing address substep.
	 */
	public function get_billing_address_ignored_billing_field_ids() {
		$billing_ignored_field_ids = $this->get_contact_step_display_field_ids();
		return $billing_ignored_field_ids;
	}



	/**
	 * Output billing address fields, except those already added at the contact step.
	 */
	public function output_substep_billing_address_fields() {

		// Get checkout object and billing fields, with ignored billing fields removed
		$checkout = WC()->checkout();
		$billing_fields = $checkout->get_checkout_fields( 'billing' );
		$billing_fields = array_filter( $billing_fields, function( $key ) {
			return ! in_array( $key, $this->get_billing_address_ignored_billing_field_ids() );
		}, ARRAY_FILTER_USE_KEY );

		// Get list of billling fields that might be copied from shipping fields
		$billing_same_as_shipping_fields = array_filter( $billing_fields, function( $key ) {
			return in_array( $key, $this->get_billing_same_shipping_fields_keys() );
		}, ARRAY_FILTER_USE_KEY );

		// Get list of billing only fields
		$billing_only_fields = array_filter( $billing_fields, function( $key ) {
			return in_array( $key, $this->get_billing_only_fields_keys() );
		}, ARRAY_FILTER_USE_KEY );

		do_action( 'fc_checkout_before_step_billing_fields' );

		wc_get_template(
			'checkout/form-billing.php',
			array(
				'checkout'                         => $checkout,
				'billing_same_as_shipping_fields'  => $billing_same_as_shipping_fields,
				'billing_only_fields'              => $billing_only_fields,
				'is_billing_same_as_shipping'      => $this->is_billing_same_as_shipping(),
			)
		);

		do_action( 'fc_checkout_after_step_billing_fields' );
	}

	/**
	 * Get billing address fields, except those already added at the contact step.
	 */
	public function get_substep_billing_address_fields() {
		ob_start();
		$this->output_substep_billing_address_fields();
		return ob_get_clean();
	}

	/**
	 * Add billing address fields section as a checkout fragment.
	 */
	function add_checkout_billing_address_fields_fragment( $fragments ) {
		$html = $this->get_substep_billing_address_fields();
		$fragments['.woocommerce-billing-fields'] = $html;
		return $fragments;
	}



	/**
	 * Get billing address substep in text format for when the step is completed.
	 */
	public function get_substep_text_billing_address() {
		$customer = WC()->customer;

		$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--billing-address">';

		if ( $this->is_billing_same_as_shipping_checked() ) {
			$html .= '<div class="fc-step__substep-text-line"><em>' . __( 'Same as shipping address', 'fluid-checkout' ) . '</em></div>';
		}
		else {
			$address_data = array(
				'address_1' => $customer->get_billing_address_1(),
				'address_2' => $customer->get_billing_address_2(),
				'city' => $customer->get_billing_city(),
				'state' => $customer->get_billing_state(),
				'country' => $customer->get_billing_country(),
				'postcode' => $customer->get_billing_postcode(),
			);

			$html .= '<div class="fc-step__substep-text-line">' . esc_html( $customer->get_billing_first_name() ) . ' ' . esc_html( $customer->get_billing_last_name() ) . '</div>';
			$html .= '<div class="fc-step__substep-text-line">' . esc_html( $customer->get_billing_company() ) . '</div>';
			$html .= '<div class="fc-step__substep-text-line">' . WC()->countries->get_formatted_address( $address_data ) . '</div>'; // WPCS: XSS ok.
			$html .= '<div class="fc-step__substep-text-line">' . esc_html( $customer->get_billing_phone() ) . '</div>';
		}

		$html .= '</div>';

		return apply_filters( 'fc_substep_billing_address_text', $html );
	}

	/**
	 * Add billing address text format as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_billing_address_text_fragment( $fragments ) {
		$html = $this->get_substep_text_billing_address();
		$fragments['.fc-step__substep-text-content--billing-address'] = $html;
		return $fragments;
	}

	/**
	 * Output billing address substep in text format for when the step is completed.
	 */
	public function output_substep_text_billing_address() {
		echo $this->get_substep_text_billing_address();
	}



	/**
	 * Determines if all required data for the billing step has been provided.
	 *
	 * @return  boolean  `true` if the user has provided all the required data for this step, `false` otherwise. Defaults to `false`.
	 */
	public function is_step_complete_billing() {
		$checkout = WC()->checkout();
		$is_step_complete = true;

		// Get billing fields moved to contact step
		$contact_display_field_keys = $this->get_contact_step_display_field_ids();

		// Check required data for billing company
		$fields = $checkout->get_checkout_fields( 'billing' );
		foreach ( $fields as $field_key => $field ) {
			// Skip billing fields moved to contact step
			if ( in_array( $field_key, $contact_display_field_keys ) ) { continue; }

			if ( array_key_exists( 'required', $field ) && $field[ 'required' ] === true && ! $checkout->get_value( $field_key ) ) {
				$is_step_complete = false;
				break;
			}
		}

		return apply_filters( 'fc_is_step_complete_billing', $is_step_complete );
	}



	/**
	 * Output field for billing address same as shipping.
	 */
	public function output_billing_same_as_shipping_field() {
		// Output a hidden field when shipping country not allowed for billing, or shipping not needed
		if ( ! WC()->cart->needs_shipping_address() || $this->is_shipping_country_allowed_for_billing() === null || ! $this->is_shipping_country_allowed_for_billing() ) : ?>
			<input type="hidden" name="billing_same_as_shipping" id="billing_same_as_shipping" value="<?php echo $this->is_billing_same_as_shipping_checked() ? '1' : '0'; // WPCS: XSS ok. ?>">
		<?php
		// Output the checkbox when shipping country is allowed for billing
		else :
		?>
			<p id="billing_same_as_shipping_field" class="form-row form-row-wide">
				<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="billing_same_as_shipping" id="billing_same_as_shipping" value="1" <?php checked( $this->is_billing_same_as_shipping(), true ); ?>>
				<label for="billing_same_as_shipping"><?php echo esc_html( __( 'Same as shipping address', 'fluid-checkout' ) ); ?></label>
			</p>
		<?php
		endif;
	}



	/**
	 * Check whether the selected shipping country is also available for billing country.
	 *
	 * @return  mixed  `true` if the selected shipping country is also available for billing country, `false` if the shipping country is not allowed for billing, and `null` if the shipping country is not set.
	 */
	public function is_shipping_country_allowed_for_billing() {
		// Get shipping value from customer data
		$customer = WC()->customer;
		$shipping_country = $customer->get_shipping_country();

		// Use posted data when doing checkout update
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Try get value from the post_data
			if ( isset( $_POST['s_country'] ) ) {
				$shipping_country = isset( $_POST['s_country'] ) ? wc_clean( wp_unslash( $_POST['s_country'] ) ) : null;
			}
			// Try get value from the form data sent on process checkout
			else if ( isset( $_POST['shipping_country'] ) ) {
				$shipping_country = isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : null;
			}
		}

		// Shipping country is defined, return bool
		if ( $shipping_country != null && ! empty( $shipping_country ) ) {
			$allowed_billing_countries = WC()->countries->get_allowed_countries();
			return in_array( $shipping_country, array_keys( $allowed_billing_countries ) );
		}

		return null;
	}



	/**
	 * Check whether the checkbox "billing address same as shipping" is checked.
	 * This function will return `true` even if the shipping country is not allowed for billing,
	 * use `is_billing_same_as_shipping` to also check if the shipping country is allowed for billing.
	 *
	 * @return  bool  `true` checkbox "billing address same as shipping" is checked, `false` otherwise.
	 */
	public function is_billing_same_as_shipping_checked() {
		// Get parsed posted data
		$posted_data = $this->get_parsed_posted_data();

		// Set default value
		$billing_same_as_shipping = apply_filters( 'fc_default_to_billing_same_as_shipping', get_option( 'fc_default_to_billing_same_as_shipping', 'yes' ) == 'yes' );

		// Try get value from the post_data
		if ( isset( $_POST['post_data'] ) ) {
			$billing_same_as_shipping = isset( $posted_data['billing_same_as_shipping'] ) && $posted_data['billing_same_as_shipping'] === '1' ? true : false;
		}
		// Try get value from the form data sent on process checkout
		else if ( isset( $_POST['billing_same_as_shipping'] ) ) {
			$billing_same_as_shipping = isset( $_POST['billing_same_as_shipping'] ) && wc_clean( wp_unslash( $_POST['billing_same_as_shipping'] ) ) === '1' ? true : false;
		}
		// Try to get value from the session
		else if ( WC()->session->__isset( 'fc_billing_same_as_shipping' ) ) {
			$billing_same_as_shipping = WC()->session->get( 'fc_billing_same_as_shipping' ) === '1';
		}

		// Set to different billing address when order does not need shipping
		if ( ! WC()->cart->needs_shipping() ) {
			$billing_same_as_shipping = false;
		}

		return $billing_same_as_shipping;
	}



	/**
	 * Get value for whether the billing address is the same as the shipping address.
	 *
	 * @return  bool  `true` if the billing address is the same as the shipping address, `false` otherwise.
	 */
	public function is_billing_same_as_shipping() {
		// Set to different billing address when shipping country not allowed
		if ( $this->is_shipping_country_allowed_for_billing() !== null && ! $this->is_shipping_country_allowed_for_billing() ) {
			return false;
		}

		// Set to different billing address when shipping address not needed
		if ( ! WC()->cart->needs_shipping_address() ) {
			return false;
		}

		return $this->is_billing_same_as_shipping_checked();
	}

	/**
	 * Save value of `billing_same_as_shipping` to the current user session.
	 */
	public function set_billing_same_as_shipping_session( $billing_same_as_shipping ) {
		// Set session value
		WC()->session->set( 'fc_billing_same_as_shipping', $billing_same_as_shipping ? '1' : '0');
	}



	/**
	 * Get list of checkout field keys which values are to be copied from shipping fields.
	 *
	 * @return  array  List of checkout field keys.
	 */
	public function get_billing_same_shipping_fields_keys() {
		// Intialize list of supported field keys
		$billing_copy_shipping_field_keys = array();

		// Get checkout object and fields
		$checkout = WC()->checkout();
		$billing_fields = $checkout->get_checkout_fields( 'billing' );
		$shipping_fields = $checkout->get_checkout_fields( 'shipping' );

		// Get list of billing fields to skip copying from shipping fields
		$skip_field_keys = apply_filters( 'fc_billing_same_as_shipping_skip_fields', array() );

		// Use the `WC_Customer` object for supported properties
		foreach ( $shipping_fields as $field_key => $field_args ) {

			// Get billing field key
			$billing_field_key = str_replace( 'shipping_', 'billing_', $field_key );

			// Maybe add field key to the list of fields to copy
			if ( ! in_array( $billing_field_key, $skip_field_keys ) ) {
				$billing_copy_shipping_field_keys[] = $billing_field_key;
			}

		}

		// Filter list leaving only billing fields that actually exist
		$billing_copy_shipping_field_keys = array_intersect( array_keys( $billing_fields ), $billing_copy_shipping_field_keys );

		// Remove ignored billing fields
		$billing_copy_shipping_field_keys = array_diff( $billing_copy_shipping_field_keys, $this->get_billing_address_ignored_billing_field_ids() );

		return apply_filters( 'fc_billing_same_as_shipping_field_keys', $billing_copy_shipping_field_keys );
	}

	/**
	 * Get list of billing only fields, that is, fields that are not present on both shipping and billing fields,
	 * which would be copied when "Billing same as shipping" is cheched. Also returns the fields which are to be
	 * ignored when copying values from the shipping to billing.
	 *
	 * @return  array  List of checkout field keys.
	 */
	public function get_billing_only_fields_keys() {
		// Get checkout object and fields
		$checkout = WC()->checkout();
		$billing_fields = $checkout->get_checkout_fields( 'billing' );

		// Get list of billing fields to copy from shipping fields
		$billing_copy_shipping_field_keys = $this->get_billing_same_shipping_fields_keys();

		// Get list of billing only fields
		$billing_only_field_keys = array_diff( array_keys( $billing_fields ), $billing_copy_shipping_field_keys );

		// Remove ignored billing fields
		$billing_only_field_keys = array_diff( $billing_only_field_keys, $this->get_billing_address_ignored_billing_field_ids() );

		return $billing_only_field_keys;
	}

	/**
	 * Maybe set billing address fields values to same as shipping address from the posted data.
	 *
	 * @param array $posted_data Post data for all checkout fields.
	 */
	public function maybe_set_billing_address_same_as_shipping( $posted_data ) {
		// Get value for billing same as shipping
		$is_billing_same_as_shipping = $this->is_billing_same_as_shipping();
		$is_billing_same_as_shipping_checked = $this->is_billing_same_as_shipping_checked();

		// Save checked state of the billing same as shipping field to the session,
		// for the case the shipping country changes again and the new value is also accepted for billing.
		$this->set_billing_same_as_shipping_session( $is_billing_same_as_shipping_checked );

		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();

		// Maybe set post data for billing same as shipping
		if ( $is_billing_same_as_shipping ) {

			// Get list of billing fields to copy from shipping fields
			$billing_copy_shipping_field_keys = $this->get_billing_same_shipping_fields_keys();

			// Get list of posted data keys
			$posted_data_field_keys = array_keys( $parsed_posted_data );

			// Iterate posted data
			foreach( $billing_copy_shipping_field_keys as $field_key ) {

				// Get shipping field key
				$shipping_field_key = str_replace( 'billing_', 'shipping_', $field_key );

				// Update billing field values
				if ( in_array( $shipping_field_key, $posted_data_field_keys ) ) {
					$parsed_posted_data[ $field_key ] = isset( $parsed_posted_data[ $shipping_field_key ] ) ? $parsed_posted_data[ $shipping_field_key ] : null;
					$_POST[ $field_key ] = isset( $parsed_posted_data[ $shipping_field_key ] ) ? $parsed_posted_data[ $shipping_field_key ] : null;
				}

			}

		}

		return $posted_data;
	}

	/**
	 * Set addresses session values when processing an order (place order).
	 *
	 * @param array $post_data Post data for all checkout fields.
	 */
	public function maybe_set_billing_address_same_as_shipping_on_process_checkout( $post_data ) {
		// Maybe set posted data for billing address to same as shipping
		if ( $this->is_billing_same_as_shipping() ) {
			// Iterate posted data
			foreach( $post_data as $field_key => $field_value ) {

				// Only process shipping fields
				if ( strpos( $field_key, 'shipping_' ) === 0 ) {

					// Get billing field key
					$billing_field_key = str_replace( 'shipping_', 'billing_', $field_key );

					// Update billing field values
					$skip_field_keys = apply_filters( 'fc_billing_same_as_shipping_skip_fields', array() );
					if ( ! in_array( $billing_field_key, $skip_field_keys ) ) {
						$post_data[ $billing_field_key ] = isset( $post_data[ $field_key ] ) ? $post_data[ $field_key ] : null;
					}
				}

			}
		}

		return $post_data;
	}





	/**
	 * Checkout Step: Payment.
	 */



	/**
	 * Output payment step.
	 */
	public function output_step_payment() {
		do_action( 'fc_output_step_payment', 'payment' );
	}



	/**
	 * Output payment substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_payment( $step_id ) {
		$substep_id = 'payment';
		$substep_title = __( 'Payment method', 'fluid-checkout' );
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title, apply_filters( "fc_substep_{$substep_id}_attributes", array() ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_payment_fields();
		$this->output_substep_fields_end_tag();

		$this->output_substep_end_tag( $step_id, $substep_id, $substep_title, false );
	}



	/**
	 * Output payment fields.
	 */
	public function output_substep_payment_fields() {
		wc_get_template(
			'fc/checkout/form-payment.php',
			array(
				'checkout'          => WC()->checkout(),
			)
		);
	}



	/**
	 * Remove links and fix accessibility attributes for payment method icons.
	 */
	public function change_payment_gateway_icon_html( $icon, $id = null ) {

		// Remove links from the icon html
		$pattern = '/(<a [^<]*)([^<]*)(<\/a>)/';
		$icon = preg_replace( $pattern, '$2', $icon );

		// Fix accessibility attributes
		$pattern = '/( alt="[^<]*")/';
		$icon = preg_replace( $pattern, 'alt="" aria-hidden="true" role="presentation"', $icon );

		return $icon;
	}



	/**
	 * END - Checkout Steps.
	 */





	/**
	 * Order Review.
	 */



	/**
	 * Output sidebar section wrapper.
	 */
	public function output_checkout_sidebar_wrapper() {
		$sidebar_attributes = array(
			'class' => 'fc-sidebar',
		);
		$sidebar_attributes_inner = array(
			'class' => 'fc-sidebar__inner',
		);

		// Sticky state attributes
		if ( get_option( 'fc_enable_checkout_sticky_order_summary', 'yes' ) === 'yes' ) {
			$sidebar_attributes = array_merge( $sidebar_attributes, array(
				'data-sticky-states' => true,
				'data-sticky-container' => 'div.woocommerce',
			) );
			$sidebar_attributes_inner = array_merge( $sidebar_attributes_inner, array(
				'data-sticky-states-inner' => true,
			) );
		}

		// Filter attributes
		$sidebar_attributes = apply_filters( 'fc_checkout_sidebar_attributes', $sidebar_attributes );
		$sidebar_attributes_inner = apply_filters( 'fc_checkout_sidebar_attributes_inner', $sidebar_attributes_inner );

		// Convert attributes to string
		$sidebar_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $sidebar_attributes ), $sidebar_attributes ) );
		$sidebar_attributes_inner_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $sidebar_attributes_inner ), $sidebar_attributes_inner ) );
		?>
		<div <?php echo $sidebar_attributes_str; // WPCS: XSS ok. ?>>
			<div <?php echo $sidebar_attributes_inner_str; // WPCS: XSS ok. ?>>
				<?php do_action( 'fc_checkout_order_review_section' ); ?>
			</div>
		</div>
		<?php
	}



	/**
	 * Get the order review section title.
	 *
	 * @return  string  The order review section title.
	 */
	public function get_order_review_title() {
		return apply_filters( 'fc_order_review_title', __( 'Order summary', 'fluid-checkout' ) );
	}

	/**
	 * Get attributes for the order review section element.
	 *
	 * @param   bool  $is_sidebar_widget  Whether or not outputting the sidebar.
	 *
	 * @return  array                    Array of key/value html attributes.
	 */
	public function get_order_review_html_attributes( $is_sidebar_widget = false ) {
		$attributes = array(
			'class' => 'fc-checkout-order-review',
		);

		// Sidebar widget
		if ( $is_sidebar_widget ) {
			$attributes = array_merge( $attributes, array(
				'id' => 'fc-checkout-order-review',
				'data-flyout' => true,
				'data-flyout-order-review' => true,
				'data-flyout-open-animation-class' => 'fade-in-down',
				'data-flyout-close-animation-class' => 'fade-out-up',
			) );
		}

		// Maybe add class for additional content inside the order summary section
		if ( get_option( 'fc_enable_checkout_place_order_sidebar', 'no' ) === 'yes' || is_active_sidebar( 'fc_order_summary_after' ) ) {
			$attributes[ 'class' ] = $attributes[ 'class' ] . ' has-additional-content';
		}

		return $attributes;
	}

	/**
	 * Get attributes for the order review section inner element.
	 *
	 * @param   bool  $is_sidebar_widget  Whether or not outputting the sidebar.
	 *
	 * @return  array                    Array of key/value html attributes.
	 */
	public function get_order_review_html_attributes_inner( $is_sidebar_widget = false ) {
		$attributes = array(
			'class' => 'fc-checkout-order-review__inner',
		);

		// Sidebar widget
		if ( $is_sidebar_widget ) {
			$attributes = array_merge( $attributes, array(
				'data-flyout-content' => true,
			) );
		}

		return $attributes;
	}

	/**
	 * Output Order Review.
	 */
	public function output_order_review() {
		wc_get_template(
			'fc/checkout/review-order-section.php',
			array(
				'checkout'           => WC()->checkout(),
				'order_review_title' => $this->get_order_review_title(),
				'is_sidebar_widget'  => false,
				'attributes'         => $this->get_order_review_html_attributes(),
				'attributes_inner'   => $this->get_order_review_html_attributes_inner(),
			)
		);
	}

	/**
	 * Output Order Review for sidebar.
	 */
	public function output_order_review_for_sidebar() {
		wc_get_template(
			'fc/checkout/review-order-section.php',
			array(
				'checkout'           => WC()->checkout(),
				'order_review_title' => $this->get_order_review_title(),
				'is_sidebar_widget'  => true,
				'attributes'         => $this->get_order_review_html_attributes( true ),
				'attributes_inner'   => $this->get_order_review_html_attributes_inner( true ),
			)
		);
	}



	/**
	 * Output checkout place order section.
	 */
	public function get_checkout_place_order_html( $step_id = 'payment', $is_sidebar = false ) {
		ob_start();
		wc_get_template(
			'fc/checkout/place-order.php',
			array(
				'checkout'           => WC()->checkout(),
				'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) ),
			)
		);
		$place_order_html = ob_get_clean();

		// Add terms checkbox custom class
		$place_order_html = str_replace( 'input-checkbox" name="terms"', 'input-checkbox fc-terms-checkbox" name="terms"', $place_order_html );

		// Make sure there are no duplicate fields for outputting place order on the sidebar
		if ( $is_sidebar ) {
			$place_order_html = str_replace( 'id="terms"', '', $place_order_html );
			$place_order_html = str_replace( 'id="place_order"', '', $place_order_html );
			$place_order_html = str_replace( 'id="woocommerce-process-checkout-nonce"', '', $place_order_html );
			$place_order_html = str_replace( 'name="terms"', '', $place_order_html );
			$place_order_html = str_replace( 'name="terms-field"', '', $place_order_html );
			$place_order_html = str_replace( 'name="woocommerce-process-checkout-nonce"', '', $place_order_html );
			$place_order_html = str_replace( 'name="_wp_http_referer"', '', $place_order_html );
		}

		return $place_order_html; // WPCS: XSS ok.
	}

	/**
	 * Output checkout place order section.
	 */
	public function output_checkout_place_order( $step_id = 'payment', $is_sidebar = false ) {
		echo $this->get_checkout_place_order_html( $step_id, $is_sidebar );
	}

	/**
	 * Output checkout place order section.
	 *
	 * @param   bool  $is_sidebar_widget  Whether or not outputting the sidebar.
	 */
	public function output_checkout_place_order_for_sidebar( $is_sidebar_widget ) {
		// Bail if not ouputting for the sidebar
		if ( ! $is_sidebar_widget ) { return; }

		// Bail if additional place order section is not enabled
		if ( get_option( 'fc_enable_checkout_place_order_sidebar', 'no' ) === 'no' ) { return; }

		$this->output_checkout_place_order( '__sidebar', true );
	}

	/**
	 * Add place order section as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_place_order_fragment( $fragments ) {
		$html = $this->get_checkout_place_order_html();
		$fragments['.place-order'] = $html;
		return $fragments;
	}



	/**
	 * Add wrapper element and custom class for the checkout place order button.
	 */
	public function add_place_order_button_wrapper( $button_html ) {
		$button_html = str_replace( 'class="button alt', 'class="' . esc_attr( apply_filters( 'fc_place_order_button_classes', 'button alt' ) ) . ' fc-place-order-button', $button_html );
		return '<div class="fc-place-order">' . $button_html . '</div>';
	}



	/**
	 * Maybe output the shipping methods chosen for order review section.
	 */
	public function maybe_output_order_review_shipping_method_chosen() {
		// Bail if not on checkout or cart page
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() ) ) { return; }

		$packages = WC()->shipping()->get_packages();
		$first    = true;

		foreach ( $packages as $i => $package ) {
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$product_names = array();

			if ( count( $packages ) > 1 ) {
				foreach ( $package['contents'] as $item_id => $values ) {
					$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
				}
				$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
			}

			wc_get_template(
				'fc/checkout/review-order-shipping.php',
				array(
					'package'                  => $package,
					'available_methods'        => $package['rates'],
					'show_package_details'     => count( $packages ) > 1,
					'show_shipping_calculator' => is_cart() && apply_filters( 'woocommerce_shipping_show_shipping_calculator', $first, $i, $package ),
					'package_details'          => implode( ', ', $product_names ),
					'package_name'             => apply_filters( 'woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'woocommerce' ), ( $i + 1 ) ) : _x( 'Shipping', 'shipping packages', 'woocommerce' ), $i, $package ),
					'index'                    => $i,
					'chosen_method'            => $chosen_method,
					'formatted_destination'    => WC()->countries->get_formatted_address( $package['destination'], ', ' ),
					'has_calculated_shipping'  => WC()->customer->has_calculated_shipping(),
				)
			);

			$first = false;
		}
	}


	/**
	 * Get shipping method label with only the cost, removing the label of the shipping method chosen.
	 *
	 * @param  WC_Shipping_Rate $method Shipping method rate data.
	 *
	 * @return  string                  Shipping method label with only the cost.
	 */
	public function get_cart_totals_shipping_method_label( $method ) {
		$method_label = $method->get_label();

		// Remove the shipping method label, leaving only the cost
		$shipping_total_label = str_replace( $method_label.': ', '', wc_cart_totals_shipping_method_label( $method ) );

		return $shipping_total_label;
	}



	/**
	 * END - Order Review.
	 */





	/**
	 * Persisted Data
	 */



	/**
	 * Get list of checkout field keys that are supported by `WC_Customer` object.
	 *
	 * @return  array  List of checkout field keys.
	 */
	public function get_supported_customer_property_field_keys() {
		// Intialize list of supported field keys
		$customer_supported_field_keys = array();

		// Get customer object
		$customer = WC()->customer;

		// Get checkout object and fields
		$checkout = WC()->checkout();
		$fields = $checkout->get_checkout_fields();

		// Use the `WC_Customer` object for supported properties
		foreach ( $fields as $fieldset_key => $fieldset ) {

			// Iterate checkout fieldset groups (ie. billing, shipping, account)
			foreach ( $fieldset as $field_key => $field ) {

				// Get the setter method name for the customer property
				$setter = "set_$field_key";

				// Check if the setter method is supported
				if ( is_callable( array( $customer, $setter ) ) ) {
					// Add field key to the list of already saved values
					$customer_supported_field_keys[] = $field_key;
				}

			}

		}

		return $customer_supported_field_keys;
	}

	/**
	 * Get list of checkout field keys that are not supported by `WC_Customer` object, and therefore needs to be saved to the session.
	 *
	 * @return  array  List of checkout field keys.
	 */
	public function get_customer_session_field_keys() {
		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();

		// Get list of field keys posted
		$session_field_keys = array_keys( $parsed_posted_data );

		$skip_field_keys = array(
			'ship_to_different_address',
			'payment_method',
			'terms-field',
			'_wp_http_referer',
		);

		// Add some fields to the skip list
		foreach ( $session_field_keys as $field_key ) {
			if (
				( strpos( $field_key, 'shipping_method[' ) !== false && strpos( $field_key, 'shipping_method[' ) === 0 ) // Target field keys such as `shipping_method[0]` and `shipping_method[1]`
				|| ( strpos( $field_key, '-nonce' ) !== false && strpos( $field_key, '-nonce' ) >= 0 ) // Target field keys such as `woocommerce-process-checkout-nonce`
			) {
				$skip_field_keys[] = $field_key;
			}
		}

		// Filter skip fields to allow developers to add more fields to the skip list
		$skip_field_keys = apply_filters( 'fc_customer_persisted_data_skip_fields', $skip_field_keys, $parsed_posted_data );

		// Remove fields that should be skipped
		$session_field_keys = array_diff( $session_field_keys, $skip_field_keys );

		return $session_field_keys;
	}



	/**
	 * Update the customer's data to the WC_Customer object.
	 *
	 * @param string $posted_data Post data for all checkout fields.
	 */
	public function update_customer_persisted_data( $posted_data ) {
		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();

		// Get customer object and supported field keys
		$customer = WC()->customer;
		$customer_supported_field_keys = $this->get_supported_customer_property_field_keys();

		// Use the `WC_Customer` object for supported properties
		foreach ( $customer_supported_field_keys as $field_key ) {
			// Get the setter method name for the customer property
			$setter = "set_$field_key";

			// Check if the setter method is supported
			if ( is_callable( array( $customer, $setter ) ) ) {
				// Set property value to the customer object
				if ( array_key_exists( $field_key, $parsed_posted_data ) ) {
					$customer->{$setter}( $parsed_posted_data[ $field_key ] );
				}
			}
		}

		// Save/commit changes to the customer object
		WC()->customer->save();

		// Get list of fields to save to the session
		$session_field_keys = $this->get_customer_session_field_keys();

		// Save customer data to the session
		foreach ( $session_field_keys as $field_key ) {

			// Set property value to the customer object
			if ( array_key_exists( $field_key, $parsed_posted_data ) ) {
				// Set session value
				WC()->session->set( self::SESSION_PREFIX . $field_key, $parsed_posted_data[ $field_key ] );
			}
			else {
				// Set session value as empty
				WC()->session->set( self::SESSION_PREFIX . $field_key, null );
			}

		}
	}



	/**
	 * Change default checkout field value, getting it from the persisted fields session.
	 *
	 * @param   mixed    $value   Value of the field.
	 * @param   string   $input   Checkout field key (ie. order_comments ).
	 */
	public function change_default_checkout_field_value_from_session_or_posted_data( $value, $input ) {
		// Maybe return field value from posted data
		$posted_data = $this->get_parsed_posted_data();
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && array_key_exists( $input, $posted_data ) ) {
			$field_posted_data_value = $posted_data[ $input ];
			return $field_posted_data_value;
		}
		
		// Maybe return field value from session
		$field_session_value = $this->get_checkout_field_value_from_session( $input );
		if ( $field_session_value !== null ) {
			return $field_session_value;
		}

		return $value;
	}

	/**
	 * Get values for a checkout field from the session.
	 *
	 * @param   string  $field_key  Checkout field key name (ie. order_comments ).
	 * @return  mixed               The value of the field from the saved session.
	 */
	public function get_checkout_field_value_from_session( $field_key ) {
		// Bail if WC or session not available yet
		if ( ! function_exists( 'wC' ) || ! isset( WC()->session ) ) { return; }

		return WC()->session->get( self::SESSION_PREFIX . $field_key );
	}

	/**
	 * Clear session values for checkout fields when the order is processed.
	 **/
	public function unset_session_customer_persisted_data_order_processed() {
		$clear_field_keys = array(
			'order_comments',
		);

		// Filter clear fields to allow developers to add more fields to be cleared
		$clear_field_keys = apply_filters( 'fc_customer_persisted_data_clear_fields_order_processed', $clear_field_keys );

		// Clear customer data from the session
		foreach ( $clear_field_keys as $field_key ) {
			WC()->session->__unset( self::SESSION_PREFIX . $field_key );
		}
	}

	/**
	 * Clear session values for all checkout fields.
	 **/
	public function unset_all_session_customer_persisted_data() {
		// Bail if session not available
		if ( ! function_exists( 'WC' ) || ! isset( WC()->session ) ) { return; }

		// Filter clear fields to allow developers to add more fields to skip being cleared
		$clear_field_keys_skip_list = apply_filters( 'fc_customer_persisted_data_clear_all_fields_skip_list', array( 'order_comments' ) );

		// Get field keys from the session
		$all_session_data = WC()->session->get_session_data();
		$clear_field_keys = array();
		foreach ( array_keys( $all_session_data ) as $session_field_key ) {
			if ( 0 === strpos( $session_field_key, self::SESSION_PREFIX ) ) {
				$clear_field_keys[] = substr_replace( $session_field_key, '', strpos( $session_field_key, self::SESSION_PREFIX ), strlen( self::SESSION_PREFIX ) );
			}
		}

		// Clear customer data from the session
		foreach ( $clear_field_keys as $field_key ) {
			// Skip clearing some fields
			if ( in_array( $field_key, $clear_field_keys_skip_list ) ) { continue; }
			
			WC()->session->__unset( self::SESSION_PREFIX . $field_key );
		}
	}



	/**
	 * END - Persisted Data
	 */

}

FluidCheckout_Steps::instance();
