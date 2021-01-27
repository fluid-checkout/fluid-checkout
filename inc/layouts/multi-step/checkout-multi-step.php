<?php
/**
 * Checkout steps layout: Multi Step
 */
class FluidCheckoutLayout_MultiStep extends FluidCheckout {

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
		
		// Template loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 20, 3 );

		// Steps display order
		add_action( 'wfc_checkout_before', array( $this, 'output_checkout_progress_bar' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_billing' ), 10 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_shipping' ), 50 );
		add_action( 'wfc_checkout_steps', array( $this, 'output_step_payment' ), 100 );

		// Billing
		add_action( 'wfc_checkout_before_step_billing_fields', array( $this, 'output_billing_step_section_title' ), 10 );

		// Shipping
		add_action( 'wfc_checkout_before_step_shipping_fields', array( $this, 'output_shipping_step_section_title' ), 10 );
		add_action( 'wfc_before_checkout_shipping_address_wrapper', array( $this, 'output_ship_to_different_address_checkbox' ), 10 );
		add_action( 'wfc_cart_totals_shipping', array( $this, 'output_cart_totals_shipping_section' ), 10 );

		// Additional Information
		add_action( 'wfc_checkout_after_step_shipping_fields', array( $this, 'maybe_output_additional_fields_shipping_step' ), 50 );
		add_action( 'wfc_checkout_after_step_payment_fields', array( $this, 'maybe_output_additional_fields_payment_step' ), 50 );

		// Payment
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_checkout_before_step_payment_fields', array( $this, 'output_order_review' ), 5 );
		add_action( 'wfc_checkout_before_step_payment_fields', array( $this, 'output_payment_step_section_title' ), 10 );
		add_action( 'wfc_checkout_payment', 'woocommerce_checkout_payment', 20 );
		add_action( 'wfc_checkout_after_step_payment_fields', array( $this, 'output_checkout_place_order' ), 100 );
		add_filter( 'woocommerce_order_button_html', array( $this, 'get_payment_step_actions_html' ), 20 );

		// Theme fixes
		add_action( 'wp_footer', array( $this, 'maybe_add_theme_inline_code' ), 10 );
		
	}



	/**
	 * Add page body class for feature detection.
	 */
	public function add_body_class( $classes ) {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return $classes; }
		
		return array_merge( $classes, array( 'has-wfc-checkout-layout', 'has-wfc-checkout-layout--multi-step' ) );
	}



	/**
	 * Enqueue scripts
	 */
	public function enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ){ return; }

		// Styles
		wp_enqueue_style( 'wfc-checkout-layout--multi-step', self::$directory_url . 'css/checkout-multi-step'. self::$asset_version . '.css', NULL, NULL );
		wp_enqueue_style( 'wfc-progress-bar', self::$directory_url . 'css/checkout-progress-bar--'.get_option( 'wfc_checkout_progress_bar_layout', 'default' ). self::$asset_version . '.css', NULL, NULL );
		
		// Scripts
		wp_enqueue_script( 'wfc-checkout-steps', self::$directory_url . 'js/checkout-steps'. self::$asset_version . '.js', NULL, NULL, true );
		wp_add_inline_script( 'wfc-checkout-steps', 'window.addEventListener("load",function(){CheckoutSteps.init();})' );
	}




	/**
	 * Locate template files from this checkout layout.
	 * 
	 * @since 1.1.0
	 * 
	 * @param   string  $template       Template filename.
	 * @param   string  $template_name  Template name.
	 * @param   string  $template_path  Template path.
	 */
	public function locate_template( $template, $template_name, $template_path ) {
	 
		global $woocommerce;
	 
		$_template = $template;
	 
		if ( ! $template_path ) $template_path = $woocommerce->template_url;
	 
		// Get plugin path
		$plugin_path  = self::$directory_path . 'inc/layouts/multi-step/templates/';
	 
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
	 * Output the checkout progress bar.
	 */
	public function output_checkout_progress_bar() {
		?>
		<div class="wfc-checkout-progress-bar wfc-row wfc-header">
			<div id="wfc-progressbar"><?php echo apply_filters( 'wfc_progressbar_steps_placeholder', '<div class="wfc-progress-bar-step current"></div><div class="wfc-progress-bar-step"></div><div class="wfc-progress-bar-step"></div>' ); ?></div>
		</div>
		<?php
	}



	/**
	 * Output step: Billing.
	 */
	public function output_step_billing() {
		$this->output_step_start_tag( apply_filters( 'wfc_billing_step_title', __( 'Billing', 'woocommerce-fluid-checkout' ) ), 'billing' );
		do_action( 'woocommerce_checkout_before_customer_details' );

		wc_get_template(
			'checkout/form-billing.php',
			array(
				'checkout'          => WC()->checkout(),
			)
		);

		echo $this->get_billing_step_actions_html();
		$this->output_step_end_tag();
	}



	/**
	 * Output billing step section title.
	 */
	public function output_billing_step_section_title() {
		?>
		<h3 class="wfc-checkout-step-title"><?php echo esc_html( apply_filters( 'wfc_checkout_billing_step_section_title', __( 'Billing', 'woocommerce-fluid-checkout' ) ) ); ?></h3>
		<?php
	}



	/**
	 * Output step: Shipping.
	 */
	public function output_step_shipping() {
		// Bail if shipping not needed
		if ( ! WC()->cart->needs_shipping() ) { return; }

		$this->output_step_start_tag( apply_filters( 'wfc_shipping_step_title', __( 'Shipping', 'woocommerce-fluid-checkout' ) ), 'shipping' );

		wc_get_template(
			'checkout/form-shipping.php',
			array(
				'checkout'          => WC()->checkout(),
			)
		);

		do_action( 'woocommerce_checkout_after_customer_details' );

		echo $this->get_shipping_step_actions_html();
		$this->output_step_end_tag();
	}

	
	
	/**
	 * Output "ship to different address" checkbox.
	 */
	public function output_ship_to_different_address_checkbox() {
		?>
		<label id="ship-to-different-address" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1 ); ?> type="checkbox" name="ship_to_different_address" value="1" /> <span><?php esc_html_e( 'Ship to a different address?', 'woocommerce' ); ?></span>
		</label>
		<?php
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
	 * Output shipping section for cart totals.
	 */
	public function output_cart_totals_shipping_section() {
		wc_get_template(
			'cart/cart-totals-shipping.php'
		);
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
	 * Output step: Payment.
	 */
	public function output_step_payment() {
		$this->output_step_start_tag( apply_filters( 'wfc_payment_step_title', __( 'Payment', 'woocommerce-fluid-checkout' ) ), 'payment' );
		
		wc_get_template(
			'checkout/form-payment.php',
			array(
				'checkout'          => WC()->checkout(),
			)
		);

		$this->output_step_end_tag();
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
	 * Return html for billing step actions.
	 */
	public function get_billing_step_actions_html() {
		$proceed_button_text = __( 'Proceed to Shipping', 'woocommerce-fluid-checkout' );
		if ( ! WC()->cart->needs_shipping() ) { $proceed_button_text = __( 'Proceed to Payment', 'woocommerce-fluid-checkout' ); }

		$actions_html = '<div class="wfc-actions"><button class="wfc-next button alt">' . $proceed_button_text . '</button></div>';
		return apply_filters( 'wfc_billing_step_actions_html', $actions_html );
	}



	/**
	 * Return html for shipping step actions
	 */
	public function get_shipping_step_actions_html() {
		$actions_html = '<div class="wfc-actions"><button class="wfc-prev">' . _x( 'Back', 'Previous step button', 'woocommerce-fluid-checkout' ) . '</button> <button class="wfc-next button alt">' . __( 'Proceed to Payment', 'woocommerce-fluid-checkout' ) . '</button></div>';
		return apply_filters( 'wfc_shipping_step_actions_html', $actions_html );
	}



	/**
	 * Add back button html to place order button on checkout.
	 * @param [String] $button_html Place Order button html.
	 */
	public function get_payment_step_actions_html( $button_html ) {
		$actions_html = '<div class="wfc-actions"><button class="wfc-prev">' . _x( 'Back', 'Previous step button', 'woocommerce-fluid-checkout' ) . '</button> ' . $button_html . '</div>';
		return apply_filters( 'wfc_payment_step_actions_html', $actions_html, $button_html );
	}







	/**
	 * Theme issues fixes
	 */



	/**
	 * Maybe call function to add styles for specific themes when active
	 */
	public function maybe_add_theme_inline_code() {
		// Bail if not on checkout page
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) return;

		$current_theme = wp_get_theme();
		$theme_slug = sanitize_title( $current_theme->get( 'Name' ) );
		$methodName = 'add_theme_inline_code_'.$theme_slug;
		if ( method_exists( $this, $methodName ) ) {
			$this->{$methodName}();
		}
	}



	/**
	 * Add inline styles to fix issues with Storefront theme
	 */
	public function add_theme_inline_code_storefront() {
		?>
		<style type="text/css">
			/**
			* Styles for Storefront theme
			*/
			.place-order {
				padding: 1.41575em;
				background-color: #fafafa;
			}

			.place-order .button {
				font-size: 1.41575em;
				width: 100%;
				white-space: pre-wrap;
			}
			/** / Styles for Storefront theme */
		</style>
		<?php
	}


	/**
	 * END - Theme issues fixes
	 */

}

FluidCheckoutLayout_MultiStep::instance();
