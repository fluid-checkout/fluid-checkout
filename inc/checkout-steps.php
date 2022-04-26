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
	 * Hold cached values for parsed `post_data`.
	 *
	 * @var array
	 */
	private $posted_data = null;
	private $set_parsed_posted_data_filter_applied = false;



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

		// Customer details hooks
		add_action( 'fc_checkout_after_step_billing_fields', array( $this, 'run_action_woocommerce_checkout_after_customer_details' ), 90 );

		// Contact
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
		add_filter( 'woocommerce_registration_error_email_exists', array( $this, 'change_message_registration_error_email_exists' ), 10 );
		add_action( 'fc_output_step_contact', array( $this, 'output_substep_contact_login' ), 10 );
		add_action( 'fc_output_step_contact', array( $this, 'output_substep_contact' ), 20 );
		add_action( 'wp_footer', array( $this, 'output_login_form_flyout' ), 10 );
		add_action( 'woocommerce_login_form_end', array( $this, 'output_woocommerce_login_form_redirect_hidden_field'), 10 );
		add_filter( 'fc_substep_contact_text_lines', array( $this, 'add_substep_text_lines_contact' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_contact_text_fragment' ), 10 );

		// Account creation
		add_action( 'fc_checkout_after_contact_fields', array( $this, 'output_form_account_creation' ), 10 );

		// Shipping
		add_filter( 'option_woocommerce_ship_to_destination', array( $this, 'change_woocommerce_ship_to_destination' ), 100, 2 );
		add_action( 'fc_output_step_shipping', array( $this, 'output_substep_shipping_address' ), $this->get_shipping_address_hook_priority() );
		add_action( 'fc_output_step_shipping', array( $this, 'output_substep_shipping_method' ), $this->get_shipping_methods_hook_priority() );
		add_action( 'fc_before_checkout_shipping_address_wrapper', array( $this, 'output_ship_to_different_address_hidden_field' ), 10 );
		add_filter( 'fc_substep_shipping_address_text_lines', array( $this, 'add_substep_text_lines_shipping_address' ), 10 );
		add_filter( 'fc_substep_shipping_address_text_lines', array( $this, 'add_substep_text_lines_extra_fields_shipping_address' ), 20 );
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );
		add_filter( 'fc_substep_order_notes_text_lines', array( $this, 'add_substep_text_lines_order_notes' ), 10 );
		add_filter( 'woocommerce_ship_to_different_address_checked', array( $this, 'set_ship_to_different_address_true' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_address_fields_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_address_text_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_methods_fields_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_methods_text_fragment' ), 10 );

		// Billing Address
		add_action( 'fc_output_step_billing', array( $this, 'output_substep_billing_address' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_billing_address_fields_fragment' ), 10 );
		add_filter( 'fc_substep_billing_address_text_lines', array( $this, 'add_substep_text_lines_billing_address' ), 10 );
		add_filter( 'fc_substep_billing_address_text_lines', array( $this, 'add_substep_text_lines_extra_fields_billing_address' ), 20 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_billing_address_text_fragment' ), 10 );

		// Billing Same as Shipping
		add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'output_billing_same_as_shipping_field' ), 100 );
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_billing_address_same_as_shipping' ), 10 );
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

		// Formatted Address
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'add_phone_localisation_address_formats' ) );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_phone_formatted_address_replacements' ), 10, 2 );

		// Order Review
		add_action( 'fc_checkout_order_review_section', array( $this, 'output_order_review_for_sidebar' ), 10 );
		add_action( 'fc_review_order_shipping', array( $this, 'maybe_output_order_review_shipping_method_chosen' ), 30 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'update_customer_persisted_data' ), 100 );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'change_default_checkout_field_value_from_session_or_posted_data' ), 100, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'unset_session_customer_persisted_data_order_processed' ), 100 );
		add_action( 'wp_login', array( $this, 'unset_all_session_customer_persisted_data' ), 100 );
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
			$order_notes_substep_position = apply_filters( 'fc_do_order_notes_hooks_position', 'fc_checkout_after_step_shipping_fields_inside' );
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
		if ( ! WC()->checkout()->is_registration_enabled() && WC()->checkout()->is_registration_required() && ! is_user_logged_in() ) {
			$add_classes[] = 'has-checkout-must-login-notice';
		}

		// Add extra class to highlight the shipping section
		if ( true === apply_filters( 'fc_show_shipping_section_highlighted', true ) ) {
			$add_classes[] = 'has-highlighted-shipping-section';
		}

		// Add extra class to highlight the billing section
		if ( true === apply_filters( 'fc_show_billing_section_highlighted', true ) ) {
			$add_classes[] = 'has-highlighted-billing-section';
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
		if ( is_rtl() ) {
			wp_enqueue_style( 'fc-checkout-layout', self::$directory_url . 'css/checkout-layout-rtl'. self::$asset_version . '.css', NULL, NULL );
		}
		else {
			wp_enqueue_style( 'fc-checkout-layout', self::$directory_url . 'css/checkout-layout'. self::$asset_version . '.css', NULL, NULL );
		}
	
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

		// Check if checkout page is showing the checkout form, then check the settings to show theme header or plugin header
		return ( ! ( ! WC()->checkout()->is_registration_enabled() && WC()->checkout()->is_registration_required() && ! is_user_logged_in() ) ) && 'yes' === get_option( 'fc_hide_site_header_footer_at_checkout', 'yes' );
	}



	/**
	 * Return the list of values accepted for checkout layout.
	 *
	 * @return  array  List of values accepted for checkout layout.
	 */
	public function get_allowed_checkout_layouts() {
		return apply_filters( 'fc_allowed_checkout_layouts', array(
			'multi-step'     => __( 'Multi-step', 'fluid-checkout' ),
			'single-step'    => __( 'Single step', 'fluid-checkout' ),
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
	 * Check whether the shipping phone field is enabled to be used.
	 */
	public function is_shipping_phone_enabled() {
		return 'no' !== get_option( 'fc_shipping_phone_field_visibility', 'no' );
	}

	/**
	 * Check whether the billing phone field is enabled to be used.
	 */
	public function is_billing_phone_enabled() {
		return 'hidden' !== get_option( 'woocommerce_checkout_phone_field', 'required' );
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

		// Get the current step
		$current_step = $this->get_current_step();

		// Bail if current step is not defined
		if ( false === $current_step ) { return $complete_steps; }

		// Remove the current step and steps after that,
		// leaving only the complete steps in the list.
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

		// Defaults to the first step
		if ( is_array( $_checkout_steps ) && count( $_checkout_steps ) > 0 ) {
			return array( 0 => $_checkout_steps[ 0 ] );
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

		// Bail if current step is not defined
		if ( false === $current_step ) { return false; }

		// Get current steps arguments
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
	 * Add phone field replacement to localisation addresses formats.
	 *
	 * @param  array  $formats  Default localisation formats.
	 */
	public function add_phone_localisation_address_formats( $formats ) {
		// Bail if should not display phone in formatted addresses
		if ( 'yes' !== apply_filters( 'fc_add_phone_localisation_formats', 'yes' ) ) { return $formats; }

		// Bail if viewing order confirmation page
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) { return $formats; }

		// Bail when displaying addresses for email messages
		if ( did_action( 'woocommerce_email_customer_details' ) ) { return $formats; }

		foreach ( $formats as $locale => $format) {
			$formats[ $locale ] = $format . "\n{phone}";
		}

		return $formats;
	}

	/**
	 * Add phone field replacement to formatted addresses.
	 *
	 * @param   array  $replacements  Default replacements.
	 * @param   array  $address       Contains address fields.
	 */
	public function add_phone_formatted_address_replacements( $replacements, $args ) {
		// Bail if should not display phone in formatted addresses
		if ( 'yes' !== apply_filters( 'fc_add_phone_localisation_formats', 'yes' ) ) { return $replacements; }

		// Bail if viewing order confirmation page
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) { return $replacements; }

		$replacements['{phone}'] = isset( $args['phone'] ) ? $args['phone'] : '';
		return $replacements;
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

		// Bail if current step is not defined
		if ( false === $current_step ) { return; }

		// Get current steps arguments
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
		$additional_attributes = apply_filters( "fc_substep_{$substep_id}_attributes", $additional_attributes );
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
	 * Get the substep review text.
	 */
	public function get_substep_review_text( $substep_id ) {
		$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--' . $substep_id . '">';

		$review_text_lines = apply_filters( "fc_substep_{$substep_id}_text_lines", array() );

		// Maybe add notice for empty substep text
		if ( ! is_array( $review_text_lines ) || count ( $review_text_lines ) == 0 ) {
			$review_text_lines[] = apply_filters( 'fc_no_substep_review_text_notice', _x( 'None.', 'Substep review text', 'fluid-checkout' ) );
		}

		// Add each review text line to the output html
		foreach( $review_text_lines as $text_line ) {
			$html .= '<div class="fc-step__substep-text-line">' . wp_kses_post( $text_line ) . '</div>';
		}

		$html .= '</div>';

		return apply_filters( "fc_substep_{$substep_id}_text", $html );
	}



	/**
	 * Output checkout expansible form section start tag.
	 *
	 * @param   string  $section_id    ID of the expansible section.
	 * @param   string  $toggle_label  Label for the expansible section toggle link. (optional)
	 * @param   string  $args          Arguments for the expansible section.
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
			
			<?php if ( ! empty( $toggle_label ) ) : ?>
			<div <?php echo $section_toggle_attributes_str; // WPCS: XSS ok. ?>>
				<div <?php echo $section_content_inner_attributes_str; // WPCS: XSS ok. ?>>
					<a <?php echo $toggle_attributes_str; // WPCS: XSS ok. ?>>
						<?php echo esc_html( $toggle_label ); ?>
					</a>
				</div>
			</div>
			<?php endif; ?>

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
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title );

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
				'checkout'             => WC()->checkout(),
				'contact_field_ids'    => $this->get_contact_step_display_field_ids(),
			)
		);
	}



	/**
	 * Add the contact substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_contact( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get fields
		$contact_field_ids = $this->get_contact_step_display_field_ids();
		$checkout_fields = WC()->checkout->get_checkout_fields();
		
		// Add a text line for each field
		foreach( $contact_field_ids as $field_key ) {
			// Iterate checkout fields
			foreach ( $checkout_fields as $field_group => $field_group_fields ) {
				if ( array_key_exists( $field_key, $field_group_fields ) ) {
					$review_text_lines[] = WC()->checkout->get_value( $field_key );
					continue 2;
				}
			}
		}

		// Maybe add notice for account creation
		if ( 'true' === get_option( 'fc_show_account_creation_notice_checkout_contact_step_text', 'true' ) ) {
			$parsed_posted_data = $this->get_parsed_posted_data();
			if ( array_key_exists( 'createaccount', $parsed_posted_data ) && $parsed_posted_data[ 'createaccount' ] == '1' ) {
				$review_text_lines[] = '<em>' . __( 'An account will be created with the information provided.', 'fluid-checkout' ) . '</em>';
			}
		}

		return $review_text_lines;
	}

	/**
	 * Get contact substep review text.
	 */
	public function get_substep_text_contact() {
		return $this->get_substep_review_text( 'contact' );
	}

	/**
	 * Add contact substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_contact_text_fragment( $fragments ) {
		$html = $this->get_substep_text_contact();
		$fragments['.fc-step__substep-text-content--contact'] = $html;
		return $fragments;
	}

	/**
	 * Output contact substep review text.
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
		$is_step_complete = true;

		// Get contact fields
		$contact_field_ids = $this->get_contact_step_display_field_ids();
		
		// Get all checkout fields
		$field_groups = WC()->checkout()->get_checkout_fields();
			
		// Iterate contact field ids
		foreach( $contact_field_ids as $field_key ) {
			foreach ( $field_groups as $group_key => $fields ) {
				// Check field exists
				if ( array_key_exists( $field_key, $fields ) ) {
					// Check required fields
					if ( array_key_exists( 'required', $fields[ $field_key ] ) && $fields[ $field_key ][ 'required' ] === true && ! WC()->checkout()->get_value( $field_key ) ) {
						$is_step_complete = false;
						break 2;
					}
				}
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
		return array_unique( apply_filters( 'fc_checkout_contact_step_field_ids', array(
			'billing_email',
		) ) );
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
		$this->output_substep_start_tag( $step_id, $substep_id, null );

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
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title );

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
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title );

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
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title );

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
		do_action( 'fc_checkout_before_step_shipping_fields' );
		echo $this->get_substep_shipping_address_fields();
		do_action( 'fc_checkout_after_step_shipping_fields' );
	}

	/**
	 * Get shipping address step fields html.
	 */
	public function get_substep_shipping_address_fields() {
		ob_start();

		// Filter out shipping fields moved to another step
		$shipping_fields = WC()->checkout()->get_checkout_fields( 'shipping' );
		$shipping_fields = array_filter( $shipping_fields, function( $key ) {
			return ! in_array( $key, $this->get_shipping_address_ignored_shipping_field_ids() );
		}, ARRAY_FILTER_USE_KEY );

		// Get list of shipping fields that might be copied from shipping to billing fields
		$shipping_same_as_billing_fields = array_filter( $shipping_fields, function( $key ) {
			return in_array( $key, $this->get_shipping_same_billing_fields_keys() );
		}, ARRAY_FILTER_USE_KEY );

		// Get list of billing only fields
		$shipping_only_fields = array_filter( $shipping_fields, function( $key ) {
			return in_array( $key, $this->get_shipping_only_fields_keys() );
		}, ARRAY_FILTER_USE_KEY );

		wc_get_template(
			'checkout/form-shipping.php',
			array(
				'checkout'                            => WC()->checkout(),
				'display_fields'                      => array_keys( $shipping_fields ),
				'shipping_same_as_billing_fields'     => $shipping_same_as_billing_fields,
				'shipping_only_fields'                => $shipping_only_fields,
			)
		);

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
	 * Get the display value for the custom checkout fields.
	 *
	 * @param   mixed  $field_display_value  The display value for the custom checkout field.
	 */
	public function get_field_display_value_from_array( $field_display_value ) {
		// Bail if not an array value
		if ( ! is_array( $field_display_value ) ) { return $field_display_value; }
		
		// Initialize resulting string value
		$new_field_display_value = '';
		
		// Iterate each array value to add to the new display value
		$first = true;
		foreach ( $field_display_value as $value ) {
			if ( ! $first ) { $new_field_display_value .= ', '; }
			$new_field_display_value .= $value;
			$first = false;
		}

		return $new_field_display_value;
	}

	/**
	 * Get the display value for the custom checkout fields with multiple options object.
	 *
	 * @param   string  $field_value       The field value.
	 * @param   string  $field_key         The field key.
	 * @param   array   $field_options     The field options object.
	 * @param   string  $field_label       The field label.
	 * @param   bool    $show_field_label  Whether to show the field label on the display value.
	 */
	public function get_field_display_value_with_pattern( $field_value, $field_key, $field_options, $field_label, $show_field_label = false ) {
		$field_display_value = $field_value;
		
		/* translators: %1$s the selected option text, %2$s the field label. */
		$field_display_value_pattern = _x( '%1$s', 'Substep review field format', 'fluid-checkout' );

		// // Get field display value pattern
		if ( $show_field_label ) {
			/* translators: %1$s the selected option text, %2$s the field label. */
			$field_display_value_pattern = _x( '%2$s: %1$s', 'Substep review field format: with label', 'fluid-checkout' );
		}

		// Apply field display value pattern
		if ( ! empty( $field_display_value ) ) {
			$field_display_value = sprintf( $field_display_value_pattern, $field_value, $field_label );
		}

		return $field_display_value;
	}

	/**
	 * Get the display value for the custom checkout fields with multiple options object.
	 *
	 * @param   string  $field_value       The field value.
	 * @param   string  $field_key         The field key.
	 * @param   array   $field_args        The custom field arguments.
	 * @param   string  $field_label       The field label.
	 * @param   bool    $show_field_label  Whether to show the field label on the display value.
	 */
	public function get_field_display_value_from_field_options( $field_value, $field_key, $field_args, $field_label, $show_field_label = false ) {
		// Bail if not valid field value or option value non existent
		if ( empty( $field_value ) || ! array_key_exists( 'options', $field_args ) || ! is_array( $field_args[ 'options' ] ) || ! array_key_exists( $field_value, $field_args[ 'options' ] ) ) { return $field_value; }

		// Get selected option display text
		$field_display_value = $field_args[ 'options' ][ $field_value ];

		return $this->get_field_display_value_with_pattern( $field_display_value, $field_key, $field_args, $field_label, $show_field_label );
	}

	/**
	 * Get the display value for the custom checkout fields.
	 *
	 * @param   mixed   $field_value       The field value.
	 * @param   string  $field_key         The field key.
	 * @param   array   $field_args        The custom field arguments.
	 */
	public function get_field_display_value( $field_value, $field_key, $field_args ) {
		$field_display_value = $field_value;
		$field_label = ! empty( $field_args[ 'label' ] ) ? $field_args[ 'label' ] : $field_key;

		// Get field type
		$field_type = array_key_exists( 'type', $field_args ) ? $field_args[ 'type' ] : 'text';

		// Only process if field value is not empty
		if ( ! empty( $field_value ) ) {

			// Get show label flag
			$show_field_label = apply_filters( 'fc_substep_text_display_value_show_field_label', false );

			// Get field display values based on type
			switch ( $field_type ) {
				case 'hidden':
					$field_display_value = null;
					break;
				case 'text':
				case 'textarea':
				case 'datetime':
				case 'datetime-local':
				case 'date':
				case 'month':
				case 'time':
				case 'week':
				case 'email':
				case 'url':
				case 'tel':
					$field_display_value = $this->get_field_display_value_with_pattern( $field_display_value, $field_key, $field_args, $field_label, apply_filters( "fc_substep_text_display_value_show_field_label_{$field_type}", $show_field_label ) );
					break;
				case 'number':
				case 'checkbox':
					$field_display_value = $this->get_field_display_value_with_pattern( $field_display_value, $field_key, $field_args, $field_label, apply_filters( "fc_substep_text_display_value_show_field_label_{$field_type}", true ) );
					break;
				case 'password':
					$field_display_value = str_repeat( apply_filters( 'fc_substep_text_display_value_' . $field_type . '_char', '*' ), strlen( $field_value ) );
					$field_display_value = $this->get_field_display_value_with_pattern( $field_display_value, $field_key, $field_args, $field_label, apply_filters( "fc_substep_text_display_value_show_field_label_{$field_type}", $show_field_label ) );
					break;
				case 'country':
				case 'state':
				case 'radio':
				case 'select':
					$field_display_value = $this->get_field_display_value_from_field_options( $field_value, $field_key, $field_args, $field_label, apply_filters( "fc_substep_text_display_value_show_field_label_{$field_type}", $show_field_label ) );
					break;
				default:
					$field_display_value = $this->get_field_display_value_from_array( $field_display_value );
					$field_display_value = $this->get_field_display_value_with_pattern( $field_display_value, $field_key, $field_args, $field_label, apply_filters( "fc_substep_text_display_value_show_field_label_{$field_type}", $show_field_label ) );
					break;
			}
		}

		$field_display_value = apply_filters( 'fc_substep_text_display_value_' . $field_type, $field_display_value, $field_value, $field_key, $field_args );
		$field_display_value = apply_filters( 'fc_substep_text_display_value_' . $field_key, $field_display_value, $field_value, $field_key, $field_args );

		return $field_display_value;
	}



	/**
	 * Get the address substep review text for the address type.
	 * 
	 * @param   string  $address_type  The address type.
	 */
	public function get_substep_text_formatted_address_text_line( $address_type ) {
		// Field prefix
		$field_key_prefix = $address_type . '_';

		// Get field keys from checkout fields
		$address_data = array();
		$fields = WC()->checkout()->get_checkout_fields( $address_type );
		$field_keys = array_keys( $fields );
		
		// Get data from checkout fields
		foreach ( $field_keys as $field_key ) {
			// Get field key
			$address_field_key = str_replace( $field_key_prefix, '', $field_key );
			$address_data[ $address_field_key ] = WC()->checkout->get_value( $field_key );
		}

		$address_data = apply_filters( 'fc_'.$address_type.'_substep_text_address_data', $address_data );

		return WC()->countries->get_formatted_address( $address_data );
	}

	/**
	 * Add the address substep review text lines for the address type.
	 * 
	 * @param   string  $address_type  The address type.
	 * @param   array   $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function get_substep_text_lines_address_type( $address_type, $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Add formatted address line
		$review_text_lines[] = $this->get_substep_text_formatted_address_text_line( $address_type );

		return $review_text_lines;
	}

	/**
	 * Add the address substep review text lines for extra fields of the address type.
	 * 
	 * @param   string  $address_type  The address type.
	 * @param   array   $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function get_substep_text_lines_extra_fields_address_type( $address_type, $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get address fields
		$address_fields = WC()->checkout->get_checkout_fields( $address_type );

		// Define list of address fields to skip as the formatted address has already been added
		$field_keys_skip_list = apply_filters( "fc_substep_text_{$address_type}_address_field_keys_skip_list", array(
			$address_type . '_first_name',
			$address_type . '_last_name',
			$address_type . '_company',
			$address_type . '_country',
			$address_type . '_address_1',
			$address_type . '_address_2',
			$address_type . '_city',
			$address_type . '_state',
			$address_type . '_postcode',
			$address_type . '_phone',
			$address_type . '_email',
		) );

		// Add extra fields lines
		foreach ( $address_fields as $field_key => $field_args ) {
			// Skip some fields
			if ( in_array( $field_key, $field_keys_skip_list ) ) { continue; }
			
			// Get field display value
			$field_value = WC()->checkout->get_value( $field_key );
			$field_display_value = $this->get_field_display_value( $field_value, $field_key, $field_args );

			// Maybe add field
			if ( ! empty( $field_display_value ) ) {
				$review_text_lines[] = $field_display_value;
			}
		}

		return $review_text_lines;
	}



	/**
	 * Add the shipping address substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_address( $review_text_lines = array() ) {
		return $this->get_substep_text_lines_address_type( 'shipping', $review_text_lines );
	}

	/**
	 * Add the shipping address substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_extra_fields_shipping_address( $review_text_lines = array() ) {
		return $this->get_substep_text_lines_extra_fields_address_type( 'shipping', $review_text_lines );
	}

	/**
	 * Get shipping address substep review text.
	 */
	public function get_substep_text_shipping_address() {
		return $this->get_substep_review_text( 'shipping_address' );
	}

	/**
	 * Add shipping address substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_address_text_fragment( $fragments ) {
		$html = $this->get_substep_text_shipping_address();
		$fragments['.fc-step__substep-text-content--shipping_address'] = $html;
		return $fragments;
	}

	/**
	 * Output shipping address substep review text.
	 */
	public function output_substep_text_shipping_address() {
		echo $this->get_substep_text_shipping_address();
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		$packages = WC()->shipping()->get_packages();

		foreach ( $packages as $i => $package ) {
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;
			$chosen_method_label = $method ? wc_cart_totals_shipping_method_label( $method ) : __( 'Not selected yet.', 'fluid-checkout' );

			// TODO: Maybe handle multiple packages
			// $package_name = apply_filters( 'woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'woocommerce' ), ( $i + 1 ) ) : _x( 'Shipping', 'shipping packages', 'woocommerce' ), $i, $package );

			$review_text_lines[] = wp_kses( $chosen_method_label, array( 'span' => array( 'class' => '' ), 'bdi' => array(), 'strong' => array() ) );
		}

		return $review_text_lines;
	}

	/**
	 * Get shipping methods substep review text.
	 */
	public function get_substep_text_shipping_method() {
		return $this->get_substep_review_text( 'shipping_method' );
	}

	/**
	 * Add shipping methods substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_methods_text_fragment( $fragments ) {
		$html = $this->get_substep_text_shipping_method();
		$fragments['.fc-step__substep-text-content--shipping_method'] = $html;
		return $fragments;
	}

	/**
	 * Output shipping method substep review text.
	 */
	public function output_substep_text_shipping_method() {
		echo $this->get_substep_text_shipping_method();
	}



	/**
	 * Add the order notes substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_order_notes( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get order notes
		$order_notes = WC()->checkout()->get_value( 'order_comments' );

		// The order notes value
		if ( ! empty( $order_notes ) ) {
			$review_text_lines[] = $order_notes;
		}
		// "No order notes" notice.
		else {
			$review_text_lines[] = apply_filters( 'fc_no_order_notes_order_review_notice', _x( 'None.', 'Notice for no order notes provided', 'fluid-checkout' ) );
		}

		return $review_text_lines;
	}

	/**
	 * Get order notes substep review text.
	 */
	public function get_substep_text_order_notes() {
		return $this->get_substep_review_text( 'order_notes' );
	}

	/**
	 * Add order notes substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_order_notes_text_fragment( $fragments ) {
		$html = $this->get_substep_text_order_notes();
		$fragments['.fc-step__substep-text-content--order_notes'] = $html;
		return $fragments;
	}

	/**
	 * Output order notes substep review text.
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
		$is_step_complete = true;

		// Check required data for shipping address
		if ( WC()->cart->needs_shipping_address() ) {
			// Get shipping country
			$shipping_country = WC()->customer->get_shipping_country();

			// Try get value from session
			$shipping_country_session = $this->get_checkout_field_value_from_session( 'shipping_country' );
			if ( isset( $shipping_country_session ) && ! empty( $shipping_country_session ) ) {
				$shipping_country = $shipping_country_session;
			}

			// Get address fields for country
			$address_fields = WC()->countries->get_address_fields( $shipping_country, 'shipping_' );

			// Get fields skip list
			$step_complete_field_keys_skip_list = apply_filters( 'fc_is_step_complete_shipping_field_keys_skip_list', $this->get_contact_step_display_field_ids() );

			// Check each required country field
			foreach ( $address_fields as $field_key => $field ) {
				// Skip checking some fields
				if ( in_array( $field_key, $step_complete_field_keys_skip_list ) ) { continue; }

				if ( array_key_exists( 'required', $field ) && $field[ 'required' ] === true && empty( WC()->checkout()->get_value( $field_key ) ) ) {
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
	 * Get hook priority for the shipping address substep.
	 */
	public function get_shipping_address_hook_priority() {
		$priority = 10;

		// Change priority depending on the settings
		if ( 'before_shipping_address' === get_option( 'fc_shipping_methods_substep_position', 'after_shipping_address' ) ) {
			$priority = 20;
		}

		return $priority;
	}

	/**
	 * Get hook priority for the shipping methods substep.
	 */
	public function get_shipping_methods_hook_priority() {
		$priority = 20;

		// Change priority depending on the settings
		if ( 'before_shipping_address' === get_option( 'fc_shipping_methods_substep_position', 'after_shipping_address' ) ) {
			$priority = 10;
		}

		return $priority;
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

				$method_costs = wc_price( $method->cost );
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
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title );

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
		do_action( 'fc_checkout_before_step_billing_fields' );
		echo $this->get_substep_billing_address_fields();
		do_action( 'fc_checkout_after_step_billing_fields' );
	}

	/**
	 * Get billing address fields, except those already added at the contact step.
	 */
	public function get_substep_billing_address_fields() {
		ob_start();

		// Get checkout object and billing fields, with ignored billing fields removed
		$billing_fields = WC()->checkout()->get_checkout_fields( 'billing' );
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

		wc_get_template(
			'checkout/form-billing.php',
			array(
				'checkout'                         => WC()->checkout(),
				'billing_same_as_shipping_fields'  => $billing_same_as_shipping_fields,
				'billing_only_fields'              => $billing_only_fields,
				'is_billing_same_as_shipping'      => $this->is_billing_same_as_shipping(),
			)
		);

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
	 * Add the billing methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_billing_address( $review_text_lines = array() ) {
		// Maybe display billing same as shipping notice
		if ( $this->is_billing_same_as_shipping() && true === apply_filters( 'fc_billing_same_as_shipping_display_substep_review_text_notice', true ) ) {
			$review_text_lines[] = '<em>' . $this->get_option_label_billing_same_as_shipping() . '</em>';
		}
		// Otherwise, display the address data
		else {
			$review_text_lines = $this->get_substep_text_lines_address_type( 'billing', $review_text_lines );
		}

		return $review_text_lines;
	}

	/**
	 * Add the billing address substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_extra_fields_billing_address( $review_text_lines = array() ) {
		return $this->get_substep_text_lines_extra_fields_address_type( 'billing', $review_text_lines );
	}

	/**
	 * Get billing address substep review text.
	 */
	public function get_substep_text_billing_address() {
		return $this->get_substep_review_text( 'billing_address' );
	}

	/**
	 * Add billing address substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_billing_address_text_fragment( $fragments ) {
		$html = $this->get_substep_text_billing_address();
		$fragments['.fc-step__substep-text-content--billing_address'] = $html;
		return $fragments;
	}

	/**
	 * Output billing address substep review text.
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
		$is_step_complete = true;

		// Get billing country
		$billing_country = WC()->customer->get_billing_country();

		// Try get value from session
		$billing_country_session = $this->get_checkout_field_value_from_session( 'billing_country' );
		if ( isset( $billing_country_session ) && ! empty( $billing_country_session ) ) {
			$billing_country = $billing_country_session;
		}

		// Get address fields for country
		$address_fields = WC()->countries->get_address_fields( $billing_country, 'billing_' );

		// Get fields skip list
		$step_complete_field_keys_skip_list = apply_filters( 'fc_is_step_complete_billing_field_keys_skip_list', $this->get_contact_step_display_field_ids() );
		
		// Check each required country field
		foreach ( $address_fields as $field_key => $field ) {
			// Skip checking some fields
			if ( in_array( $field_key, $step_complete_field_keys_skip_list ) ) { continue; }

			if ( array_key_exists( 'required', $field ) && $field[ 'required' ] === true && empty( WC()->checkout()->get_value( $field_key ) ) ) {
				$is_step_complete = false;
				break;
			}
		}

		return apply_filters( 'fc_is_step_complete_billing', $is_step_complete );
	}


	/**
	 * Get the label for billing same as shipping option.
	 */
	public function get_option_label_billing_same_as_shipping() {
		return apply_filters( 'fc_billing_same_as_shipping_option_label', __( 'Same as shipping address', 'fluid-checkout' ) );
	}

	/**
	 * Output field for billing address same as shipping.
	 */
	public function output_billing_same_as_shipping_field() {
		// Output a hidden field when shipping country not allowed for billing, or shipping not needed
		if ( apply_filters( 'fc_output_billing_same_as_shipping_as_hidden_field', false ) || ! $this->is_shipping_address_available_for_billing() ) : ?>
			<input type="hidden" name="billing_same_as_shipping" id="billing_same_as_shipping" value="<?php echo $this->is_billing_same_as_shipping_checked() ? '1' : '0'; // WPCS: XSS ok. ?>">
		<?php
		// Output the checkbox when shipping country is allowed for billing
		else :
		?>
			<p id="billing_same_as_shipping_field" class="form-row form-row-wide">
				<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="billing_same_as_shipping" id="billing_same_as_shipping" value="1" <?php checked( $this->is_billing_same_as_shipping(), true ); ?>>
				<label for="billing_same_as_shipping"><?php echo esc_html( $this->get_option_label_billing_same_as_shipping() ); ?></label>
			</p>
		<?php
		endif;

		// Output the current value as a hidden field
		// to be able to detect when the value changes
		?>
		<input type="hidden" name="billing_same_as_shipping_previous" id="billing_same_as_shipping_previous" value="<?php echo $this->is_billing_same_as_shipping_checked() ? '1' : '0'; // WPCS: XSS ok. ?>">
		<?php

	}



	/**
	 * Check whether a country is allowed for shipping.
	 *
	 * @param   string  $country_code  Country code to check against site settings.
	 *
	 * @return  bool                   `true` when the country is allowed for shipping addresses, `false` otherwise.
	 */
	public function is_country_allowed_for_shipping( $country_code ) {
		// Bail if countries object not available
		if ( ! function_exists( 'WC' ) || null === WC()->countries ) { return false; }

		$allowed_countries = WC()->countries->get_shipping_countries();
		return in_array( $country_code, array_keys( $allowed_countries ) );
	}

	/**
	 * Check whether a country is allowed for billing.
	 *
	 * @param   string  $country_code  Country code to check against site settings.
	 *
	 * @return  bool                   `true` when the country is allowed for billing addresses, `false` otherwise.
	 */
	public function is_country_allowed_for_billing( $country_code ) {
		// Bail if countries object not available
		if ( ! function_exists( 'WC' ) || null === WC()->countries ) { return false; }

		$allowed_countries = WC()->countries->get_allowed_countries();
		return in_array( $country_code, array_keys( $allowed_countries ) );
	}

	/**
	 * Check whether the selected shipping country is also available for billing country.
	 *
	 * @return  mixed  `true` if the selected shipping country is also available for billing country, `false` if the shipping country is not allowed for billing, and `null` if the shipping country is not set.
	 */
	public function is_shipping_country_allowed_for_billing() {
		// Bail if customer object not available
		if ( ! function_exists( 'WC' ) || null === WC()->customer ) { return null; }

		// Get shipping value from customer data
		$customer = WC()->customer;
		$shipping_country = $customer->get_shipping_country();

		// Try get value from session
		$shipping_country_session = $this->get_checkout_field_value_from_session( 'shipping_country' );
		if ( isset( $shipping_country_session ) && ! empty( $shipping_country_session ) ) {
			$shipping_country = $shipping_country_session;
		}

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
		if ( null !== $shipping_country && ! empty( $shipping_country ) ) {
			return $this->is_country_allowed_for_billing( $shipping_country );
		}

		return null;
	}

	/**
	 * Check whether the shipping address is available to be used for the billing address.
	 */
	public function is_shipping_address_available_for_billing() {
		// Bail if cart is not available
		if ( ! function_exists( 'WC' ) || null === WC()->cart ) { return false; }

		return WC()->cart->needs_shipping_address() && true === $this->is_shipping_country_allowed_for_billing();
	}



	/**
	 * Determine if billing address field values are the same as shipping address.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function is_billing_address_data_same_as_shipping( $posted_data = array() ) {
		// Get parsed posted data
		if ( empty( $posted_data ) ) {
			$posted_data = $this->get_parsed_posted_data();
		}

		// Allow developers to hijack the returning value
		$value_from_filter = apply_filters( 'fc_is_billing_address_data_same_as_shipping_before', null );
		if ( null !== $value_from_filter ) {
			return $value_from_filter;
		}

		$is_billing_same_as_shipping = true;
		
		// Get list of billing fields to copy from shipping fields
		$billing_copy_shipping_field_keys = $this->get_billing_same_shipping_fields_keys();

		// Get shipping fields
		$shipping_fields = WC()->checkout->get_checkout_fields( 'shipping' );

		// Iterate posted data
		foreach( $billing_copy_shipping_field_keys as $field_key ) {

			// Get shipping field key
			$shipping_field_key = str_replace( 'billing_', 'shipping_', $field_key );
			
			// Check billing field values against shipping
			if ( array_key_exists( $shipping_field_key, $shipping_fields ) ) {
				$billing_field_value = null;
				$shipping_field_value = null;

				// Maybe get field values from posted data
				if ( isset( $_POST['post_data'] ) ) {
					$billing_field_value = array_key_exists( $field_key, $posted_data ) ? $posted_data[ $field_key ] : null;
					$shipping_field_value = array_key_exists( $shipping_field_key, $posted_data ) ? $posted_data[ $shipping_field_key ] : null;
				}
				// Maybe get field values from checkout fields
				else {
					$billing_field_value = WC()->checkout->get_value( $field_key );
					$shipping_field_value = WC()->checkout->get_value( $shipping_field_key );
				}

				if ( $billing_field_value !== $shipping_field_value ) {
					$is_billing_same_as_shipping = false;
					break;
				}
			}

		}

		return $is_billing_same_as_shipping;
	}

	/**
	 * Check whether the checkbox "billing address same as shipping" is checked.
	 * This function will return `true` even if the shipping country is not allowed for billing,
	 * use `is_billing_same_as_shipping` to also check if the shipping country is allowed for billing.
	 * 
	 * @param  array  $posted_data   Post data for all checkout fields.
	 *
	 * @return  bool  `true` checkbox "billing address same as shipping" is checked, `false` otherwise.
	 */
	public function is_billing_same_as_shipping_checked( $posted_data = array() ) {
		// Get parsed posted data
		if ( empty( $posted_data ) ) {
			$posted_data = $this->get_parsed_posted_data();
		}

		// Set default value
		$billing_same_as_shipping = apply_filters( 'fc_default_to_billing_same_as_shipping', 'yes' === get_option( 'fc_default_to_billing_same_as_shipping', 'yes' ) );
		
		// Maybe set as same as shipping for logged users
		if ( is_user_logged_in() ) {
			$billing_same_as_shipping = $this->is_billing_address_data_same_as_shipping( $posted_data );
		}

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

		// Filter to allow for customizations
		$billing_same_as_shipping = apply_filters( 'fc_is_billing_same_as_shipping_checked', $billing_same_as_shipping );

		return $billing_same_as_shipping;
	}



	/**
	 * Get value for whether the billing address is the same as the shipping address.
	 * 
	 * @param  array  $posted_data   Post data for all checkout fields.
	 *
	 * @return  bool  `true` if the billing address is the same as the shipping address, `false` otherwise.
	 */
	public function is_billing_same_as_shipping( $posted_data = array() ) {
		// Set to different billing address when shipping country not allowed
		if ( null === $this->is_shipping_country_allowed_for_billing() || ! $this->is_shipping_country_allowed_for_billing() ) {
			return false;
		}

		// Set to different billing address when shipping address not needed
		if ( ! WC()->cart->needs_shipping_address() ) {
			return false;
		}

		return $this->is_billing_same_as_shipping_checked( $posted_data );
	}

	/**
	 * Save value of `billing_same_as_shipping` to the current user session.
	 */
	public function set_billing_same_as_shipping_session( $billing_same_as_shipping ) {
		// Set session value
		WC()->session->set( 'fc_billing_same_as_shipping', $billing_same_as_shipping ? '1' : '0');
	}



	/**
	 * Get list of shipping checkout field keys which values are to be copied from shipping to billing fields.
	 *
	 * @return  array  List of checkout field keys.
	 */
	public function get_shipping_same_billing_fields_keys() {
		// Intialize list of supported field keys
		$shipping_copy_shipping_field_keys = array();

		// Get checkout object and fields
		$billing_fields = WC()->checkout()->get_checkout_fields( 'billing' );
		$shipping_fields = WC()->checkout()->get_checkout_fields( 'shipping' );

		// Get list of billing fields to skip copying from shipping fields
		$skip_field_keys = apply_filters( 'fc_shipping_same_as_billing_skip_fields', array() );

		// Use the `WC_Customer` object for supported properties
		foreach ( $shipping_fields as $field_key => $field_args ) {

			// Get billing field key
			$billing_field_key = str_replace( 'shipping_', 'billing_', $field_key );

			// Maybe add field key to the list of fields to copy
			if ( ! in_array( $field_key, $skip_field_keys ) && array_key_exists( $billing_field_key, $billing_fields ) ) {
				$shipping_copy_shipping_field_keys[] = $field_key;
			}

		}

		// Remove ignored shipping fields
		$shipping_copy_shipping_field_keys = array_diff( $shipping_copy_shipping_field_keys, $this->get_shipping_address_ignored_shipping_field_ids() );

		return apply_filters( 'fc_shipping_same_as_billing_field_keys', $shipping_copy_shipping_field_keys );
	}

	/**
	 * Get list of billing checkout field keys which values are to be copied from shipping to billing fields.
	 *
	 * @return  array  List of checkout field keys.
	 */
	public function get_billing_same_shipping_fields_keys() {
		// Intialize list of supported field keys
		$billing_copy_shipping_field_keys = array();

		// Get checkout fields
		$billing_fields = WC()->checkout()->get_checkout_fields( 'billing' );
		$shipping_fields = WC()->checkout()->get_checkout_fields( 'shipping' );

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
	 * Get list of shipping only fields, that is, fields that are not present on both shipping and billing fields,
	 * which would be copied when "Billing same as shipping" is cheched. Also remove the fields which are to be
	 * ignored when copying values from the shipping to billing.
	 *
	 * @return  array  List of checkout field keys.
	 */
	public function get_shipping_only_fields_keys() {
		// Get checkout object and fields
		$shipping_fields = WC()->checkout()->get_checkout_fields( 'shipping' );

		// Get list of shipping fields to copy from shipping to billing
		$shipping_copy_shipping_field_keys = $this->get_shipping_same_billing_fields_keys();

		// Get list of shipping only fields
		$shipping_only_field_keys = array_diff( array_keys( $shipping_fields ), $shipping_copy_shipping_field_keys );

		// Remove ignored shipping fields
		$shipping_only_field_keys = array_diff( $shipping_only_field_keys, $this->get_shipping_address_ignored_shipping_field_ids() );

		return $shipping_only_field_keys;
	}

	/**
	 * Get list of billing only fields, that is, fields that are not present on both shipping and billing fields,
	 * which would be copied when "Billing same as shipping" is cheched. Also remove the fields which are to be
	 * ignored when copying values from the shipping to billing.
	 *
	 * @return  array  List of checkout field keys.
	 */
	public function get_billing_only_fields_keys() {
		// Get checkout object and fields
		$billing_fields = WC()->checkout()->get_checkout_fields( 'billing' );

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
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_billing_address_same_as_shipping( $posted_data ) {
		// Get value for billing same as shipping
		$is_billing_same_as_shipping_previous = $posted_data[ 'billing_same_as_shipping_previous' ];
		$is_billing_same_as_shipping = $this->is_billing_same_as_shipping( $posted_data );
		$is_billing_same_as_shipping_checked = $this->is_billing_same_as_shipping_checked( $posted_data );

		// Save checked state of the billing same as shipping field to the session,
		// for the case the shipping country changes again and the new value is also accepted for billing.
		$this->set_billing_same_as_shipping_session( $is_billing_same_as_shipping_checked );

		// Get list of billing fields to copy from shipping fields
		$billing_copy_shipping_field_keys = $this->get_billing_same_shipping_fields_keys();

		// Get list of posted data keys
		$posted_data_field_keys = array_keys( $posted_data );

		// Maybe set post data for billing same as shipping
		if ( $is_billing_same_as_shipping ) {

			// Iterate posted data
			foreach( $billing_copy_shipping_field_keys as $field_key ) {

				// Get related field keys
				$shipping_field_key = str_replace( 'billing_', 'shipping_', $field_key );
				$save_field_key = str_replace( 'billing_', 'save_billing_', $field_key );

				// Update billing field values
				if ( in_array( $shipping_field_key, $posted_data_field_keys ) ) {
					// Maybe save billing address data
					if ( '0' === $is_billing_same_as_shipping_previous && ! apply_filters( 'fc_save_new_address_data_billing_skip_update', false ) ) {
						$posted_data[ $save_field_key ] = $posted_data[ $field_key ];
					}

					// Copy field value from shipping fields
					$new_field_value = isset( $posted_data[ $shipping_field_key ] ) ? $posted_data[ $shipping_field_key ] : null;
					$posted_data[ $field_key ] = $new_field_value;
					$_POST[ $field_key ] = $new_field_value;
				}

			}

		}
		// When switching to "billing same as shipping" unchecked, copy from saved billing address fields.
		else if ( '1' === $is_billing_same_as_shipping_previous ) {

			// Iterate posted data
			foreach( $billing_copy_shipping_field_keys as $field_key ) {

				// Get related field keys
				$save_field_key = str_replace( 'billing_', 'save_billing_', $field_key );

				$new_field_value = $this->get_checkout_field_value_from_session( $save_field_key );
				$posted_data[ $field_key ] = $new_field_value;
				$_POST[ $field_key ] = $new_field_value;

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
		$this->output_substep_start_tag( $step_id, $substep_id, $substep_title );

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
	 * Run the action hook `woocommerce_checkout_after_customer_details`.
	 */
	public function run_action_woocommerce_checkout_after_customer_details() {
		do_action( 'woocommerce_checkout_after_customer_details' );
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

		// Get checkout fields
		$fields = WC()->checkout()->get_checkout_fields();

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
	 * @param   array  $parsed_posted_data  The parsed posted data.
	 *
	 * @return  array                       List of checkout field keys.
	 */
	public function get_customer_session_field_keys( $parsed_posted_data = null ) {
		// Get parsed posted data
		if ( null === $parsed_posted_data ) {
			$parsed_posted_data = $this->get_parsed_posted_data();
		}

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
	 * Parse the data from the `post_data` request parameter into an `array`.
	 */
	public function set_parsed_posted_data() {
		// Get sanitized posted data as a string
		$posted_data = isset( $_POST[ 'post_data' ] ) ? wp_unslash( $_POST[ 'post_data' ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Define new posted data
		$new_posted_data = array();

		// Set as posted data as empty array if `post_data` was not sent
		if ( '' === $posted_data ) {
			// Updated cached posted data
			$this->posted_data = $new_posted_data;
			return;
		}

		// Parsing posted data into an array
		$vars = explode( '&', $posted_data );
		foreach ( $vars as $key => $value ) {
			// Get decoded data
			$decoded_data = explode( '=', urldecode( $value ) );
			$field_key = $decoded_data[0];
			
			// Handle multi value fields
			$needle = '[]';
			$needle_len = strlen( $needle );
        	if ( $needle_len === 0 || 0 === substr_compare( $field_key, $needle, - $needle_len ) ) {
				// Get new field key, without the multi value markers
				$new_field_key = str_replace( $needle, '', $field_key );

				// Initialize field array on posted data
				if ( ! array_key_exists( $new_field_key, $new_posted_data ) ) {
					$new_posted_data[ $new_field_key ] = array();
				}
				// Add new field value
				$new_posted_data[ $new_field_key ][] = array_key_exists( 1, $decoded_data ) ? wc_clean( wp_unslash( $decoded_data[1] ) ) : null;
			}
			// Handle single value fields
			else {
				$new_posted_data[ $field_key ] = array_key_exists( 1, $decoded_data ) ? wc_clean( wp_unslash( $decoded_data[1] ) ) : null;
			}
		}

		// Maybe apply filter
		if ( ! $this->set_parsed_posted_data_filter_applied ) {
			$this->set_parsed_posted_data_filter_applied = true;

			// Updated cached posted data
			// Needed to make already parsed data available for all functions,
			// especially those used by filters hooked to `fc_set_parsed_posted_data` below.
			$this->posted_data = $new_posted_data;
			
			// Filter to allow customizations
			$new_posted_data = apply_filters( 'fc_set_parsed_posted_data', $new_posted_data );
		}

		// Updated cached posted data
		$this->posted_data = $new_posted_data;
	}

	/**
	 * Parse the data from the `post_data` request parameter into an `array`.
	 *
	 * @return  array  Post data for all checkout fields parsed into an `array`.
	 */
	public function get_parsed_posted_data() {
		// Maybe initialize posted data
		if ( null === $this->posted_data ) {
			$this->set_parsed_posted_data();
		}

		return $this->posted_data;
	}

	/**
	 * Clear persisted data for remaining checkout fields, usually optional empty `checkbox`, `radio` and `select` fields.
	 *
	 * @param  string  $posted_data  Post data for all checkout fields.
	 */
	public function reset_remaining_customer_persisted_data( $posted_data ) {
		// Bail if not updating via AJAX call
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) { return $posted_data; }

		// Get parsed posted data
		if ( empty( $posted_data ) ) {
			$posted_data = $this->get_parsed_posted_data();
		}

		// Bail if no posted data is available
		if ( empty( $posted_data ) ) { return $posted_data; }

		// Get all checkout fields
		$field_groups = WC()->checkout()->get_checkout_fields();
		
		// Iterate checkout fields
		foreach ( $field_groups as $group_key => $fields ) {
			// Skip shipping fields if shipping address is not needed
			if ( 'shipping' === $group_key && ! WC()->cart->needs_shipping_address() ) { continue; }

			foreach( $fields as $field_key => $field_args ) {
				// Skip fields in posted data
				if ( in_array( $field_key, array_keys( $posted_data ) ) ) { continue; }

				// Set session value as empty
				$this->set_checkout_field_value_to_session( $field_key, '' );
				$posted_data[ $field_key ] = '';
			}
		}

		return $posted_data;
	}

	/**
	 * Update the customer's data to the WC_Customer object.
	 *
	 * @param  string  $posted_data  Post data for all checkout fields.
	 */
	public function update_customer_persisted_data( $posted_data ) {
		// Get parsed posted data
		if ( empty( $posted_data ) ) {
			$posted_data = $this->get_parsed_posted_data();
		}

		// Get customer object and supported field keys
		$customer_supported_field_keys = $this->get_supported_customer_property_field_keys();

		// Use the `WC_Customer` object for supported properties
		foreach ( $customer_supported_field_keys as $field_key ) {
			// Get the setter method name for the customer property
			$setter = "set_$field_key";

			// Check if the setter method is supported
			if ( is_callable( array( WC()->customer, $setter ) ) ) {
				// Set property value to the customer object
				if ( array_key_exists( $field_key, $posted_data ) ) {
					WC()->customer->{$setter}( $posted_data[ $field_key ] );
				}
			}
		}

		// Save/commit changes to the customer object
		WC()->customer->save();

		// Get list of fields to save to the session
		$session_field_keys = $this->get_customer_session_field_keys( $posted_data );

		// Save customer data to the session
		foreach ( $session_field_keys as $field_key ) {

			// Set property value to the customer object
			if ( array_key_exists( $field_key, $posted_data ) ) {
				// Set session value
				$this->set_checkout_field_value_to_session( $field_key, $posted_data[ $field_key ] );
			}
			else {
				// Set session value as empty
				$this->set_checkout_field_value_to_session( $field_key, null );
			}

		}

		// Clear values for remaining checkout fields
		// Usually optional empty `checkbox`, `radio` and `select` fields
		$posted_data = $this->reset_remaining_customer_persisted_data( $posted_data );

		return $posted_data;
	}



	/**
	 * Get the list of address fields to update on the checkout form.
	 *
	 * @param   string  $address_type  The address type.
	 */
	public function get_address_field_keys( $address_type ) {
		$field_key_prefix = $address_type . '_';

		// Get field keys from checkout fields
		$fields = WC()->checkout()->get_checkout_fields( $address_type );
		$field_keys = array_keys( $fields );

		// Skip some fields
		$skip_field_keys = apply_filters( 'fc_address_field_keys_skip_list', array( $field_key_prefix.'email' ) );
		$field_keys = array_diff( $field_keys, $skip_field_keys );

		// Maybe remove billing only fields
		if ( 'billing' === $address_type ) {
			$field_keys = array_diff( $field_keys, $this->get_billing_only_fields_keys() );
		}

		// Filter to allow customizations
		$field_keys = apply_filters( 'fc_address_field_keys', $field_keys, $address_type );

		return $field_keys;
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
		if ( null !== $field_session_value ) {
			return $field_session_value;
		}

		return $value;
	}

	/**
	 * Get values for a checkout field from the session.
	 *
	 * @param   string  $field_key  Checkout field key name (ie. order_comments ).
	 * 
	 * @return  mixed               The value of the field from the saved session.
	 */
	public function get_checkout_field_value_from_session( $field_key ) {
		// Bail if WC or session not available yet
		if ( ! function_exists( 'wC' ) || ! isset( WC()->session ) ) { return; }

		return WC()->session->get( self::SESSION_PREFIX . $field_key );
	}

	/**
	 * Set values for a checkout field to the session.
	 *
	 * @param   string  $field_key  Checkout field key name (ie. order_comments ).
	 * @param   mixed   $value      Value of the field.
	 * 
	 * @return  mixed               The value of the field from the saved session.
	 */
	public function set_checkout_field_value_to_session( $field_key, $value ) {
		// Bail if WC or session not available yet
		if ( ! function_exists( 'wC' ) || ! isset( WC()->session ) ) { return; }

		return WC()->session->set( self::SESSION_PREFIX . $field_key, $value );
	}

	/**
	 * Clear session values for checkout fields when the order is processed.
	 **/
	public function unset_session_customer_persisted_data_order_processed() {
		$clear_field_keys = array(
			'account_username',
			'account_password',
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
