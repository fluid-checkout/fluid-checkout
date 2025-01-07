<?php
defined( 'ABSPATH' ) || exit;

/**
 * Feature for adding coupon codes to checkout.
 */
class FluidCheckout_CouponCodes extends FluidCheckout {

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
		// Bail if feature is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_enable_checkout_coupon_codes' ) ) { return false; }

		return true;
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Bail if use of coupons not enabled
		if ( ! wc_coupons_enabled() ) { return; }

		// Body Class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Optional fields
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields_coupon_code' ), 10 );

		// Actions
		add_action( 'wc_ajax_fc_add_coupon_code', array( $this, 'add_coupon_code' ), 10 );
		add_action( 'wc_ajax_fc_remove_coupon_code', array( $this, 'remove_coupon_code' ), 10 );

		// Integrated coupon code section at checkout
		if ( $this->is_feature_enabled() ) {
			// Checkout coupon notice
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}

		// Substep
		$this->maybe_register_substep_hooks();
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Substep
		$this->maybe_register_substep();
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Bail if use of coupons not enabled
		if ( ! wc_coupons_enabled() ) { return; }

		// Body Class
		remove_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Enqueue
		remove_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		remove_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Prevent hiding coupon code field behind a link button, as it is implemented directly
		remove_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields_coupon_code' ), 10 );

		// Actions
		remove_action( 'wc_ajax_fc_add_coupon_code', array( $this, 'add_coupon_code' ), 10 );
		remove_action( 'wc_ajax_fc_remove_coupon_code', array( $this, 'remove_coupon_code' ), 10 );

		// Integrated coupon code section at checkout
		if ( $this->is_feature_enabled() ) {
			// Checkout coupon notice
			add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

			// Coupon code substep
			$this->unregister_substep();
		}
	}



	/**
	 * Get the step id for where to display the coupon codes substep.
	 */
	public function get_substep_step_id() {
		return apply_filters( 'fc_coupon_code_substep_step_id', 'payment' );
	}

	/**
	 * Get the priority for the coupon codes substep.
	 */
	public function get_substep_priority() {
		return apply_filters( 'fc_coupon_code_substep_priority', 10 );
	}

	/**
	 * Maybe register the substep.
	 */
	public function maybe_register_substep() {
		// Bail if feature is not enabled
		if ( ! $this->is_feature_enabled() ) { return; }

		// Maybe bail if coupon codes is not to be displayed as a substep
		if ( false === apply_filters( 'fc_coupon_code_displayed_as_substep', true ) ) { return; }

		// Get variables for the substep
		$step_id = $this->get_substep_step_id();
		$substep_priority = $this->get_substep_priority();
		$substep_id = 'coupon_codes';
		$substep_title = 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_display_coupon_code_section_title' ) ? apply_filters( 'fc_substep_coupon_codes_section_title', __( 'Coupon code', 'woocommerce' ) ) : null; // Intentionally using text domain from WooCommerce

		// Register substep
		FluidCheckout_Steps::instance()->register_checkout_substep( $step_id, array(
			'substep_id' => $substep_id,
			'substep_title' => $substep_title,
			'priority' => $substep_priority,
			'render_fields_callback' => array( $this, 'output_substep_coupon_codes_fields' ),
			'render_review_text_callback' => null,
		) );
	}

	/**
	 * Maybe register the substep hooks.
	 */
	public function maybe_register_substep_hooks() {
		// Bail if feature is not enabled
		if ( ! $this->is_feature_enabled() ) { return; }

		// Auxiliary sections
		add_action( 'fc_before_substep_coupon_codes', array( $this, 'output_substep_coupon_codes_auxiliary_sections' ), 10, 2 );

		// Fragments
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_coupon_codes_text_fragment' ), 10 );
	}



	/**
	 * Unregister the coupon codes substep and add related hooks.
	 */
	public function unregister_substep() {
		// Get variables for the substep
		$substep_id = 'coupon_codes';

		// Unregister substep
		FluidCheckout_Steps::instance()->unregister_checkout_substep( $substep_id );

		// Auxiliary sections
		remove_action( 'fc_before_substep_coupon_codes', array( $this, 'output_substep_coupon_codes_auxiliary_sections' ), 10, 2 );

		// Fragments
		remove_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_coupon_codes_text_fragment' ), 10 );
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param   array  $classes  Body classes array.
	 */
	public function add_body_class( $classes ) {
		// Bail if not at checkout
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $classes; }

		return array_merge( $classes, array( 'has-fc-coupon-code-fields' ) );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-checkout-coupons', FluidCheckout_Enqueue::instance()->get_script_url( 'js/checkout-coupons' ), array( 'jquery', 'fc-utils', 'fc-collapsible-block' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-coupons', 'window.addEventListener("load",function(){CheckoutCoupons.init(fcSettings.checkoutCoupons);})' );

		// Styles
		wp_register_style( 'fc-checkout-coupons', FluidCheckout_Enqueue::instance()->get_style_url( 'css/checkout-coupons' ), NULL, NULL );
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'fc-checkout-coupons' );
		wp_enqueue_style( 'fc-checkout-coupons' );
	}

	/**
	 * Maybe enqueue assets on the checkout page.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Bail if coupon feature is not enabled
		if ( ! $this->is_feature_enabled() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {

		$settings[ 'checkoutCoupons' ] = apply_filters( 'fc_checkout_coupons_script_settings', array(
			'isEnabled'                => $this->is_feature_enabled() ? 'yes' : 'no',
			'addCouponCodeNonce'       => wp_create_nonce( 'fc-add-coupon-code' ),
			'removeCouponCodeNonce'    => wp_create_nonce( 'fc-remove-coupon-code' ),
		) );

		return $settings;
	}




	/**
	 * Output coupon codes fields.
	 *
	 * @param   array  $field_args               The coupon code field arguments. Will use default values if attributes are not passed in.
	 * @param   array  $expansible_section_args  The attributes for the coupon code expansible section. Will use default values if attributes are not passed in.
	 */
	public function output_section_coupon_codes_fields( $field_args = array(), $expansible_section_args = array(), $output_handle = true, $section_key = null ) {
		// Define labels
		$coupon_code_field_label       = apply_filters( 'fc_coupon_code_field_label', __( 'Coupon code', 'woocommerce' ) ); // Intentionally using text domain from WooCommerce
		$coupon_code_field_description = apply_filters( 'fc_coupon_code_field_description', '' );
		$coupon_code_field_placeholder = apply_filters( 'fc_coupon_code_field_placeholder', __( 'Enter your code here', 'fluid-checkout' ) );
		$coupon_code_button_label      = apply_filters( 'fc_coupon_code_button_label', _x( 'Apply', 'Button label for applying coupon codes', 'fluid-checkout' ) );

		// Maybe define section key
		$section_id = 'coupon_code';
		if ( empty( $section_key ) ) {
			$section_key = $section_id . '--' . rand();
		}

		// Coupon code field args
		$field_key = 'coupon_code';
		$coupon_code_field_args = array_merge( array(
			'required'                   => false,
			'fc_skip_server_validation'  => true,
			'class'                      => array( 'form-row-wide', 'fc-no-validation-icon' ),
			'placeholder'                => $coupon_code_field_placeholder,
			'description'                => $coupon_code_field_description,
			'custom_attributes'          => array(
				'aria-label'             => $coupon_code_field_label,
				'data-autofocus'         => true,
			),
		), $field_args );

		// Expansible section args
		$coupon_code_expansible_args = array_merge( array(
			'initial_state' => true === apply_filters( 'fc_coupon_code_field_initially_expanded', false ) ? 'expanded' : 'collapsed',
			'section_attributes' => array(
				'class' => 'fc-coupon_code__collapsible',
			),
		), $expansible_section_args );

		// Define toggle label
		$coupon_code_toggle_label = null;
		if ( $output_handle ) {
			// Output coupon code field and button in an expansible form section
			$coupon_code_toggle_label = 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_optional_fields_link_label_lowercase' ) ? strtolower( $coupon_code_field_label ) : $coupon_code_field_label;
			/* translators: %s: Form field label */
			$coupon_code_toggle_label = apply_filters( 'fc_expansible_section_toggle_label_' . $section_id, sprintf( __( 'Add %s', 'fluid-checkout' ), $coupon_code_toggle_label ) );
		}

		// Output section
		FluidCheckout_Steps::instance()->output_expansible_form_section_start_tag( $section_key, $coupon_code_toggle_label, $coupon_code_expansible_args );

		do_action( 'fc_coupon_code_section_before' );

		?>
		<div class="fc-coupon-code-section">
			<?php woocommerce_form_field( $field_key, $coupon_code_field_args ); ?>
			<button type="button" class="fc-coupon-code__apply <?php echo esc_attr( apply_filters( 'fc_coupon_code_apply_button_classes', 'button' ) ); ?>" data-apply-coupon-button><?php echo esc_html( $coupon_code_button_label ); ?></button>
		</div>
		<?php

		do_action( 'fc_coupon_code_section_after' );

		FluidCheckout_Steps::instance()->output_expansible_form_section_end_tag();
	}

	/**
	 * Output coupon codes fields section for the substep.
	 * 
	 * @param   string  $step_id      Id of the step in which the substep will be rendered.
	 * @param   string  $substep_id   Id of the substep in which the fields will be rendered.
	 */
	public function output_substep_coupon_codes_fields( $step_id = 'payment', $substep_id = 'coupon_codes' ) {
		$this->output_section_coupon_codes_fields();
	}

	/**
	 * Output coupon codes substep auxiliary sections for the substep.
	 * 
	 * @param   string  $step_id      Id of the step in which the substep will be rendered.
	 * @param   string  $substep_id   Id of the substep in which the fields will be rendered.
	 */
	public function output_substep_coupon_codes_auxiliary_sections( $step_id, $substep_id ) {
		$this->output_coupon_codes_messages_container();
		$this->output_substep_text_coupon_codes();
	}



	/**
	 * Prevent hiding optional coupon code field behind a link button, as it is implemented directly.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields_coupon_code( $skip_list ) {
		$skip_list[] = 'coupon_code';
		return $skip_list;
	}



	/**
	 * Get coupon codes substep added coupon codes.
	 */
	public function get_substep_text_coupon_codes() {
		$html = '<div class="fc-step__substep-text-content fc-step__substep-text-content--coupon-codes">';
		ob_start();

		do_action( 'fc_substep_coupon_codes_text_before' );

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) :
			// Get coupon label with changed "remove" link
			ob_start();
			wc_cart_totals_coupon_html( $coupon );
			$coupon_html_esc = ob_get_clean();

			// Change the "remove" link text
			$coupon_html_esc = str_replace( esc_html( __( '[Remove]', 'woocommerce' ) ), esc_html( __( 'Remove', 'fluid-checkout' ) ), $coupon_html_esc );
			?>
			<?php // The function `sanitize_title` is used below to convert the string into a CSS-class-like string ?>
			<div class="fc-coupon-codes__coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<strong class="fc-coupon-codes__coupon-code"><?php wc_cart_totals_coupon_label( $coupon ); ?></strong>
				<span class="fc-coupon-codes__coupon-amount"><?php echo $coupon_html_esc; // WPCS: XSS ok. ?></span>
			</div>
			<?php
		endforeach;

		do_action( 'fc_substep_coupon_codes_text_after' );

		$html .= ob_get_clean();
		$html .= '</div>';

		return apply_filters( 'fc_substep_coupon_codes_text', $html );
	}

	/**
	 * Add coupon codes substep review text as checkout fragment.
	 *
	 * @param array $fragments Checkout fragments.
	 */
	public function add_coupon_codes_text_fragment( $fragments ) {
		$html = $this->get_substep_text_coupon_codes();
		$fragments['.fc-step__substep-text-content--coupon-codes'] = $html;
		return $fragments;
	}

	/**
	 * Output coupon codes substep review text.
	 */
	public function output_substep_text_coupon_codes() {
		echo $this->get_substep_text_coupon_codes();
	}

	/**
	 * Output the coupon codes messages container element.
	 */
	public function output_coupon_codes_messages_container() {
		echo '<div class="fc-coupon-code-messages"></div>';
	}



	/**
	 * AJAX Add a coupon code to the cart.
	 * @see WC_AJAX::apply_coupon
	 */
	public function add_coupon_code() {
		check_ajax_referer( 'fc-add-coupon-code', 'security' );

		$coupon_code = sanitize_text_field( wp_unslash( $_REQUEST['coupon_code'] ) );
		$reference_id = sanitize_text_field( wp_unslash( $_REQUEST['reference_id'] ) );

		if ( ! empty( $coupon_code ) ) {
			// Add the coupon code to the cart,
			// which triggers calculating the cart totals to ensure pricing is correct.
			WC()->cart->add_discount( wc_format_coupon_code( $coupon_code ) );

			// Intercept notices to avoid them being displayed on other pages
			ob_start();
			wc_print_notices();
			$message = ob_get_clean();
			
			wp_send_json(
				array(
					'result'           => false === strpos( $message, 'woocommerce-error' ) ? 'success' : 'error',
					'coupon_code'      => $coupon_code,
					'reference_id'     => $reference_id,
					'message'          => $message,
				)
			);
		}
		else {
			wp_send_json(
				array(
					'result'           => 'error',
					'coupon_code'      => $coupon_code,
					'reference_id'     => $reference_id,
					'error_slug'       => 'coupon_code_does_not_exist',
					'message'          => WC_Coupon::get_generic_coupon_error( WC_Coupon::E_WC_COUPON_PLEASE_ENTER ),
				)
			);
		}
	}

	/**
	 * AJAX Remove a coupon code from the cart.
	 * @see WC_AJAX::remove_coupon
	 */
	public function remove_coupon_code() {
		check_ajax_referer( 'fc-remove-coupon-code', 'security' );

		$coupon_code = sanitize_text_field( wp_unslash( $_REQUEST['coupon_code'] ) );
		$reference_id = sanitize_text_field( wp_unslash( $_REQUEST['reference_id'] ) );

		if ( ! empty( $coupon_code ) ) {
			WC()->cart->remove_coupon( wc_format_coupon_code( $coupon_code ) );
			wc_add_notice( __( 'Coupon has been removed.', 'woocommerce' ) );

			// Intercept notices to avoid them being displayed on other pages
			ob_start();
			wc_print_notices();
			$message = ob_get_clean();
			
			wp_send_json(
				array(
					'result'           => false === strpos( $message, 'woocommerce-error' ) ? 'success' : 'error',
					'coupon_code'      => $coupon_code,
					'reference_id'     => $reference_id,
					'message'          => $message,
				)
			);
		}
		else {
			wc_add_notice( __( 'Sorry there was a problem removing this coupon.', 'woocommerce' ), 'error' );

			// Intercept notices to avoid them being displayed on other pages
			ob_start();
			wc_print_notices();
			$message = ob_get_clean();

			wp_send_json(
				array(
					'result'           => 'error',
					'coupon_code'      => $coupon_code,
					'reference_id'     => $reference_id,
					'error_slug'       => 'coupon_code_not_provided',
					'message'          => $message,
				)
			);
		}
	}

}

FluidCheckout_CouponCodes::instance();
