<?php
/**
 * Checkout steps layout: Multi Step
 */
class FluidCheckout_Layout extends FluidCheckout {

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
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 10 );

		// Checkout Header
		// Uses `woocommerce_before_checkout_form_cart_notices` because it runs before the hook `woocommerce_before_checkout_form`
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_checkout_header' ), 1 );
		
		// Notices
		add_action( 'woocommerce_before_checkout_form_cart_notices', array( $this, 'output_checkout_notices_wrapper_start_tag' ), 5 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'output_checkout_notices_wrapper_end_tag' ), PHP_INT_MAX );

		// Display ctions
		add_action( 'wfc_checkout_before_steps', array( $this, 'output_checkout_progress_bar' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_contact' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_shipping' ), 50 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_payment' ), 100 );
		add_action( 'wfc_checkout_after', array( $this, 'output_checkout_order_review_wrapper' ), 10 );

		// Contact
		add_action( 'wfc_checkout_before_contact_fields', array( $this, 'output_contact_step_section_title' ), 10 );

		// Account creation
		add_action( 'wfc_checkout_after_contact_fields', array( $this, 'output_form_account_creation' ), 10 );

		// Shipping
		add_action( 'wfc_checkout_before_step_shipping_fields', array( $this, 'output_shipping_step_section_title' ), 10 );
		add_action( 'wfc_cart_totals_shipping', array( $this, 'output_cart_totals_shipping_section' ), 10 );
		add_action( 'wfc_before_checkout_shipping_address_wrapper', array( $this, 'output_ship_to_different_address_hidden_field' ), 10 );
		add_filter( 'woocommerce_ship_to_different_address_checked', array( $this, 'set_ship_to_different_address_true' ), 10 );
		add_action( 'wfc_checkout_after_step_shipping_fields', array( $this, 'output_shipping_methods_available' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_checkout_shipping_methods_fragment' ), 10 );
		add_action( 'wfc_shipping_methods_before_packages', array( $this, 'output_shipping_methods_start_tag' ), 10 );
		add_action( 'wfc_shipping_methods_after_packages', array( $this, 'output_shipping_methods_end_tag' ), 10 );

		// Additional Information
		add_action( 'wfc_checkout_after_step_shipping_fields', array( $this, 'maybe_output_additional_fields_shipping_step' ), 50 );
		add_action( 'wfc_checkout_after_step_payment_fields', array( $this, 'maybe_output_additional_fields_payment_step' ), 50 );

		// Payment
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_checkout_before_step_payment_fields', array( $this, 'output_payment_step_section_title' ), 10 );
		add_action( 'wfc_checkout_payment', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_checkout_before_step_billing_fields', array( $this, 'output_billing_address_step_section_title' ), 10 );
		add_action( 'wfc_checkout_before_step_payment_fields', array( $this, 'output_billing_fields' ), 20 );
		add_action( 'wfc_checkout_after_step_payment_fields', array( $this, 'output_payment_step_actions_html' ), 100 );
		
		// Order Review
		add_action( 'wfc_checkout_order_review_section', array( $this, 'output_order_review' ), 10 );
		add_action( 'woocommerce_checkout_after_order_review', array( $this, 'output_checkout_place_order' ), 30 );
		add_action( 'wfc_review_order_shipping', array( $this, 'maybe_output_order_review_shipping_method_chosen' ), 30 );
		
		// Order Received (default functionality)
		add_action( 'wfc_order_received_failed', array( $this, 'output_order_received_failed_template' ), 10 );
		add_action( 'wfc_order_received_successful', array( $this, 'output_order_received_successful_template' ), 10 );
		add_action( 'wfc_order_received_successful_no_order_details', array( $this, 'output_order_received_no_order_details_template' ), 10 );
		add_action( 'woocommerce_thankyou', array( $this, 'do_woocommerce_thankyou_payment_method' ), 1 );
		add_action( 'wfc_order_details_after_order_table_section', array( $this, 'output_order_customer_details' ), 10 );
		add_action( 'wfc_order_details_before_order_table_section', array( $this, 'output_order_downloads_details' ), 10 );

		// Widget Areas
		add_action( 'widgets_init', array( $this, 'register_cart_widgets_areas' ), 50 );
		add_action( 'woocommerce_after_cart_totals', array( $this, 'output_sidebar_cart_totals_inside' ), 50 );
		add_action( 'woocommerce_cart_collaterals', array( $this, 'output_sidebar_cart_totals_outside' ), 11 );
		add_action( 'widgets_init', array( $this, 'register_checkout_widgets_areas' ), 50 );
		add_action( 'woocommerce_checkout_after_order_review', array( $this, 'output_sidebar_order_review_inside' ), 50 );
		add_action( 'wfc_checkout_after_order_review', array( $this, 'output_sidebar_order_review_outside' ), 50 );
	}



	/**
	 * Add page body class for feature detection.
	 *
     * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }

		$add_classes = array();
		
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
	 * @return  Boolean  True if should hide the site's original header at the checkout page, false otherwise.
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
	 * @return  Boolean  True if should hide the site's original footer at the checkout page, false otherwise.
	 */
	public function get_hide_site_footer_at_checkout() {
		// Bail if WooCommerce class not available
		if ( ! function_exists( 'WC' ) ) { return false; }

		// Get checkout object.
		$checkout = WC()->checkout();

		return ( ! ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) ) && 'true' === get_option( 'wfc_hide_site_footer_at_checkout', 'true' );
	}



	/**
	 * Output order review section wrapper.
	 */
	public function output_checkout_order_review_wrapper() {
		?>
		<div class="wfc-checkout-order-review__wrapper">
			<div class="wfc-checkout-order-review__inner">
				<?php echo '<div style="margin: 20px 0; padding: 5px 10px; background-color: white; text-align: center;">ORDER REVIEW SECTION</div>'; ?>
				<?php // do_action( 'wfc_checkout_order_review_section' ); ?>
			</div>
		</div>
		<?php
	}



	/**
	 * Output Order Review.
	 */
	public function output_order_review() {
		wc_get_template(
			'checkout/review-order-section.php',
			array(
				'checkout'           => WC()->checkout(),
				'order_review_title' => apply_filters( 'wfc_order_review_title', __( 'Your order', 'woocommerce' ) ),
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
		// Bail if not checkout page
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
					/* translators: %d: shipping package number */
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
	 * Checkout Header
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
	 * Checkout Steps
	 */


	
	/**
	 * Output the checkout progress bar.
	 */
	public function output_checkout_progress_bar() {
		?>
		<div class="wfc-progress-bar" style="margin: 20px 0; padding: 5px 10px; background-color: #f3f3f3; text-align: center;">
			PROGRESS BAR
		</div>
		<?php
	}



	/**
	 * Output start tag for a checkout step.
	 *
	 * @param   string  $step_label  Step label.
	 * @param   string  $step_id     Step ID.
	 */
	public function output_step_start_tag( $step_label, $step_id = '' ) {
		$step_id_attribute = ! empty( $step_id ) && $step_id != null ? 'data-step-id="'.esc_attr( $step_id ).'"' : '';
		?>
		<section class="wfc-frame" <?php echo $step_id_attribute; ?> data-label="<?php echo esc_attr( $step_label ); ?>">
		<?php
	}

	/**
	 * Output end tag for a checkout step.
	 */
	public function output_step_end_tag() {
		?>
		</section>
		<?php
	}





	/**
	 * Checkout Step: Contact
	 */



	/**
	 * Output step: Contact.
	 */
	public function output_step_contact() {
		$this->output_step_start_tag( apply_filters( 'wfc_contact_step_title', __( 'Contact', 'woocommerce-fluid-checkout' ) ), 'contact' );
		
		echo '<div class="wfc-step__content" style="margin: 20px 0; padding: 5px 10px; background-color: #f3f3f3; text-align: center;">CONTACT STEP</div>';

		// do_action( 'woocommerce_checkout_before_customer_details' );

		// $checkout = WC()->checkout();

		// // Check if user has required data
		// $fields = $checkout->get_checkout_fields( 'billing' );
		// $contact_display_field_keys = $this->get_contact_step_display_fields();
		// $has_required_contact_data = true;
		// foreach ( $contact_display_field_keys as $field_key ) {
		// 	$field = array_key_exists( $field_key, $fields ) ? $fields[ $field_key ] : array();
		// 	if ( $has_required_contact_data && array_key_exists( 'required', $field ) && $field[ 'required' ] === true && ! $checkout->get_value( $field_key ) ) {
		// 		$has_required_contact_data = false;
		// 		break;
		// 	}
		// }
		
		// wc_get_template(
		// 	'checkout/form-contact.php',
		// 	array(
		// 		'checkout'			=> $checkout,
		// 		'display_fields'	=> $contact_display_field_keys,
		// 		'user_data'			=> $this->get_user_data(),
		// 		'has_required_contact_data' => $has_required_contact_data,
		// 	)
		// );

		// echo $this->get_contact_step_actions_html();


		$this->output_step_end_tag();
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
			'billing_full_name',
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
	 * Return html for contact step actions.
	 */
	public function get_contact_step_actions_html() {
		$next_step_label = WC()->cart->needs_shipping() ? __( 'Proceed to Shipping', 'woocommerce-fluid-checkout' ) : __( 'Proceed to Payment', 'woocommerce-fluid-checkout' );
		$actions_html = '<div class="wfc-actions"><button class="wfc-next button alt">' . $next_step_label . '</button></div>';
		return apply_filters( 'wfc_contact_step_actions_html', $actions_html );
	}

	/**
	 * Output contact step section title.
	 */
	public function output_contact_step_section_title() {
		?>
		<h3 class="wfc-checkout-step-title"><?php echo esc_html( apply_filters( 'wfc_checkout_contact_step_section_title', __( 'Contact details', 'woocommerce-fluid-checkout' ) ) ); ?></h3>
		<?php
	}





	/**
	 * Checkout Step: Shipping.
	 */


	
	/**
	 * Output step: Shipping.
	 */
	public function output_step_shipping() {
		// Bail if shipping not needed
		if ( ! WC()->cart->needs_shipping() ) { return; }

		$this->output_step_start_tag( apply_filters( 'wfc_shipping_step_title', __( 'Shipping', 'woocommerce-fluid-checkout' ) ), 'shipping' );
		
		echo '<div class="wfc-step__content" style="margin: 20px 0; padding: 5px 10px; background-color: #f3f3f3; text-align: center;">SHIPPING STEP</div>';

		// wc_get_template(
		// 	'checkout/form-shipping.php',
		// 	array(
		// 		'checkout'          => WC()->checkout(),
		// 	)
		// );

		// do_action( 'woocommerce_checkout_after_customer_details' );

		// echo $this->get_shipping_step_actions_html();

		$this->output_step_end_tag();
	}



	/**
	 * Output shipping step section title.
	 */
	public function output_shipping_step_section_title() {
		?>
		<h3 class="wfc-checkout-step-title"><?php echo esc_html( apply_filters( 'wfc_checkout_shipping_step_section_title', __( 'Shipping Address', 'woocommerce-fluid-checkout' ) ) ); ?></h3>
		<?php
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
	function get_shipping_methods_available() {
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
	 * Output shipping methods start tag.
	 */
	public function output_shipping_methods_start_tag() {
		?>
		<div class="shipping-method__packages">
			<h3><?php esc_html_e( 'Shipping Methods', 'woocommerce-fluid-checkout' ); ?></h3>
		<?php
	}

	/**
	 * Output shipping methods end tag.
	 */
	public function output_shipping_methods_end_tag() {
		?>
		</div>
		<?php
	}

	/**
	 * Add shipping methods as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	function add_checkout_shipping_methods_fragment( $fragments ) {
		$shipping_methods_html = $this->get_shipping_methods_available();
		$fragments['.shipping-method__packages'] = $shipping_methods_html;
		return $fragments;
	}

	/**
	 * Output shipping methods available.
	 *
	 * @access public
	 */
	function output_shipping_methods_available() {
		echo $this->get_shipping_methods_available();
	}

	/**
	 * Change shipping methods full label including price with markup necessary for displaying price as a separate element.
	 *
	 * @param object|string $method Either the name of the method's class, or an instance of the method's class.
	 * 
	 * @return string $label Shipping rate label.
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
	 * Output order additional fields.
	 */
	public function maybe_output_additional_fields_shipping_step() {
		// Bail if shipping not needed
		if ( ! WC()->cart->needs_shipping() ) { return; }

		$this->output_additional_fields();
	}

	/**
	 * Output order additional fields.
	 */
	public function maybe_output_additional_fields_payment_step() {
		// Bail if shipping is needed
		if ( WC()->cart->needs_shipping() ) { return; }

		$this->output_additional_fields();
	}





	/**
	 * Checkout Step: Payment.
	 */



	/**
	 * Output step: Payment.
	 */
	public function output_step_payment() {
		$this->output_step_start_tag( apply_filters( 'wfc_payment_step_title', __( 'Payment', 'woocommerce-fluid-checkout' ) ), 'payment' );

		echo '<div class="wfc-step__content" style="margin: 20px 0; padding: 5px 10px; background-color: #f3f3f3; text-align: center;">PAYMENT STEP</div>';
		
		// wc_get_template(
		// 	'checkout/form-payment.php',
		// 	array(
		// 		'checkout'          => WC()->checkout(),
		// 	)
		// );

		$this->output_step_end_tag();
	}



	/**
	 * Return html for payment step actions.
	 */
	public function get_payment_step_actions_html() {
		$actions_html = '<div class="wfc-actions"><button class="wfc-prev">' . _x( 'Back', 'Previous step button', 'woocommerce-fluid-checkout' ) . '</button></div>';
		return apply_filters( 'wfc_payment_step_actions_html', $actions_html, null );
	}

	/**
	 * Output payment step actions.
	 */
	public function output_payment_step_actions_html() {
		echo $this->get_payment_step_actions_html();
	}

	/**
	 * Output payment step section title.
	 */
	public function output_payment_step_section_title() {
		?>
		<h3 class="wfc-checkout-step-title"><?php echo esc_html( apply_filters( 'wfc_checkout_payment_step_section_title', __( 'Payment', 'woocommerce-fluid-checkout' ) ) ); ?></h3>
		<?php
	}

	/**
	 * Output billing step section title.
	 */
	public function output_billing_address_step_section_title() {
		?>
		<h3 class="wfc-checkout-step-title"><?php echo esc_html( apply_filters( 'wfc_checkout_billing_step_section_title', __( 'Billing Address', 'woocommerce-fluid-checkout' ) ) ); ?></h3>
		<?php
	}

	/**
	 * Output billing fields except those already added at contact step.
	 */
	public function output_billing_fields() {

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
	 * END - Checkout Steps.
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




	
	/**
	 * Register widget areas for the checkout pages.
	 */
	function register_checkout_widgets_areas() {
		register_sidebar( array(
			'name'			=> __( 'Order Review - Inside', 'woocommerce-fluid-checkout' ),
			'id'			=> 'wfc_order_review_inside',
			'description'	=> __( 'Display widgets on order review section at checkout.', 'woocommerce-fluid-checkout' ),
			'before_widget'	=> '<aside id="%1$s" class="widget %2$s">',
			'after_widget'	=> '</aside>',
			'before_title'	=> '<h5 class="widget-title">',
			'after_title'	=> '</h5>',
		) );

		register_sidebar( array(
			'name'			=> __( 'Order Review - After', 'woocommerce-fluid-checkout' ),
			'id'			=> 'wfc_order_review_outside',
			'description'	=> __( 'Display widgets after the order review section at checkout.', 'woocommerce-fluid-checkout' ),
			'before_widget'	=> '<aside id="%1$s" class="widget %2$s">',
			'after_widget'	=> '</aside>',
			'before_title'	=> '<h5 class="widget-title">',
			'after_title'	=> '</h5>',
		) );
	}

	/**
	 * Output widget area inside order review section.
	 */
	function output_sidebar_order_review_inside() {
		if ( is_active_sidebar( 'wfc_order_review_inside' ) ) :
			dynamic_sidebar( 'wfc_order_review_inside' );
		endif;
	}

	/**
	 * Output widget area outside order review section.
	 */
	function output_sidebar_order_review_outside() {
		if ( is_active_sidebar( 'wfc_order_review_outside' ) ) :
			dynamic_sidebar( 'wfc_order_review_outside' );
		endif;
	}



	/**
	 * Register widget areas for the cart pages.
	 */
	function register_cart_widgets_areas() {
		register_sidebar( array(
			'name'			=> __( 'Cart Totals - Inside', 'woocommerce-fluid-checkout' ),
			'id'			=> 'wfc_cart_totals_inside',
			'description'	=> __( 'Display widgets on cart totals section at checkout.', 'woocommerce-fluid-checkout' ),
			'before_widget'	=> '<aside id="%1$s" class="widget %2$s">',
			'after_widget'	=> '</aside>',
			'before_title'	=> '<h5 class="widget-title">',
			'after_title'	=> '</h5>',
		) );

		register_sidebar( array(
			'name'			=> __( 'Cart Totals - After', 'woocommerce-fluid-checkout' ),
			'id'			=> 'wfc_cart_totals_outside',
			'description'	=> __( 'Display widgets after the cart totals section at checkout.', 'woocommerce-fluid-checkout' ),
			'before_widget'	=> '<aside id="%1$s" class="widget %2$s">',
			'after_widget'	=> '</aside>',
			'before_title'	=> '<h5 class="widget-title">',
			'after_title'	=> '</h5>',
		) );
	}

	/**
	 * Output widget area inside cart totals section.
	 */
	function output_sidebar_cart_totals_inside() {
		if ( is_active_sidebar( 'wfc_cart_totals_inside' ) ) :
			dynamic_sidebar( 'wfc_cart_totals_inside' );
		endif;
	}

	/**
	 * Output widget area outside cart totals section.
	 */
	function output_sidebar_cart_totals_outside() {
		if ( is_active_sidebar( 'wfc_cart_totals_outside' ) ) :
			dynamic_sidebar( 'wfc_cart_totals_outside' );
		endif;
	}
	

}

FluidCheckout_Layout::instance();
