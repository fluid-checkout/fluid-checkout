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
	 *      ['render_callback']              callable    Function name or callable array to display the contents of the checkout step.
	 *      ['render_condition_callback']    callable    (optional) Function name or callable array to determine if the step should be rendered. If a callback is not provided the checkout step will be displayed.
	 *      ['is_complete_callback']         callable    (optional) Function name or callable array to determine if all required date for the step has been provided. If a callback is not provided it will consider the step as 'incomplete'.	
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

		// Checkout Header
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_checkout_header' ), 1 ); // Uses `woocommerce_before_checkout_form_cart_notices` as it runs before the hook `woocommerce_before_checkout_form`
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_header_cart_link_fragment' ), 10 );
		add_action( 'wfc_checkout_header_cart_link', array( $this, 'output_checkout_header_cart_link' ), 10 );
		
		// Notices
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_checkout_notices_wrapper_start_tag' ), 5 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'output_checkout_notices_wrapper_end_tag' ), 100 );

		// Checkout steps
		add_action( 'wfc_checkout_before_steps', array( $this, 'output_checkout_progress_bar' ), 10 );
		add_action( 'init', array( $this, 'register_default_checkout_steps' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_checkout_steps' ), 10 );
		add_action( 'wfc_checkout_after', array( $this, 'output_checkout_order_review_wrapper' ), 10 );

		// Contact
		add_action( 'wfc_output_step_contact', array( $this, 'output_substep_contact' ), 10 );

		// Account creation
		add_action( 'wfc_checkout_after_contact_fields', array( $this, 'output_form_account_creation' ), 10 );

		// Shipping
		add_action( 'wfc_output_step_shipping', array( $this, 'output_substep_shipping_address' ), 10 );
		add_action( 'wfc_output_step_shipping', array( $this, 'output_substep_shipping_method' ), 20 );
		add_action( 'wfc_output_step_shipping', array( $this, 'output_substep_order_notes' ), 100 );
		add_action( 'wfc_cart_totals_shipping', array( $this, 'output_cart_totals_shipping_section' ), 10 );
		add_action( 'wfc_before_checkout_shipping_address_wrapper', array( $this, 'output_ship_to_different_address_hidden_field' ), 10 );
		add_filter( 'woocommerce_ship_to_different_address_checked', array( $this, 'set_ship_to_different_address_true' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_shipping_methods_fragment' ), 10 );
		
		// Billing Address
		add_action( 'wfc_output_step_billing', array( $this, 'output_substep_billing_address' ), 10 );

		// Payment
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_checkout_payment', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_output_step_payment', array( $this, 'output_substep_payment' ), 80 );
		add_action( 'wfc_output_step_payment', array( $this, 'output_order_review' ), 90 );
		add_action( 'wfc_output_step_payment', array( $this, 'output_checkout_place_order' ), 100 );
		
		// Order Review
		add_action( 'wfc_checkout_order_review_section', array( $this, 'output_order_review_for_sidebar' ), 10 );
		add_action( 'wfc_review_order_shipping', array( $this, 'maybe_output_order_review_shipping_method_chosen' ), 30 );
		
		// Order Received (default functionality)
		add_action( 'wfc_order_received_failed', array( $this, 'output_order_received_failed_template' ), 10 );
		add_action( 'wfc_order_received_successful', array( $this, 'output_order_received_successful_template' ), 10 );
		add_action( 'wfc_order_received_successful_no_order_details', array( $this, 'output_order_received_no_order_details_template' ), 10 );
		add_action( 'woocommerce_thankyou', array( $this, 'do_woocommerce_thankyou_payment_method' ), 1 );
		add_action( 'wfc_order_details_after_order_table_section', array( $this, 'output_order_customer_details' ), 10 );
		add_action( 'wfc_order_details_before_order_table_section', array( $this, 'output_order_downloads_details' ), 10 );
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
		if ( $this->get_hide_site_header_at_checkout() ) {
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
		
		// Scripts
		wp_enqueue_script( 'wfc-checkout-steps', self::$directory_url . 'js/checkout-steps'. self::$asset_version . '.js', NULL, NULL, true );
		wp_add_inline_script( 'wfc-checkout-steps', 'window.addEventListener("load",function(){CheckoutSteps.init();})' );
	}



	/**
	 * Get option for hiding the site's original header at the checkout page.
	 *
	 * @return  boolean  True if should hide the site's original header at the checkout page, false otherwise.
	 */
	public function get_hide_site_header_at_checkout() {
		// Bail if WooCommerce class not available
		if ( ! function_exists( 'WC' ) ) { return false; }

		// Get checkout object.
		$checkout = WC()->checkout();

		return ( ! ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) ) && 'true' === get_option( 'wfc_hide_site_header_at_checkout', 'true' );
	}

	/**
	 * Get option for hiding the site's original footer at the checkout page.
	 *
	 * @return  boolean  True if should hide the site's original footer at the checkout page, false otherwise.
	 */
	public function get_hide_site_footer_at_checkout() {
		// Bail if WooCommerce class not available
		if ( ! function_exists( 'WC' ) ) { return false; }

		// Get checkout object.
		$checkout = WC()->checkout();

		return ( ! ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) ) && 'true' === get_option( 'wfc_hide_site_footer_at_checkout', 'true' );
	}



	/**
	 * Get the current checkout layout value.
	 *
	 * @return  string  The name of the currently selected checkout layout option. Defaults to `multi-step`.
	 */
	public function get_checkout_layout() {
		$allowed_values = apply_filters( 'wfc_allowed_checkout_layouts', array( 'multi-step', 'one-page' ) );
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
		if ( ! $this->get_hide_site_header_at_checkout() ) { return; }
		
		wc_get_template(
			'checkout/checkout-header.php',
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
		?>
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wfc-checkout__cart-link" data-flyout-toggle data-flyout-target="[data-flyout-order-review]"><?php wc_cart_totals_order_total_html(); ?></a>
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
		$cart_link_html = $this->get_checkout_header_cart_link();
		$fragments['.wfc-checkout__cart-link'] = $cart_link_html;
		return $fragments;
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
	 * @return  array  An array of the registered checkout steps. For more details of what is expected see the documentation of the property $checkout_steps of this class.
	 * @see $checkout_steps
	 */
	public function get_checkout_steps() {
		return $this->checkout_steps;
	}



	/**
	 * Register a new checkout step.
	 *
	 * @param   array  $step_args   Arguments of the checkout step. For more details of what is expected see the documentation of the property $checkout_steps of this class.
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
		
		// Sanitize step id
		$step_args[ 'step_id' ] = sanitize_title( $step_args[ 'step_id' ] );
		$step_id = $step_args[ 'step_id' ];

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
		
		$this->register_checkout_step( array(
			'step_id' => 'contact',
			'step_title' => _x( 'Contact', 'Checkout step title', 'woocommerce-fluid-checkout' ),
			'priority' => 10,
			'render_callback' => array( $this, 'output_step_contact' ),
			'is_complete_callback' => array( $this, 'is_step_complete_contact' ),
		) );

		$this->register_checkout_step( array(
			'step_id' => 'shipping',
			'step_title' => _x( 'Shipping', 'Checkout step title', 'woocommerce-fluid-checkout' ),
			'priority' => 20,
			'render_callback' => array( $this, 'output_step_shipping' ),
			'render_condition_callback' => array( WC()->cart, 'needs_shipping' ),
			'is_complete_callback' => array( $this, 'is_step_complete_shipping' ),
		) );

		$this->register_checkout_step( array(
			'step_id' => 'billing',
			'step_title' => _x( 'Billing', 'Checkout step title', 'woocommerce-fluid-checkout' ),
			'priority' => 30,
			'render_callback' => array( $this, 'output_step_billing' ),
			'is_complete_callback' => array( $this, 'is_step_complete_billing' ),
		) );

		$this->register_checkout_step( array(
			'step_id' => 'payment',
			'step_title' => _x( 'Payment', 'Checkout step title', 'woocommerce-fluid-checkout' ),
			'priority' => 100,
			'render_callback' => array( $this, 'output_step_payment' ),
			'is_complete_callback' => '__return_false', // Payment step is only complete when the order has been placed and the payment has been accepted, during the checkout process it will always be considered 'incomplete'.
		) );

	}



	/**
	 * Output the contents of each registered checkout step.
	 */
	public function output_checkout_steps() {
		foreach ( $this->get_checkout_steps() as $step_args ) {
			$step_id = $step_args[ 'step_id' ];
			$render_callback = array_key_exists( 'render_callback', $step_args ) ? $step_args[ 'render_callback' ] : null;
			$render_conditional_callback = array_key_exists( 'render_condition_callback', $step_args ) ? $step_args[ 'render_condition_callback' ] : null;
			
			// Skip step if step render function not callable
			if ( ! $render_callback || ! is_callable( $render_callback ) ) {
				trigger_error( "The output function for the checkout step with `step_id = {$step_id}` is not callable. Skipping step render.", E_USER_WARNING );
				continue;
			}
			
			// Skip step if conditional is not met
			if ( $render_conditional_callback && is_callable( $render_conditional_callback ) && ! call_user_func( $render_conditional_callback ) ) { continue; }
			
			// Output the step
			$this->output_step_start_tag( $step_args );
			call_user_func( $render_callback );
			$this->output_step_end_tag( $step_args );
		}
	}




	/**
	 * Checkout Progress Bar
	 */



	/**
	 * Get the list checkout steps considered complete, those which all required data has been provided.
	 *
	 * @return  array  List of checkout steps which all required data has been provided. The index is preserved from the original checkout steps list.
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

		return $complete_steps;
	}
	
	
	
	/**
	 * Get the list checkout steps considered incomplete, those missing required data.
	 *
	 * @return  array  List of checkout steps with required data missing. The index is preserved from the original checkout steps list.
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
	 * @return  array  An array with only one value, the first checkout step which is considered incomplete. The index is preserved from the original checkout steps list.
	 */
	public function get_current_step() {
		$incomplete_steps = $this->get_incomplete_steps();
		return array_slice( $incomplete_steps, 0, 1, true );
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
		$current_step_index = ( array_keys( $current_step )[0] ); // First and only value in the array, the key is preserved from the original checkout steps list
		$current_step_id = $current_step[ $current_step_index ][ 'step_id' ];

		return ( $step_id == $current_step_id );
	}



	/**
	 * Determine if the step is complete.
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
		$current_step_index = ( array_keys( $current_step )[0] ); // First and only value in the array, the key is preserved from the original checkout steps list
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

		?>
		<div class="wfc-progress-bar" data-progress-bar>
			<div class="wfc-progress-bar__count" data-step-count-text><?php echo $steps_count_label_html ?></div>
			<div class="wfc-progress-bar__bars" data-progress-bar data-step-count="<?php echo esc_attr( $steps_count ); ?>">
				<?php
				foreach ( $_checkout_steps as $step_index => $step_args ) :
					$step_bar_class = $step_index < $current_step_index ? 'complete' : ( $step_index == $current_step_index ? 'current' : '' );
					?>
					<span class="wfc-progress-bar__bar <?php echo esc_attr( $step_bar_class ); ?>" data-step-id="<?php echo esc_attr( $step_args[ 'step_id' ] ); ?>" data-step-index="<?php echo esc_attr( $step_index ); ?>"></span>
				<?php
				endforeach;
				?>
			</div>
		</div>
		<?php
	}



	/**
	 * Output checkout step start tag.
	 *
	 * @param   array  $step_args   Arguments of the checkout step. For more details of what is expected see the documentation of the property $checkout_steps of this class.
	 */
	public function output_step_start_tag( $step_args ) {
		$step_id = $step_args[ 'step_id' ];
		$step_title = apply_filters( "wfc_step_title_{$step_id}", $step_args[ 'step_title' ] );
		$step_attributes = array(
			'data-step-id' => ! empty( $step_id ) && $step_id != null ? $step_id : '',
			'data-step-label' => $step_title,
			'data-step-complete' => $this->is_step_complete( $step_id ),
			'data-step-current' => $this->is_current_step( $step_id ),
		);
		$step_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $step_attributes ), $step_attributes ) );
		?>
		<section class="wfc-checkout-step" <?php echo $step_attributes_str; ?>>
		<?php
	}

	/**
	 * Output checkout step end tag.
	 * 
	 * @param   array  $step_args   Arguments of the checkout step. For more details of what is expected see the documentation of the property $checkout_steps of this class.
	 */
	public function output_step_end_tag( $step_args ) {
		?>
		</section>
		<?php
	}



	/**
	 * Output checkout substep start tag.
	 */
	public function output_substep_start_tag( $substep_id, $substep_title ) {
		$substep_title = apply_filters( "wfc_substep_title_{$substep_id}", $substep_title );
		$substep_attributes = array(
			'data-substep-id' => ! empty( $substep_id ) && $substep_id != null ? $substep_id : '',
		);
		$substep_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_attributes ), $substep_attributes ) );
		?>
		<div class="wfc-step__substep" <?php echo $substep_attributes_str; ?>>
			<h3 class="wfc-step__substep-title"><?php echo esc_html( $substep_title ); ?></h3>
		<?php
	}

	/**
	 * Output checkout substep end tag.
	 */
	public function output_substep_end_tag() {
		?>
		</div>
		<?php
	}



	/**
	 * Output checkout substep start tag.
	 */
	public function output_substep_fields_start_tag( $substep_id ) {
		$substep_attributes = array(
			'data-substep-id' => ! empty( $substep_id ) && $substep_id != null ? $substep_id : '',
		);
		$substep_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_attributes ), $substep_attributes ) );
		?>
		<div class="wfc-step__substep-fields" <?php echo $substep_attributes_str; ?>>
		<?php
	}

	/**
	 * Output checkout substep end tag.
	 */
	public function output_substep_fields_end_tag() {
		?>
		</div>
		<?php
	}



	/**
	 * Output checkout substep start tag.
	 */
	public function output_substep_text_start_tag( $substep_id ) {
		$substep_attributes = array(
			'data-substep-id' => ! empty( $substep_id ) && $substep_id != null ? $substep_id : '',
		);
		$substep_attributes_str = implode( ' ', array_map( array( $this, 'map_html_attributes' ), array_keys( $substep_attributes ), $substep_attributes ) );
		?>
		<div class="wfc-step__substep-text" <?php echo $substep_attributes_str; ?>>
		<?php
	}

	/**
	 * Output checkout substep end tag.
	 */
	public function output_substep_text_end_tag() {
		?>
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
		do_action( 'wfc_output_step_contact' );
	}

	/**
	 * Output contact substep.
	 */
	public function output_substep_contact() {
		$substep_id_contact = 'contact';
		$this->output_substep_start_tag( $substep_id_contact, __( 'My contact', 'woocommerce-fluid-checkout' ) );
		
		$this->output_substep_fields_start_tag( $substep_id_contact );
		$this->output_step_contact_fields();
		$this->output_substep_fields_end_tag();
		
		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $substep_id_contact );
			$this->output_substep_contact_text();
			$this->output_substep_text_end_tag();
		}
			
		$this->output_substep_end_tag();
	}
	
	/**
	 * Output contact step fields.
	 */
	public function output_step_contact_fields() {
		do_action( 'woocommerce_checkout_before_customer_details' );
		
		wc_get_template(
			'checkout/form-contact.php',
			array(
				'checkout'			=> WC()->checkout(),
				'display_fields'	=> $this->get_contact_step_display_fields(),
				'user_data'			=> $this->get_user_data(),
			)
		);
	}


	/**
	 * Output contact substep in text format for when the step is complete.
	 */
	public function output_substep_contact_text() {
		$checkout = WC()->checkout();
		$text_html = '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_email' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_first_name' ) . ' ' . $checkout->get_value( 'billing_last_name' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_phone' ) . '</span>';

		echo apply_filters( 'wfc_substep_contact_text', $text_html );
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
		$contact_display_field_keys = $this->get_contact_step_display_fields();

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
			'checkout/form-account-creation.php',
			array(
				'checkout'			=> WC()->checkout(),
			)
		);
	}



	/**
	 * Return list of checkout fields for contact step.
	 */
	public function get_contact_step_display_fields() {
		return apply_filters( 'wfc_checkout_contact_step_field_ids', array(
			'billing_email',
			'billing_first_name',
			'billing_last_name',
			'billing_phone',
		) );
	}

	/**
	 * Get user data for checkout steps.
	 */
	public function get_user_data() {
		$user_data = array();

		if ( is_user_logged_in() ) {
			$current_user = WC()->customer;

			$user_data = array(
				'user_email'	=> $current_user->get_email(),
				'display_name'	=> $current_user->get_billing_first_name() . ' ' . $current_user->get_billing_last_name(),
			);
			
			if ( 'hidden' !== get_option( 'woocommerce_checkout_phone_field', 'required' ) ) {
				$billing_phone = $current_user->get_billing_phone();
				if ( ! empty( $billing_phone ) ) {
					$user_data['billing_phone'] = $billing_phone;
				}
			}

			$user_data = apply_filters( 'wfc_checkout_contact_user_data', $user_data );
		}

		return $user_data;
	}





	/**
	 * Checkout Step: Shipping.
	 */



	/**
	 * Output shipping step.
	 */
	public function output_step_shipping() {
		do_action( 'wfc_output_step_shipping' );
	}

	/**
	 * Output shipping address substep.
	 */
	public function output_substep_shipping_address() {
		$substep_id_shipping_address = 'shipping_address';
		$this->output_substep_start_tag( $substep_id_shipping_address, __( 'Shipping Address', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $substep_id_shipping_address );
		$this->output_substep_shipping_address_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $substep_id_shipping_address );
			$this->output_substep_shipping_address_text();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag();
	}

	/**
	 * Output shipping method substep.
	 */
	public function output_substep_shipping_method() {
		$substep_id_shipping_method = 'shipping_method';
		$this->output_substep_start_tag( $substep_id_shipping_method, __( 'Shipping Method', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $substep_id_shipping_method );
		$this->output_shipping_methods_available();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $substep_id_shipping_method );
			$this->output_substep_text_shipping_method();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag();
	}

	/**
	 * Output order notes substep.
	 */
	public function output_substep_order_notes() {
		$substep_id_order_notes = 'order_notes';
		$this->output_substep_start_tag( $substep_id_order_notes, __( 'Additional notes', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $substep_id_order_notes );
		$this->output_additional_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $substep_id_order_notes );
			$this->output_substep_text_order_notes();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag();
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
	 * Output shipping address substep in text format for when the step is complete.
	 */
	public function output_substep_shipping_address_text() {
		$checkout = WC()->checkout();
		$text_html = '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_first_name' ) . '' . $checkout->get_value( 'shipping_last_name' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_phone' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_company' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_address_1' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_address_2' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_city' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_state' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_country' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'shipping_postcode' ) . '</span>';

		echo apply_filters( 'wfc_substep_shipping_address_text', $text_html );
	}



	/**
	 * Output shipping method substep in text format for when the step is complete.
	 */
	public function output_substep_text_shipping_method() {
		$packages = WC()->shipping()->get_packages();

		foreach ( $packages as $i => $package ) {
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;
			$chosen_method_label = wc_cart_totals_shipping_method_label( $method );
			
			// TODO: Maybe handle multiple packages
			// $package_name = apply_filters( 'woocommerce_shipping_package_name', ( ( $i + 1 ) > 1 ) ? sprintf( _x( 'Shipping %d', 'shipping packages', 'woocommerce' ), ( $i + 1 ) ) : _x( 'Shipping', 'shipping packages', 'woocommerce' ), $i, $package );

			echo '<span class="wfc-step__substep-text-line">' . $chosen_method_label . '</span>';
		}
	}



	/**
	 * Output order notes substep in text format for when the step is complete.
	 */
	public function output_substep_text_order_notes() {
		$checkout = WC()->checkout();
		echo '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'order_notes' ) . '</span>';
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
			'cart/cart-totals-shipping.php'
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
	
			wc_get_template( 'cart/shipping-methods-available.php', array(
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
	 * Add shipping methods as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_checkout_shipping_methods_fragment( $fragments ) {
		$shipping_methods_html = $this->get_shipping_methods_available();
		$fragments['.shipping-method__packages'] = $shipping_methods_html;
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
		$label     = sprintf( apply_filters( 'wfc_shipping_method_option_label_markup', '<span class="shipping_method__option-label">%s</span>' ), $method->get_label() );
		$has_cost  = 0 < $method->cost;
		$hide_cost = ! $has_cost && in_array( $method->get_method_id(), array( 'free_shipping', 'local_pickup' ), true );
		
		if ( $has_cost && ! $hide_cost ) {
			
			if ( WC()->cart->display_prices_including_tax() ) {

				$method_costs = wc_price( $method->cost + $method->get_shipping_tax() );
				if ( $method->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
					$method_costs .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}

				$label .= sprintf( apply_filters( 'wfc_shipping_method_option_price_markup', ' <span class="shipping_method__option-price">%s</span>' ), $method_costs );

			} else {
				
				$method_costs = wc_price( $method->cost + $method->get_shipping_tax() );
				if ( $method->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
					$method_costs .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}

				$label .= sprintf( apply_filters( 'wfc_shipping_method_option_price_markup', ' <span class="shipping_method__option-price">%s</span>' ), $method_costs );

			}
		}

		return $label;
	}



	/**
	 * Output step: Additional Information fields.
	 */
	public function output_additional_fields() {
		wc_get_template(
			'checkout/form-additional-fields.php',
			array(
				'checkout' => WC()->checkout(),
			)
		);
	}





	/**
	 * Checkout step: Billing.
	 */


	/**
	 * Output billing step.
	 */
	public function output_step_billing() {
		do_action( 'wfc_output_step_billing' );
	}
	


	/**
	 * Output billing address substep.
	 */
	public function output_substep_billing_address() {
		$substep_id_billing_address = 'billing_address';
		$this->output_substep_start_tag( $substep_id_billing_address, __( 'Billing Address', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $substep_id_billing_address );
		$this->output_substep_billing_address_fields();
		$this->output_substep_fields_end_tag();

		// Only output substep text format for multi-step checkout layout
		if ( $this->is_checkout_layout_multistep() ) {
			$this->output_substep_text_start_tag( $substep_id_billing_address );
			$this->output_substep_billing_address_text();
			$this->output_substep_text_end_tag();
		}

		$this->output_substep_end_tag();
	}



	/**
	 * Output billing address fields, except those already added at the contact step.
	 */
	public function output_substep_billing_address_fields() {

		do_action( 'wfc_checkout_before_step_billing_fields' );

		wc_get_template(
			'checkout/form-billing.php',
			array(
				'checkout'			=> WC()->checkout(),
				'ignore_fields'		=> $this->get_contact_step_display_fields(),
			)
		);

		do_action( 'wfc_checkout_after_step_billing_fields' );
	}



	/**
	 * Output billing address substep in text format for when the step is complete.
	 */
	public function output_substep_billing_address_text() {
		$checkout = WC()->checkout();
		$text_html = '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_company' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_address_1' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_address_2' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_city' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_state' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_country' ) . '</span>';
		$text_html .= '<span class="wfc-step__substep-text-line">' . $checkout->get_value( 'billing_postcode' ) . '</span>';

		echo apply_filters( 'wfc_substep_billing_address_text', $text_html );
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
		$contact_display_field_keys = $this->get_contact_step_display_fields();

		// Check required data for billing address
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
	 * Checkout Step: Payment.
	 */



	/**
	 * Output payment step.
	 */
	public function output_step_payment() {
		do_action( 'wfc_output_step_payment' );
	}
	


	/**
	 * Output payment substep.
	 */
	public function output_substep_payment() {
		$substep_id_payment = 'payment';
		$this->output_substep_start_tag( $substep_id_payment, __( 'Payment', 'woocommerce-fluid-checkout' ) );

		$this->output_substep_fields_start_tag( $substep_id_payment );
		$this->output_substep_payment_fields();
		$this->output_substep_fields_end_tag();

		$this->output_substep_end_tag();
	}



	/**
	 * Output payment fields.
	 */
	public function output_substep_payment_fields() {
		wc_get_template(
			'checkout/form-payment.php',
			array(
				'checkout'          => WC()->checkout(),
			)
		);
	}



	/**
	 * END - Checkout Steps.
	 */





	/**
	 * Order Review.
	 */



	/**
	 * Output order review section wrapper.
	 */
	public function output_checkout_order_review_wrapper() {
		?>
		<div class="wfc-sidebar">
			<div class="wfc-sidebar__inner">
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
	 * Output Order Review.
	 */
	public function output_order_review() {
		wc_get_template(
			'checkout/review-order-section.php',
			array(
				'checkout'           => WC()->checkout(),
				'order_review_title' => $this->get_order_review_title(),
				'is_sidebar_widget'  => false,
			)
		);
	}

	/**
	 * Output Order Review for sidebar.
	 */
	public function output_order_review_for_sidebar() {
		wc_get_template(
			'checkout/review-order-section.php',
			array(
				'checkout'           => WC()->checkout(),
				'order_review_title' => $this->get_order_review_title(),
				'is_sidebar_widget'  => true,
			)
		);
	}



	/**
	 * Output checkout place order button.
	 */
	public function output_checkout_place_order() {
		wc_get_template(
			'checkout/place-order.php',
			array(
				'checkout'           => WC()->checkout(),
				'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) ),
			)
		);
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
				'checkout/review-order-shipping.php',
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
	 * Order Received (default functionality).
	 */


	
	/**
	 * Output template part for order received failed.
	 * 
	 * @param   WC_Order   $order   The Order object.
	 */
	public function output_order_received_failed_template( $order ) {
		wc_get_template(
			'checkout/order-received-failed.php',
			array(
				'order'			=> $order,
			)
		);
	}



	/**
	 * Output template part for order received successful.
	 * 
	 * @param   WC_Order   $order   The Order object.
	 */
	public function output_order_received_successful_template( $order ) {
		wc_get_template(
			'checkout/order-received-successful.php',
			array(
				'order'			=> $order,
			)
		);
	}



	/**
	 * Output template part for order received without order details.
	 * 
	 * @param   WC_Order   $order   The Order object.
	 */
	public function output_order_received_no_order_details_template( $order ) {
		wc_get_template(
			'checkout/order-received-no-order-details.php',
			array(
				'order'			=> $order,
			)
		);
	}



	/**
	 * Run the action `woocommerce_thankyou_<payment_method>`, give developers
	 * the ability to define which hook and priority to use.
	 * 
	 * @param   WC_Order   $order   The Order object.
	 */
	public function do_woocommerce_thankyou_payment_method( $order_id ) {
		$order = wc_get_order( $order_id );
		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
	}



	/**
	 * Output order download details.
	 * 
	 * @param   WC_Order   $order   The Order object.
	 */
	public function output_order_downloads_details( $order ) {
		$downloads             = $order->get_downloadable_items();
		$show_downloads        = $order->has_downloadable_item() && $order->is_download_permitted();
		if ( $show_downloads ) {
			wc_get_template(
				'order/order-downloads.php',
				array(
					'downloads'  => $downloads,
					'show_title' => true,
				)
			);
		}
	}



	/**
	 * Output order customer details.
	 * 
	 * @param   WC_Order   $order   The Order object.
	 */
	public function output_order_customer_details( $order ) {
		$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
		if ( $show_customer_details ) {
			wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
		}
	}



	/**
	 * END - Order Received.
	 */

}

FluidCheckout_Steps::instance();
