<?php
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
	 *      ['next_step_button_label']       string      The label for the "Next step" button. Accepts `<span>` and `<i>` elements for extra styling. Defaults to "Next Step".
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

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_order_details_styles' ), 10 );

		// Checkout Header
		add_action( 'wfc_checkout_header', array( $this, 'output_checkout_header' ), 1 );
		add_action( 'wfc_checkout_header_cart_link', array( $this, 'output_checkout_header_cart_link' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_header_cart_link_fragment' ), 10 );
		add_filter( 'wfc_content_section_class', array( $this, 'wfc_content_section_class' ), 10 );
		
		// Notices
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_checkout_notices_wrapper_start_tag' ), 5 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'output_checkout_notices_wrapper_end_tag' ), 100 );

		// Checkout steps
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_checkout_progress_bar' ), 1 );
		add_action( 'wp', array( $this, 'register_default_checkout_steps' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_checkout_steps' ), 10 );
		add_action( 'wfc_checkout_after', array( $this, 'output_checkout_sidebar_wrapper' ), 10 );
		
		// Contact
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
		add_action( 'wfc_output_step_contact', array( $this, 'output_substep_contact_login' ), 10 );
		add_action( 'wfc_output_step_contact', array( $this, 'output_substep_contact' ), 20 );
		add_action( 'wp_footer', array( $this, 'output_login_form_flyout' ), 10 );
		add_action( 'woocommerce_login_form_end', array( $this, 'output_woocommerce_login_form_redirect_hidden_field'), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_contact_text_fragment' ), 10 );

		// Account creation
		add_action( 'wfc_checkout_after_contact_fields', array( $this, 'output_form_account_creation' ), 10 );

		// Shipping
		add_filter( 'option_woocommerce_ship_to_destination', array( $this, 'change_woocommerce_ship_to_destination' ), 100, 2 );
		add_action( 'wfc_output_step_shipping', array( $this, 'output_substep_shipping_address' ), 10 );
		add_action( 'wfc_output_step_shipping', array( $this, 'output_substep_shipping_method' ), 20 );
		add_action( 'wfc_output_step_shipping', array( $this, 'output_substep_order_notes' ), 100 );
		add_action( 'wfc_cart_totals_shipping', array( $this, 'output_cart_totals_shipping_section' ), 10 );
		add_action( 'wfc_before_checkout_shipping_address_wrapper', array( $this, 'output_ship_to_different_address_hidden_field' ), 10 );
		add_filter( 'woocommerce_ship_to_different_address_checked', array( $this, 'set_ship_to_different_address_true' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_methods_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_methods_text_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_shipping_address_text_fragment' ), 10 );
		
		// Order Notes
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'set_order_notes_session' ), 10 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'unset_order_notes_session' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_order_notes_text_fragment' ), 10 );
		
		// Billing Address
		add_action( 'wfc_output_step_billing', array( $this, 'output_substep_billing_address' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_billing_address_fields_fragment' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_billing_address_text_fragment' ), 10 );

		// Billing Same as Shipping
		add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'output_billing_same_as_shipping_field' ), 100 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'maybe_set_billing_address_same_as_shipping' ), 10 );
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'maybe_set_billing_address_same_as_shipping_on_process_checkout' ), 10 );

		// Billing Company
		add_action( 'wfc_output_step_billing', array( $this, 'output_substep_billing_company' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_billing_company_text_fragment' ), 10 );

		// Payment
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_checkout_payment', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_output_step_payment', array( $this, 'output_substep_payment' ), 80 );
		add_action( 'wfc_output_step_payment', array( $this, 'output_order_review' ), 90 );
		add_action( 'wfc_output_step_payment', array( $this, 'output_checkout_place_order' ), 100, 2 );
		add_action( 'wfc_checkout_after_order_review_inside', array( $this, 'output_checkout_place_order_for_sidebar' ), 1 );
		add_action( 'woocommerce_order_button_html', array( $this, 'add_place_order_button_wrapper' ), 10 );
		add_action( 'woocommerce_gateway_icon', array( $this, 'change_payment_gateway_icon_html' ), 10, 2 );
		
		// Order Review
		add_action( 'wfc_checkout_order_review_section', array( $this, 'output_order_review_for_sidebar' ), 10 );
		add_action( 'wfc_review_order_shipping', array( $this, 'maybe_output_order_review_shipping_method_chosen' ), 30 );

		// Persisted data
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'update_customer_persisted_data' ), 10 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'set_order_comments_session' ), 10 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'unset_order_comments_session' ), 10 );
		add_filter( 'default_checkout_order_comments', array( $this, 'change_default_order_comments_value' ), 10, 2 );
	}



	/**
	 * Add page body class for feature detection.
	 *
		 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		$add_classes = array( 'has-checkout-layout--' . $this->get_checkout_layout() );
		
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
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() ){ return; }

		// Styles
		wp_enqueue_style( 'wfc-checkout-layout', self::$directory_url . 'css/checkout-layout'. self::$asset_version . '.css', NULL, NULL );
		
		// Multi-step Layout scripts
		if ( $this->is_checkout_layout_multistep() ) {
			wp_enqueue_script( 'wfc-checkout-steps', self::$directory_url . 'js/checkout-steps'. self::$asset_version . '.js', array( 'jquery', 'wc-checkout' ), NULL, true );
			wp_add_inline_script( 'wfc-checkout-steps', 'window.addEventListener("load",function(){CheckoutSteps.init();})' );
		}
	}



	/**
	 * Enqueue scripts.
	 */
	public function enqueue_order_details_styles() {
		// Bail if not on order details pages
		if ( ! is_order_received_page() && ! is_wc_endpoint_url( 'view-order' ) ) { return; }

		wp_enqueue_style( 'wfc-order-details', self::$directory_url . 'css/order-details'. self::$asset_version . '.css', NULL, NULL );
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

		return ( ! ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) ) && 'yes' === get_option( 'wfc_hide_site_header_footer_at_checkout', 'yes' );
	}



	/**
	 * Return the list of values accepted for checkout layout.
	 * 
	 * @return  array  List of values accepted for checkout layout.
	 */
	public function get_allowed_checkout_layouts() {
		return apply_filters( 'wfc_allowed_checkout_layouts', array(
			'multi-step' => __( 'Multi-step', 'woocommerce-fluid-checkout' ),
			'one-page' => __( 'One page', 'woocommerce-fluid-checkout' ),
		) );
	}



	/**
	 * Get the current checkout layout value.
	 *
	 * @return  string  The name of the currently selected checkout layout option. Defaults to `multi-step`.
	 */
	public function get_checkout_layout() {
		$allowed_values = array_keys( $this->get_allowed_checkout_layouts() );
		$current_value = get_option( 'wfc_checkout_layout' );
		$default_value = 'multi-step';

		// Set layout to default value if value not set or not allowed
		if ( ! in_array( $current_value, $allowed_values ) ) {
			$current_value = $default_value;
		}

		return apply_filters( 'wfc_get_checkout_layout', $current_value );
	}

	/**
	 * Check if the current checkout layout is set to `multi-step`.
	 *
	 * @return  boolean  `true` if the current checkout layout option value is set to `multi-step`, `false` otherwise.
	 */
	public function is_checkout_layout_multistep() {
		return apply_filters( 'wfc_is_checkout_layout_multistep', $this->get_checkout_layout() === 'multi-step' );
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
			'wfc/checkout/checkout-header.php',
			array( 'checkout' => WC()->checkout() )
		);
	}

	/**
	 * Output the checkout header.
	 */
	public function output_checkout_notices_wrapper_start_tag() {
		?>
		<div class="wfc-checkout-notices">
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
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wfc-checkout__cart-link" data-flyout-toggle data-flyout-target="[data-flyout-order-review]"><?php echo $link_label_html; ?></a>
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
		$fragments['.wfc-checkout__cart-link'] = $html;
		return $fragments;
	}

	/**
	 * Output a redirect hidden field to the WooCommerce login form to redirect the user to the checkout or previous page.
	 */
	public function output_woocommerce_login_form_redirect_hidden_field() {
		$raw_referrer_url = wc_get_raw_referer() ? wc_get_raw_referer() : wc_get_page_permalink( 'myaccount' );
		$referrer_url = ( ( function_exists( 'is_checkout' ) && is_checkout() ) || $_GET[ '_redirect' ] == 'checkout' ) ? wc_get_checkout_url() : $raw_referrer_url;

		echo '<input type="hidden" name="redirect" value="' . wp_validate_redirect( $referrer_url, wc_get_page_permalink( 'myaccount' ) ) . '" />';
	}


	
	/**
	 * Add container class to the main content element.
	 * 
	 * @param string $class Main content element classes.
	 */
	public function wfc_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( $this->get_hide_site_header_footer_at_checkout() ) { return $class; }

		return $class . ' wfc-container';
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
			
			// Add incomplete steps to the list
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
			if ( $step_id == $step_args[ 'step_id' ] ) {
				$next_step_index = $step_index + 1;
				if ( array_key_exists( $next_step_index, $complete_steps ) ) {
					return true;
				}
			}
		}

		return false;
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
		$step_args = apply_filters( 'wfc_register_checkout_step_args', $step_args );
		
		// Sanitize step id
		$step_args[ 'step_id' ] = sanitize_title( $step_args[ 'step_id' ] );
		$step_id = $step_args[ 'step_id' ];

		// Sanitize value for `render_next_step_button` flag and set default value if needed
		$step_args[ 'render_next_step_button' ] = array_key_exists( 'render_next_step_button', $step_args ) && $step_args[ 'render_next_step_button' ] === false ? false : true;

		// Sanitize "next step" button label, or set default value
		$step_args[ 'next_step_button_label' ] = array_key_exists( 'next_step_button_label', $step_args ) && $step_args[ 'next_step_button_label' ] && $step_args[ 'next_step_button_label' ] != '' ? $step_args[ 'next_step_button_label' ] : __( 'Next step', 'woocommerce-fluid-checkout' );
		$step_args[ 'next_step_button_label' ] = wp_kses( $step_args[ 'next_step_button_label' ], array( 'span' => array( 'class' => '' ), 'i' => array( 'class' => '' ) ) );

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

		// Update registered checkout steps
		$this->checkout_steps = $_checkout_steps;

		return true;
	}



	/**
	 * Register the default checkout steps supported by this plugin.
	 */
	public function register_default_checkout_steps() {
		// Bail if not on checkout or cart page
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() ) ) { return; }

		// CONTACT
		$this->register_checkout_step( array(
			'step_id' => 'contact',
			'step_title' => _x( 'Contact', 'Checkout step title', 'woocommerce-fluid-checkout' ),
			'priority' => 10,
			'render_callback' => array( $this, 'output_step_contact' ),
			'is_complete_callback' => array( $this, 'is_step_complete_contact' ),
			'next_step_button_label' => WC()->cart->needs_shipping() ? __( 'Proceed to Shipping', 'woocommerce-fluid-checkout' ) : __( 'Proceed to Billing', 'woocommerce-fluid-checkout' ),
		) );

		// SHIPPING
		if ( WC()->cart->needs_shipping() ) {
			$this->register_checkout_step( array(
				'step_id' => 'shipping',
				'step_title' => _x( 'Shipping', 'Checkout step title', 'woocommerce-fluid-checkout' ),
				'priority' => 20,
				'render_callback' => array( $this, 'output_step_shipping' ),
				'render_condition_callback' => array( WC()->cart, 'needs_shipping' ),
				'is_complete_callback' => array( $this, 'is_step_complete_shipping' ),
				'next_step_button_label' => __( 'Proceed to Billing', 'woocommerce-fluid-checkout' ),
			) );
		}

		// BILLING
		$this->register_checkout_step( array(
			'step_id' => 'billing',
			'step_title' => _x( 'Billing', 'Checkout step title', 'woocommerce-fluid-checkout' ),
			'priority' => 30,
			'render_callback' => array( $this, 'output_step_billing' ),
			'is_complete_callback' => array( $this, 'is_step_complete_billing' ),
			'next_step_button_label' => __( 'Proceed to Payment', 'woocommerce-fluid-checkout' ),
		) );

		// PAYMENT
		$this->register_checkout_step( array(
			'step_id' => 'payment',
			'step_title' => _x( 'Payment', 'Checkout step title', 'woocommerce-fluid-checkout' ),
			'priority' => 100,
			'render_callback' => array( $this, 'output_step_payment' ),
			'is_complete_callback' => '__return_false', // Payment step is only complete when the order has been placed and the payment has been accepted, during the checkout process it will always be considered 'incomplete'.
			'render_next_step_button' => false,
		) );

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
		
		// Get step count html
		$steps_count_label_html = apply_filters(
			'wfc_steps_count_html',
			sprintf(
				/* translators: %1$s is replaced with html for "current checkout step number", %2$s is replaced with html for "total number of checkout steps". */
				esc_html( __( 'Step %1$s of %2$s', 'woocommerce-fluid-checkout' ) ),
				'<span class="wfc-progress-bar__current-step" data-step-count-current>' . esc_html( $current_step_index + 1 ) . '</span>',
				'<span class="wfc-progress-bar__total-steps" data-step-count-total>' . esc_html( $steps_count ) . '</span>'
			),
			$_checkout_steps,
			$current_step
		);

		// Attributes
		$progress_bar_attributes = array(
			'class' => 'wfc-progress-bar',
			'data-progress-bar' => true,

		);
		$progress_bar_inner_attributes = array(
			'class' => 'wfc-progress-bar__inner',
		);

		// Sticky state attributes
		if ( get_option( 'wfc_enable_checkout_sticky_progress_bar', 'yes' ) === 'yes' ) {
			$progress_bar_attributes = array_merge( $progress_bar_attributes, array(
				'data-sticky-states' => true,
				'data-sticky-relative-to' => '.wfc-checkout-header',
				'data-sticky-container' => 'div.woocommerce',
			) );

			$progress_bar_inner_attributes = array_merge( $progress_bar_inner_attributes, array(
				'data-sticky-states-inner' => true,
			) );
		}

		$progress_bar_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $progress_bar_attributes ), $progress_bar_attributes ) );
		$progress_bar_attributes_inner_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $progress_bar_inner_attributes ), $progress_bar_inner_attributes ) );
		?>
		<div <?php echo $progress_bar_attributes_str; ?>>
			<div <?php echo $progress_bar_attributes_inner_str; ?>>

				<div class="wfc-progress-bar__count" data-step-count-text><?php echo $steps_count_label_html ?></div>
				<div class="wfc-progress-bar__bars" data-progress-bar data-step-count="<?php echo esc_attr( $steps_count ); ?>">
					<?php
					foreach ( $_checkout_steps as $step_index => $step_args ) :
						$step_bar_class = $step_index < $current_step_index ? 'is-complete' : ( $step_index == $current_step_index ? 'is-current' : '' );
						?>
						<span class="wfc-progress-bar__bar <?php echo esc_attr( $step_bar_class ); ?>" data-step-id="<?php echo esc_attr( $step_args[ 'step_id' ] ); ?>" data-step-index="<?php echo esc_attr( $step_index ); ?>"></span>
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
		$step_title = apply_filters( "wfc_step_title_{$step_id}", $step_args[ 'step_title' ] );
		
		$step_attributes = array(
			'class' => 'wfc-checkout-step',
			'data-step-id' => ! empty( $step_id ) && $step_id != null ? $step_id : '',
			'data-step-label' => $step_title,
			'data-step-index' => $step_index,
			'data-step-complete' => $this->is_step_complete( $step_id ),
			'data-step-current' => $this->is_current_step( $step_id ),
		);
		
		// Maybe add class for previous step completed
		if ( $this->is_prev_step_complete( $step_id ) ) {
			$step_attributes['class'] .= ' wfc-checkout-step--prev-step-complete';
		}
		
		// Maybe add class for next step completed
		if ( $this->is_next_step_complete( $step_id ) ) {
			$step_attributes['class'] .= ' wfc-checkout-step--next-step-complete';
		}
		else {
			$step_attributes['class'] .= ' wfc-checkout-step--next-step-incomplete';
		}
		
		$step_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $step_attributes ), $step_attributes ) );
		echo '<section ' . $step_attributes_str . '>';
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
			$button_attributes = array(
				'class' => implode( ' ', array_merge( array( 'wfc-step__next-step', 'button' ), $step_args[ 'next_step_button_classes' ] ) ),
				'data-step-next' => '',
			);
			$button_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $button_attributes ), $button_attributes ) );
			?>
			<div class="wfc-step__actions">
				<button type="button" <?php echo $button_attributes_str; ?>><?php echo $step_args[ 'next_step_button_label' ]; ?></button>
			</div>
			<?php
		endif;

		echo '</section>';
	}



	/**
	 * Output checkout substep start tag.
	 *
	 * @param   string  $step_id        Id of the step in which the substep will be rendered.
	 * @param   string  $substep_id     Id of the substep.
	 * @param   string  $substep_title  Title of the substep.
	 */
	public function output_substep_start_tag( $step_id, $substep_id, $substep_title ) {
		$substep_title = apply_filters( "wfc_substep_title_{$substep_id}", $substep_title );
		$substep_attributes = array(
			'class' => 'wfc-step__substep',
			'data-substep-id' => ! empty( $substep_id ) && $substep_id != null ? $substep_id : '',
		);
		$substep_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_attributes ), $substep_attributes ) );
		?>
		<div <?php echo $substep_attributes_str; ?>>
			<?php if ( ! empty( $substep_title ) ) : ?>
				<h3 class="wfc-step__substep-title"><?php echo wp_kses( $substep_title, array( 'span' => array( 'class' => array() ), 'i' => array( 'class' => array() ) ) ); ?></h3>
			<?php endif; ?>
		<?php
	}

	/**
	 * Output checkout substep end tag.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 * @param   string  $substep_id  Id of the substep.
	 */
	public function output_substep_end_tag( $step_id, $substep_id, $output_edit_buttons = true ) {
		?>
			<?php if ( $output_edit_buttons && $this->is_checkout_layout_multistep() ) : ?>
				<a tabindex="0" role="button" class="wfc-step__substep-edit" data-step-edit aria-controls="wfc-substep__<?php echo esc_attr( $substep_id ); ?>"><?php echo _x( 'Change', 'Checkout substep change link label', 'woocommerce-fluid-checkout' ); ?></a>
				<a tabindex="0" role="button" class="wfc-step__substep-save" data-step-save aria-controls="wfc-substep__<?php echo esc_attr( $substep_id ); ?>"><?php echo _x( 'Save', 'Checkout substep save link label', 'woocommerce-fluid-checkout' ); ?></a>
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
			'id' => 'wfc-substep__fields--' . $substep_id,
			'class' => 'wfc-step__substep-fields wfc-substep__fields--' . $substep_id,
			'data-substep-id' => $substep_id,
		);
		
		$substep_inner_attributes = array(
			'class' => 'wfc-step__substep-fields-inner',
		);
		
		// Add collapsible-block attributes for multistep layout
		if ( $collapsible && $this->is_checkout_layout_multistep() ) {
			$is_step_complete = $this->is_step_complete( $step_id );

			$substep_attributes = array_merge( $substep_attributes, array(
				'data-collapsible' => true,
				'data-collapsible-content' => true,
				'data-collapsible-initial-state' => $is_step_complete ? 'collapsed' : 'expanded',
			) );

			$substep_inner_attributes = array(
				'class' => $substep_inner_attributes[ 'class' ] . ' collapsible-content__inner',
			);
		}

		$substep_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_attributes ), $substep_attributes ) );
		$substep_inner_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_inner_attributes ), $substep_inner_attributes ) );
		?>
		<div <?php echo $substep_attributes_str; ?>>
			<div <?php echo $substep_inner_attributes_str; ?>>
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
			'id' => 'wfc-substep__text--' . $substep_id,
			'class' => 'wfc-step__substep-text',
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
		<div <?php echo $substep_attributes_str; ?>>
			<div <?php echo $substep_inner_attributes_str; ?>>
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
		// Initial state
		$initial_state = array_key_exists( 'initial_state', $args ) && $args['initial_state'] === 'expanded' ? 'expanded' : 'collapsed';
		
		// Section attributes
		$section_attributes = array( 'class' => 'wfc-expansible-form-section' );

		// Merge section attributes
		if ( array_key_exists( 'section_attributes', $args ) && is_array( $args['section_attributes'] ) ) {
			$section_class = $section_attributes['class'];

			$section_attributes = array_merge( $section_attributes, $args['section_attributes'] );

			// Merge class attribute
			$section_attributes['class'] = array_key_exists( 'class', $args['section_attributes'] ) ? $section_class . ' ' . $args['section_attributes']['class'] : $section_class;
		}
		
		// Section toggle attributes
		$section_toggle_attributes = array(
			'id' => 'wfc-expansible-form-section__toggle--' . $section_id,
			'class' => 'wfc-expansible-form-section__toggle wfc-expansible-form-section__toggle--' . $section_id . ' ' . ( $initial_state === 'expanded' ? 'is-collapsed' : 'is-expanded' ), // Toggle is collapsed when the section is set to expanded
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
			'href' => '#wfc-expansible-form-section__content--' . $section_id,
			'class' => 'expansible-section__toggle-plus expansible-section__toggle-plus--' . $section_id,
			'data-collapsible-handler' => true,
			'data-collapsible-targets' => implode( ',', array(
				'wfc-expansible-form-section__toggle--' . $section_id,
				'wfc-expansible-form-section__content--' . $section_id,
			) ),
		);

		// Section content attributes
		$section_content_attributes = array(
			'id' => 'wfc-expansible-form-section__content--' . $section_id,
			'class' => 'wfc-expansible-form-section__content wfc-expansible-form-section__content--' . $section_id . ' ' . ( $initial_state === 'expanded' ? 'is-expanded' : 'is-collapsed' ),
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
		<div <?php echo $section_attributes_str; ?>>
			<div <?php echo $section_toggle_attributes_str; ?>>
				<div <?php echo $section_content_inner_attributes_str; ?>>
					<a <?php echo $toggle_attributes_str; ?>>
						<?php echo esc_html( $toggle_label ); ?>
					</a>
				</div>
			</div>

			<div <?php echo $section_content_attributes_str; ?>>
				<div <?php echo $section_content_inner_attributes_str; ?>>
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
		do_action( 'wfc_output_step_contact', 'contact' );
	}

	/**
	 * Output contact substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_contact( $step_id ) {
		$substep_id = 'contact';
		$this->output_substep_start_tag( $step_id, $substep_id, __( 'My contact', 'woocommerce-fluid-checkout' ) );
		
		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_step_contact_fields();
		$this->output_substep_fields_end_tag();
		
		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_contact();
			$this->output_substep_text_end_tag();
		}
			
		$this->output_substep_end_tag( $step_id, $substep_id );
	}

	/**
	 * Output contact step fields.
	 */
	public function output_step_contact_fields() {
		do_action( 'woocommerce_checkout_before_customer_details' );
		
		wc_get_template(
			'wfc/checkout/form-contact.php',
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
		$html = '<div class="wfc-step__substep-text-content wfc-step__substep-text-content--contact">';
		$html .= '<span class="wfc-step__substep-text-line">' . $customer->get_billing_email() . '</span>';
		$html .= '<span class="wfc-step__substep-text-line">' . $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name() . '</span>';
		$html .= '<span class="wfc-step__substep-text-line">' . $customer->get_billing_phone() . '</span>';
		
		// Maybe add notice for account creation
		if ( get_option( 'wfc_show_account_creation_notice_checkout_contact_step_text', 'true' ) === 'true' ) {
			$parsed_posted_data = $this->get_parsed_posted_data();
			if ( array_key_exists( 'createaccount', $parsed_posted_data ) && $parsed_posted_data[ 'createaccount' ] == '1' ) {
				$html .= '<span class="wfc-step__substep-text-line"><em>' . __( 'An account will be created with the information provided.', 'woocommerce-fluid-checkout' ) . '</em></span>';
			}
		}
		
		$html .= '</div>';

		return apply_filters( 'wfc_substep_contact_text', $html );
	}

	/**
	 * Add Contact text format as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_contact_text_fragment( $fragments ) {
		$html = $this->get_substep_text_contact();
		$fragments['.wfc-step__substep-text-content--contact'] = $html;
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

		return apply_filters( 'wfc_is_step_complete_contact', $is_step_complete );
	}



	/**
	 * Output account creation form fields.
	 */
	public function output_form_account_creation() {
		wc_get_template(
			'wfc/checkout/form-account-creation.php',
			array(
				'checkout'			=> WC()->checkout(),
			)
		);
	}



	/**
	 * Return list of checkout fields for contact step.
	 */
	public function get_contact_step_display_field_ids() {
		return apply_filters( 'wfc_checkout_contact_step_field_ids', array(
			'billing_email',
		) );
	}

	

	/**
	 * Output the login form flyout block for the checkout page.
	 */
	public function output_login_form_flyout() {
		// Bail if user already logged in or login at checkout is disabled
		if ( is_user_logged_in() || 'yes' !== get_option( 'woocommerce_enable_checkout_login_reminder' ) ) { return; };
		
		wc_get_template(
			'wfc/checkout/form-contact-login-modal.php',
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
			'wfc/checkout/form-contact-login.php',
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
			
		$this->output_substep_end_tag( $step_id, $substep_id, false );
	}





	/**
	 * Checkout Step: Shipping.
	 */



	/**
	 * Output shipping step.
	 */
	public function output_step_shipping() {
		do_action( 'wfc_output_step_shipping', 'shipping' );
	}

	/**
	 * Output shipping address substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_shipping_address( $step_id ) {
		$substep_id = 'shipping_address';
		$this->output_substep_start_tag( $step_id, $substep_id, __( 'Shipping to', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_shipping_address_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_shipping_address();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id );
	}

	/**
	 * Output shipping method substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_shipping_method( $step_id ) {
		$substep_id = 'shipping_method';
		$this->output_substep_start_tag( $step_id, $substep_id, __( 'Shipping Method', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_shipping_methods_available();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_shipping_method();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id );
	}

	/**
	 * Output order notes substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_order_notes( $step_id ) {
		$substep_id = 'order_notes';
		$this->output_substep_start_tag( $step_id, $substep_id, __( 'Additional notes', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_additional_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_order_notes();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id );
	}



	/**
	 * Output shipping step fields.
	 */
	public function output_substep_shipping_address_fields() {
		wc_get_template(
			'checkout/form-shipping.php',
			array(
				'checkout'          => WC()->checkout(),
			)
		);
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
		
		$html = '<div class="wfc-step__substep-text-content wfc-step__substep-text-content--shipping-address">';
		$html .= '<span class="wfc-step__substep-text-line">' . WC()->countries->get_formatted_address( $address_data ) . '</span>';
		$html .= '</div>';

		return apply_filters( 'wfc_substep_shipping_address_text', $html );
	}

	/**
	 * Add shipping address text as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_address_text_fragment( $fragments ) {
		$html = $this->get_substep_text_shipping_address();
		$fragments['.wfc-step__substep-text-content--shipping-address'] = $html;
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
		
		$html = '<div class="wfc-step__substep-text-content wfc-step__substep-text-content--shipping-method">';

		foreach ( $packages as $i => $package ) {
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;
			$chosen_method_label = wc_cart_totals_shipping_method_label( $method );
			
			// TODO: Maybe handle multiple packages
			// $package_name = apply_filters( 'woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'woocommerce' ), ( $i + 1 ) ) : _x( 'Shipping', 'shipping packages', 'woocommerce' ), $i, $package );

			$html .= '<span class="wfc-step__substep-text-line">' . $chosen_method_label . '</span>';
		}
		
		$html .= '</div>';

		return apply_filters( 'wfc_substep_shipping_methods_text', $html );
	}

	/**
	 * Add shipping methods text format as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_methods_text_fragment( $fragments ) {
		$html = $this->get_substep_text_shipping_method();
		$fragments['.wfc-step__substep-text-content--shipping-method'] = $html;
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
		$order_notes = $this->get_order_notes_session();

		$html = '<div class="wfc-step__substep-text-content wfc-step__substep-text-content--order-notes">';

		// The order notes value
		if ( ! empty( $order_notes ) ) {
			$html .= '<span class="wfc-step__substep-text-line">' . esc_html( $order_notes ) . '</span>';
		}
		// "No order notes" notice.
		else {
			$html .= '<span class="wfc-step__substep-text-line">' . apply_filters( 'wfc_no_order_notes_order_review_notice', _x( 'None.', 'Notice for no order notes provided', 'woocommerce-fluid-checkout' ) ) . '</span>';
		}
		
		$html .= '</div>';

		return apply_filters( 'wfc_substep_order_notes_text', $html );
	}

	/**
	 * Add order notes text format as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_order_notes_text_fragment( $fragments ) {
		$html = $this->get_substep_text_order_notes();
		$fragments['.wfc-step__substep-text-content--order-notes'] = $html;
		return $fragments;
	}

	/**
	 * Output order notes substep in text format for when the step is completed.
	 */
	public function output_substep_text_order_notes() {
		echo $this->get_substep_text_order_notes();
	}



	/**
	 * Get order notes value from session.
	 *
	 * @return  string  The order notes field value saved to session.
	 */
	public function get_order_notes_session() {
		$order_notes = WC()->session->get( '_wfc_order_notes' ) !== null ? WC()->session->get( '_wfc_order_notes' ) : '';
		return $order_notes;
	}

	/**
	 * Save the order notes fields values to the current user session.
	 * 
	 * @param array $posted_data Post data for all checkout fields.
	 */
	public function set_order_notes_session( $posted_data ) {
		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();

		// Set session value
		WC()->session->set( '_wfc_order_notes', $parsed_posted_data['order_comments'] );
		
		return $posted_data;
	}

	/**
	 * Unset order notes session.
	 **/
	public function unset_order_notes_session() {
		WC()->session->set( '_wfc_order_notes', null );
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
		$fields = $checkout->get_checkout_fields( 'shipping' );
		foreach ( $fields as $field_key => $field ) {
			if ( array_key_exists( 'required', $field ) && $field[ 'required' ] === true && ! $checkout->get_value( $field_key ) ) {
				$is_step_complete = false;
				break;
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

		return apply_filters( 'wfc_is_step_complete_shipping', $is_step_complete );
	}



	/**
	 * Return html for shipping step actions
	 */
	public function get_shipping_step_actions_html() {
		$actions_html = '<div class="wfc-actions"><button class="wfc-prev">' . _x( 'Back', 'Previous step button', 'woocommerce-fluid-checkout' ) . '</button> <button class="wfc-next button alt">' . __( 'Proceed to Payment', 'woocommerce-fluid-checkout' ) . '</button></div>';
		return apply_filters( 'wfc_shipping_step_actions_html', $actions_html );
	}



	/**
	 * Output shipping section for cart totals.
	 */
	public function output_cart_totals_shipping_section() {
		wc_get_template(
			'wfc/cart/cart-totals-shipping.php'
		);
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
		
		do_action( 'wfc_shipping_methods_before_packages' );
		
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
	
			wc_get_template( 'wfc/cart/shipping-methods-available.php', array(
				'package'					=> $package,
				'available_methods'			=> $package['rates'],
				'show_package_details'		=> sizeof( $packages ) > 1,
				'show_shipping_calculator'	=> is_cart() && $first_item,
				'package_details'			=> implode( ', ', $product_names ),
				// @codingStandardsIgnoreStart
				'package_name'				=> apply_filters( 'woocommerce_shipping_package_name', sprintf( _nx( 'Shipping', 'Shipping %d', ( $i + 1 ), 'shipping packages', 'woocommerce' ), ( $i + 1 ) ), $i, $package ),
				// @codingStandardsIgnoreEnd
				'package_index'				=> $i,
				'chosen_method'				=> $chosen_method,
			) );
	
			$first_item = false;
		}

		do_action( 'wfc_shipping_methods_after_packages' );

		return ob_get_clean();
	}

	/**
	 * Add shipping methods available as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_shipping_methods_fragment( $fragments ) {
		$html = $this->get_shipping_methods_available();
		$fragments['.shipping-method__packages'] = $html;
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
		$label     = sprintf( apply_filters( 'wfc_shipping_method_option_label_markup', '<span class="shipping-method__option-text">%s</span>' ), $method->get_label() );
		$has_cost  = 0 < $method->cost;
		$hide_cost = ! $has_cost && in_array( $method->get_method_id(), array( 'free_shipping', 'local_pickup' ), true );
		
		if ( $has_cost && ! $hide_cost ) {
			
			if ( WC()->cart->display_prices_including_tax() ) {

				$method_costs = wc_price( $method->cost + $method->get_shipping_tax() );
				if ( $method->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
					$method_costs .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}

				$label .= sprintf( apply_filters( 'wfc_shipping_method_option_price_markup', ' <span class="shipping-method__option-price">%s</span>' ), $method_costs );

			} else {
				
				$method_costs = wc_price( $method->cost + $method->get_shipping_tax() );
				if ( $method->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
					$method_costs .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}

				$label .= sprintf( apply_filters( 'wfc_shipping_method_option_price_markup', ' <span class="shipping-method__option-price">%s</span>' ), $method_costs );

			}
		}

		return $label;
	}



	/**
	 * Output step: Additional Information fields.
	 */
	public function output_additional_fields() {
		wc_get_template(
			'wfc/checkout/form-additional-fields.php',
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
		do_action( 'wfc_output_step_billing', 'billing' );
	}
	


	/**
	 * Output billing address substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_billing_address( $step_id ) {
		$substep_id = 'billing_address';
		$this->output_substep_start_tag( $step_id, $substep_id, __( 'Billing Address', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_billing_address_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_billing_address();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id );
	}



	/**
	 * Get list of billing fields ignored at the billing address substep as they were moved to another substep.
	 *
	 * @return  array  List of billing fields to ignore at billing address substep.
	 */
	public function get_billing_address_ignored_billing_field_ids() {
		$billing_ignored_field_ids = array_merge( $this->get_contact_step_display_field_ids(), $this->get_billing_company_substep_display_field_ids() );
		return $billing_ignored_field_ids;
	}



	/**
	 * Output billing address fields, except those already added at the contact step.
	 */
	public function output_substep_billing_address_fields() {

		do_action( 'wfc_checkout_before_step_billing_fields' );

		wc_get_template(
			'checkout/form-billing.php',
			array(
				'checkout'			          => WC()->checkout(),
				'ignore_fields'		          => $this->get_billing_address_ignored_billing_field_ids(),
				'is_billing_same_as_shipping' => $this->is_billing_same_as_shipping(),
			)
		);

		do_action( 'wfc_checkout_after_step_billing_fields' );
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

		$address_data = array(
			'address_1' => $customer->get_billing_address_1(),
			'address_2' => $customer->get_billing_address_2(),
			'city' => $customer->get_billing_city(),
			'state' => $customer->get_billing_state(),
			'country' => $customer->get_billing_country(),
			'postcode' => $customer->get_billing_postcode(),
		);
		
		$html = '<div class="wfc-step__substep-text-content wfc-step__substep-text-content--billing-address">';
		$html .= '<span class="wfc-step__substep-text-line">' . WC()->countries->get_formatted_address( $address_data ) . '</span>';
		$html .= '</div>';

		return apply_filters( 'wfc_substep_billing_address_text', $html );
	}

	/**
	 * Add billing address text format as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_billing_address_text_fragment( $fragments ) {
		$html = $this->get_substep_text_billing_address();
		$fragments['.wfc-step__substep-text-content--billing-address'] = $html;
		return $fragments;
	}

	/**
	 * Output billing address substep in text format for when the step is completed.
	 */
	public function output_substep_text_billing_address() {
		echo $this->get_substep_text_billing_address();
	}



	/**
	 * Return list of checkout fields for billing company substep.
	 */
	public function get_billing_company_substep_display_field_ids() {
		return apply_filters( 'wfc_checkout_billing_company_substep_field_ids', array(
			'billing_company',
		) );
	}



	/**
	 * Output billing company substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_billing_company( $step_id ) {
		// Get list of company fields present
		$fields = WC()->checkout()->get_checkout_fields( 'billing' );
		$existing_company_field_ids = array_intersect( array_keys( $fields ), $this->get_billing_company_substep_display_field_ids() );
		
		// Bail if company fields not present
		if ( ! is_array( $existing_company_field_ids ) || count( $existing_company_field_ids ) === 0 ) { return; }
		
		$substep_id = 'billing_company';
		$this->output_substep_start_tag( $step_id, $substep_id, __( 'Billing Company', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_billing_company_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $step_id, $substep_id );
			$this->output_substep_text_billing_company();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag( $step_id, $substep_id );
	}



	/**
	 * Output billing company fields, except those already added at the contact step.
	 */
	public function output_substep_billing_company_fields() {
		wc_get_template(
			'wfc/checkout/form-billing-company.php',
			array(
				'checkout'			=> WC()->checkout(),
				'display_fields'	=> $this->get_billing_company_substep_display_field_ids(),
			)
		);
	}

	/**
	 * Get billing company fields, except those already added at the contact step.
	 */
	public function get_substep_billing_company_fields() {
		ob_start();
		$this->output_substep_billing_company_fields();
		return ob_get_clean();
	}



	/**
	 * Get billing company substep in text format for when the step is completed.
	 */
	public function get_substep_text_billing_company() {
		$customer = WC()->customer;
		$billing_company = $customer->get_billing_company();
		
		$html = '<div class="wfc-step__substep-text-content wfc-step__substep-text-content--billing-company">';

		// The billing company value
		if ( ! empty( $billing_company ) ) {
			$html .= '<span class="wfc-step__substep-text-line">' . esc_html( $billing_company ) . '</span>';
		}
		// "No billing company" notice.
		else {
			$html .= '<span class="wfc-step__substep-text-line">' . apply_filters( 'wfc_no_billing_company_order_review_notice', _x( 'None.', 'Notice for no billing company provided', 'woocommerce-fluid-checkout' ) ) . '</span>';
		}
		
		$html .= '</div>';

		return apply_filters( 'wfc_substep_billing_company_text', $html );
	}

	/**
	 * Add billing company text format as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_billing_company_text_fragment( $fragments ) {
		$html = $this->get_substep_text_billing_company();
		$fragments['.wfc-step__substep-text-content--billing-company'] = $html;
		return $fragments;
	}

	/**
	 * Output billing company substep in text format for when the step is completed.
	 */
	public function output_substep_text_billing_company() {
		echo $this->get_substep_text_billing_company();
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

		return apply_filters( 'wfc_is_step_complete_billing', $is_step_complete );
	}



	/**
	 * Output field for billing address same as shipping.
	 */
	public function output_billing_same_as_shipping_field() {
		// Output a hidden field when shipping country not allowed for billing
		if ( $this->is_shipping_country_allowed_for_billing() === null || ! $this->is_shipping_country_allowed_for_billing() ) : ?>
			<input type="hidden" name="billing_same_as_shipping" id="billing_same_as_shipping" value="<?php echo $this->is_billing_same_as_shipping_checked() ? '1' : '0'; ?>">
		<?php
		// Output the checkbox when shipping country is allowed for billing
		else :
		?>
			<p id="billing_same_as_shipping_field" class="form-row form-row-wide">
				<label class="checkbox">
					<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="billing_same_as_shipping" id="billing_same_as_shipping" value="1" <?php checked( $this->is_billing_same_as_shipping(), true ); ?>> <?php echo __( 'Same as shipping address', 'woocommerce-fluid-checkout' ); ?></span>
				</label>
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
		$billing_same_as_shipping = apply_filters( 'wfc_default_to_billing_same_as_shipping', get_option( 'wfc_default_to_billing_same_as_shipping', 'yes' ) == 'yes' );

		// Try get value from the post_data
		if ( isset( $_POST['post_data'] ) ) {
			$billing_same_as_shipping = isset( $posted_data['billing_same_as_shipping'] ) && $posted_data['billing_same_as_shipping'] === '1' ? true : false;
		}
		// Try get value from the form data sent on process checkout
		else if ( isset( $_POST['billing_same_as_shipping'] ) ) {
			$billing_same_as_shipping = isset( $_POST['billing_same_as_shipping'] ) && wc_clean( wp_unslash( $_POST['billing_same_as_shipping'] ) ) === '1' ? true : false;
		}
		// Try to get value from the session
		else if ( WC()->session->__isset( 'wfc_billing_same_as_shipping' ) ) {
			$billing_same_as_shipping = WC()->session->get( 'wfc_billing_same_as_shipping' ) === '1';
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

		return $this->is_billing_same_as_shipping_checked();
	}

	/**
	 * Save value of `billing_same_as_shipping` to the current user session.
	 */
	public function set_billing_same_as_shipping_session( $billing_same_as_shipping ) {
		// Set session value
		WC()->session->set( 'wfc_billing_same_as_shipping', $billing_same_as_shipping ? '1' : '0');
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
		
		// Maybe set post data for billing same as shipping
		if ( $is_billing_same_as_shipping ) {
			$_POST['billing_first_name'] = isset( $_POST['shipping_first_name'] ) ? wc_clean( wp_unslash( $_POST['shipping_first_name'] ) ) : null;
			$_POST['billing_last_name'] = isset( $_POST['shipping_last_name'] ) ? wc_clean( wp_unslash( $_POST['shipping_last_name'] ) ) : null;
			$_POST['billing_phone'] = isset( $_POST['shipping_phone'] ) ? wc_clean( wp_unslash( $_POST['shipping_phone'] ) ) : null;
			
			$_POST['address'] = isset( $_POST['s_address'] ) ? wc_clean( wp_unslash( $_POST['s_address'] ) ) : null;
			$_POST['address_2'] = isset( $_POST['s_address_2'] ) ? wc_clean( wp_unslash( $_POST['s_address_2'] ) ) : null;
			$_POST['city'] = isset( $_POST['s_city'] ) ? wc_clean( wp_unslash( $_POST['s_city'] ) ) : null;
			$_POST['state'] = isset( $_POST['s_state'] ) ? wc_clean( wp_unslash( $_POST['s_state'] ) ) : null;
			$_POST['country'] = isset( $_POST['s_country'] ) ? wc_clean( wp_unslash( $_POST['s_country'] ) ) : null;
			$_POST['postcode'] = isset( $_POST['s_postcode'] ) ? wc_clean( wp_unslash( $_POST['s_postcode'] ) ) : null;
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
			$post_data[ 'billing_first_name' ] = $post_data[ 'shipping_first_name' ];
			$post_data[ 'billing_last_name' ] = $post_data[ 'shipping_last_name' ];
			$post_data[ 'billing_phone' ] = $post_data[ 'shipping_phone' ];
			
			$post_data[ 'billing_address_1' ] = $post_data[ 'shipping_address_1' ];
			$post_data[ 'billing_address_2' ] = $post_data[ 'shipping_address_2' ];
			$post_data[ 'billing_city' ] = $post_data[ 'shipping_city' ];
			$post_data[ 'billing_state' ] = $post_data[ 'shipping_state' ];
			$post_data[ 'billing_country' ] = $post_data[ 'shipping_country' ];
			$post_data[ 'billing_postcode' ] = $post_data[ 'shipping_postcode' ];
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
		do_action( 'wfc_output_step_payment', 'payment' );
	}
	


	/**
	 * Output payment substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_payment( $step_id ) {
		$substep_id = 'payment';
		$this->output_substep_start_tag( $step_id, $substep_id, __( 'Payment Method', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $step_id, $substep_id );
		$this->output_substep_payment_fields();
		$this->output_substep_fields_end_tag();

		$this->output_substep_end_tag( $step_id, $substep_id, false );
	}



	/**
	 * Output payment fields.
	 */
	public function output_substep_payment_fields() {
		wc_get_template(
			'wfc/checkout/form-payment.php',
			array(
				'checkout'          => WC()->checkout(),
			)
		);
	}



	/**
	 * Remove links and fix accessibility attributes for payment method icons.
	 */
	public function change_payment_gateway_icon_html( $icon, $id ) {
		
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
			'class' => 'wfc-sidebar',
		);
		$sidebar_attributes_inner = array(
			'class' => 'wfc-sidebar__inner',
		);

		// Sticky state attributes
		if ( get_option( 'wfc_enable_checkout_sticky_order_summary', 'yes' ) === 'yes' ) {
			$sidebar_attributes = array_merge( $sidebar_attributes, array(
				'data-sticky-states' => true,
				'data-sticky-container' => 'div.woocommerce',
			) );
			$sidebar_attributes_inner = array_merge( $sidebar_attributes_inner, array(
				'data-sticky-states-inner' => true,
			) );
		}

		$sidebar_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $sidebar_attributes ), $sidebar_attributes ) );
		$sidebar_attributes_inner_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $sidebar_attributes_inner ), $sidebar_attributes_inner ) );
		?>
		<div <?php echo $sidebar_attributes_str; ?>>
			<div <?php echo $sidebar_attributes_inner_str; ?>>
				<?php do_action( 'wfc_checkout_order_review_section' ); ?>
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
		return apply_filters( 'wfc_order_review_title', __( 'Order Summary', 'woocommerce-fluid-checkout' ) );
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
			'id' => 'wfc-checkout-order-review',
			'class' => 'wfc-checkout-order-review',
		);

		// Sidebar widget
		if ( $is_sidebar_widget ) {
			$attributes = array_merge( $attributes, array(
				'data-flyout' => true,
				'data-flyout-order-review' => true,
				'data-flyout-open-animation-class' => 'fade-in-down',
				'data-flyout-close-animation-class' => 'fade-out-up',
			) );
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
			'class' => 'wfc-checkout-order-review__inner',
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
			'wfc/checkout/review-order-section.php',
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
			'wfc/checkout/review-order-section.php',
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
	public function output_checkout_place_order( $step_id, $is_sidebar = false ) {
		ob_start();
		wc_get_template(
			'wfc/checkout/place-order.php',
			array(
				'checkout'           => WC()->checkout(),
				'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) ),
			)
		);
		$place_order_html = ob_get_clean();

		// Add terms checkbox custom class
		$place_order_html = str_replace( 'input-checkbox" name="terms"', 'input-checkbox wfc-terms-checkbox" name="terms"', $place_order_html );

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

		echo $place_order_html;
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
		if ( get_option( 'wfc_enable_checkout_place_order_sidebar', 'no' ) === 'no' ) { return; }

		$this->output_checkout_place_order( '__sidebar', true );
	}

	/**
	 * Add wrapper element and custom class for the checkout place order button.
	 */
	public function add_place_order_button_wrapper( $button_html ) {
		$button_html = str_replace( 'class="button alt', 'class="button alt wfc-place-order-button', $button_html );
		return '<div class="wfc-place-order">' . $button_html . '</div>';
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
				'wfc/checkout/review-order-shipping.php',
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
	 * Update the customer's data to the WC_Customer object.
	 * 
	 * @param string $posted_data Post data for all checkout fields.
	 */
	public function update_customer_persisted_data( $posted_data ) {
		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();

		// Define which WC_Customer fields to update,
		// Because `shipping_phone` is not a native WC_Customer field it does not work here.
		$persisted_field_keys = apply_filters( 'wfc_customer_persisted_fields_keys', array(
			'billing_email',
			'billing_first_name',
			'billing_last_name',
			'billing_phone',
			'billing_company',

			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
		) );

		// Get values for the persisted fields
		$persisted_fields = array();
		foreach ( $persisted_field_keys as $field_key ) {
			if ( array_key_exists( $field_key, $parsed_posted_data ) ) {
				$persisted_fields[ $field_key ] = $parsed_posted_data[ $field_key ];
			}
		}

		// Allow developers to change the values
		$persisted_fields = apply_filters( 'wfc_customer_persisted_fields_before_update', $persisted_fields, $posted_data, $parsed_posted_data );

		// Update customer data to the customer object
		WC()->customer->set_props( $persisted_fields );
	}



	/**
	 * Change default order notes value
	 */
	public function change_default_order_comments_value( $value, $input ) {
		return $this->get_order_comments_session();
	}

	/**
	 * Get order notes values from session.
	 *
	 * @return  array  The order notes fields values saved to session.
	 */
	public function get_order_comments_session() {
		$order_comments = WC()->session->get( '_order_comments' );
		return $order_comments;
	}

	/**
	 * Save the order notes fields values to the current user session.
	 * 
	 * @param array $posted_data Post data for all checkout fields.
	 */
	public function set_order_comments_session( $posted_data ) {
		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();

		// Get order notes values
		$order_comments = $parsed_posted_data['order_comments'];

		// Set session value
		WC()->session->set( '_order_comments', $order_comments );
		
		return $posted_data;
	}

	/**
	 * Unset order notes session.
	 **/
	public function unset_order_comments_session() {
		WC()->session->set( '_order_comments', null );
	}



	/**
	 * END - Persisted Data
	 */

}

FluidCheckout_Steps::instance();
