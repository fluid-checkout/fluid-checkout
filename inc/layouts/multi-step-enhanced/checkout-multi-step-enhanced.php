<?php
/**
 * Checkout steps layout: Multi Step Enhanced
 */
class FluidCheckoutLayout_MultiStepEnhanced extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
        // Load dependency: FluidCheckoutLayout_MultiStep
        require_once self::$directory_path . 'inc/layouts/multi-step/checkout-multi-step.php';

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );
		
		// Template loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 30, 3 );

		// Contact
		remove_action( 'wfc_checkout_steps', array( $this->multistep(), 'output_step_billing' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_contact' ), 10 );
		add_action( 'wfc_checkout_before_step_contact_fields', array( $this, 'output_contact_step_section_title' ), 10 );

		// Shipping
		add_action( 'wfc_checkout_after_step_shipping_fields', array( $this, 'output_shipping_methods_available' ), 10 );
		add_action( 'wfc_shipping_methods_before_packages', array( $this, 'output_shipping_methods_start_tag' ), 10 );
		add_action( 'wfc_shipping_methods_after_packages', array( $this, 'output_shipping_methods_end_tag' ), 10 );

		// Payment
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		remove_action( 'wfc_checkout_before_step_payment_fields', array( $this->multistep(), 'output_order_review' ), 5 );
		remove_action( 'wfc_checkout_after_step_payment_fields', array( $this->multistep(), 'output_checkout_place_order' ), 100 );
		remove_filter( 'woocommerce_order_button_html', array( $this->multistep(), 'get_payment_step_actions_html' ), 20 );
		add_action( 'wfc_checkout_after_step_payment_fields', array( $this, 'output_payment_step_actions_html' ), 100 );
		
		// Order Review
		add_action( 'wfc_checkout_after_steps', array( $this, 'output_checkout_order_review_wrapper' ), 10 );
		add_action( 'wfc_checkout_order_review_wrapper', array( $this->multistep(), 'output_order_review' ), 10 );
		add_action( 'wfc_checkout_order_review_wrapper', array( $this->multistep(), 'output_checkout_place_order' ), 30 );
		
	}



	/**
	 * Return WooCommerce Fluid Checkout multi-step class instance
	 */
	public function multistep() {
		return FluidCheckoutLayout_MultiStep::instance();
	}



	/**
	 * Add page body class for feature detection
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }
		
		return array_merge( $classes, array( 'has-wfc-checkout-layout--multi-step-enhanced' ) );
	}



	/**
	 * Enqueue scripts
	 */
	public function enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return; }

		// Styles
		wp_enqueue_style( 'wfc-checkout-layout--multi-step-enhanced', self::$directory_url . 'css/checkout-multi-step--enhanced'. self::$asset_version . '.css', NULL, NULL );
		
		// Scripts
		wp_enqueue_script( 'wfc-checkout-steps-enhanced', self::$directory_url . 'js/checkout-steps-enhanced'. self::$asset_version . '.js', NULL, NULL, true );
		wp_add_inline_script( 'wfc-checkout-steps-enhanced', 'window.addEventListener("load",function(){CheckoutStepsEnhanced.init();})' );
	}



	/*
	 * Locate template files from this checkout layout.
	 * @since 1.1.0
	 */
	public function locate_template( $template, $template_name, $template_path ) {
	 
		global $woocommerce;
	 
		$_template = $template;
	 
		if ( ! $template_path ) $template_path = $woocommerce->template_url;
	 
		// Get plugin path
		$plugin_path  = self::$directory_path . 'inc/layouts/multi-step-enhanced/templates/';
	 
		// Look within passed path within the theme
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name
			)
		);
	 
		// Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}
	 
		// Use default template
		if ( ! $template ){
			$template = $_template;
		}
	 
		// Return what we found
		return $template;
	}



	/**
	 * Output order review section wrapper
	 */
	public function output_checkout_order_review_wrapper() {
		?>
		<div class="wfc-checkout-order-review-wrapper">
			<?php do_action( 'wfc_checkout_order_review_wrapper' ) ?>
		</div>
		<?php
	}









	/**
	 * Checkout Step: Contact
	 */



	/**
	 * Output step: Contact
	 */
	public function output_step_contact() {
		$this->multistep()->output_step_start_tag( apply_filters( 'wfc_contact_step_title', __( 'Contact', 'woocommerce-fluid-checkout' ) ) );
		do_action( 'woocommerce_checkout_before_customer_details' );

		// Define contact fields
		$contact_fields = apply_filters( 'wfc_checkout_contact_step_field_ids', array(
			'billing_email',
			'billing_full_name',
			'billing_first_name',
			'billing_last_name',
			'billing_phone',
		) );

		// Get user data
		$user_data = array();
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();

			$user_data = array(
				'user_email'   => $current_user->user_email,
				'display_name' => ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->first_name.' '.$current_user->last_name,
			);
			$billing_phone = get_user_meta( $current_user->ID, 'billing_phone', true );
			if ( ! empty( $billing_phone ) ) {
				$user_data['billing_phone'] = $billing_phone;
			}

			$user_data = apply_filters( 'wfc_checkout_contact_user_data', $user_data );
		}

		wc_get_template(
			'checkout/form-contact.php',
			array(
				'checkout'          => WC()->checkout(),
				'display_fields'    => $contact_fields,
				'user_data'			=> $user_data,
			)
		);

		echo $this->get_contact_step_actions_html();
		$this->multistep()->output_step_end_tag();
	}

	/**
	 * Return html for contact step actions
	 */
	public function get_contact_step_actions_html() {
		$actions_html = '<div class="wfc-actions"><button class="wfc-next button alt">' . __( 'Proceed to Shipping', 'woocommerce-fluid-checkout' ) . '</button></div>';
		return apply_filters( 'wfc_contact_step_actions_html', $actions_html );
	}

	/**
	 * Output contact step section title
	 */
	public function output_contact_step_section_title() {
		?>
		<h3 class="wfc-checkout-step-title"><?php echo esc_html( apply_filters( 'wfc_checkout_contact_step_section_title', __( 'Contact details', 'woocommerce-fluid-checkout' ) ) ); ?></h3>
		<?php
	}





	/**
	 * Checkout Step: Shipping
	 */



	/**
	 * Get shipping methods for user selection.
	 *
	 * @access public
	 */
	function output_shipping_methods_available() {
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
				'package'                  => $package,
				'available_methods'        => $package['rates'],
				'show_package_details'     => sizeof( $packages ) > 1,
				'show_shipping_calculator' => is_cart() && $first_item,
				'package_details'          => implode( ', ', $product_names ),
				// @codingStandardsIgnoreStart
				'package_name'             => apply_filters( 'woocommerce_shipping_package_name', sprintf( _nx( 'Shipping', 'Shipping %d', ( $i + 1 ), 'shipping packages', 'woocommerce' ), ( $i + 1 ) ), $i, $package ),
				// @codingStandardsIgnoreEnd
				'index'                    => $i,
				'chosen_method'            => $chosen_method,
			) );
	
			$first_item = false;
		}

		do_action( 'wfc_shipping_methods_after_packages' );
	}

	/**
	 * Output shipping methods start tag
	 */
	public function output_shipping_methods_start_tag() {
		?>
		<div class="shipping-method__packages">
			<h3><?php esc_html_e( 'Shipping Methods', 'woocommerce' ); ?></h3>
		<?php
	}

	/**
	 * Output shipping methods end tag
	 */
	public function output_shipping_methods_end_tag() {
		?>
		</div>
		<?php
	}

	/**
	 * Change shipping methods full label including price with markup necessary for displaying price as a separate element
	 */
	function get_cart_shipping_methods_label( $method ) {
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
	 * Checkout Step: Payment
	 */



	/**
	 * Return html for payment step actions
	 */
	public function get_payment_step_actions_html() {
		$actions_html = '<div class="wfc-actions"><button class="wfc-prev">' . _x( 'Back', 'Previous step button', 'woocommerce-fluid-checkout' ) . '</button></div>';
		return apply_filters( 'wfc_payment_step_actions_html', $actions_html, null );
	}

	/**
	 * Output payment step actions
	 */
	public function output_payment_step_actions_html() {
		echo $this->get_payment_step_actions_html();
	}


	
	/**
	 * END - Checkout Steps
	 */

}

FluidCheckoutLayout_MultiStepEnhanced::instance();
