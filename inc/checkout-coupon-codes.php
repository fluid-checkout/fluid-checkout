<?php

/**
 * Feature for adding coupon codes or gift certificate codes to checkout
 */
class FluidCheckout_CouponCodes extends FluidCheckout {

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
		// Body Class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Checkout Coupon Notice
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		// Coupon Code Substep
		add_action( 'wfc_output_step_payment', array( $this, 'output_substep_coupon_codes' ), 10 );
		// add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_coupon_codes_text_fragment' ), 10 );
	}



	/**
	 * Return Checkout Steps class instance.
	 */
	public function checkout_steps() {
		return FluidCheckout_Steps::instance();
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param   array  $classes  Body classes array.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() ){ return $classes; }

		return array_merge( $classes, array( 'has-wfc-coupon-code-fields' ) );
	}



	/**
	 * Output coupon codes substep.
	 *
	 * @param   string  $step_id     Id of the step in which the substep will be rendered.
	 */
	public function output_substep_coupon_codes( $step_id ) {
		$substep_id = 'coupon_codes';
		$substep_title = apply_filters( 'wfc_substep_coupon_codes_title_display', false ) === true ? apply_filters( 'wfc_substep_coupon_codes_title', __( 'Coupon code', 'woocommerce-fluid-checkout' ) ) : null;
		$this->checkout_steps()->output_substep_start_tag( $step_id, $substep_id, $substep_title );

		$this->output_substep_text_coupon_codes();

		$this->checkout_steps()->output_substep_fields_start_tag( $step_id, $substep_id, false );
		$this->output_substep_coupon_codes_fields();
		$this->checkout_steps()->output_substep_fields_end_tag();		

		$this->checkout_steps()->output_substep_end_tag( $step_id, $substep_id, false );
	}



	/**
	 * Output coupon codes fields.
	 */
	public function output_substep_coupon_codes_fields() {
		$coupon_code_field_label = apply_filters( 'wfc_coupon_code_field_label', __( 'Coupon code', 'woocommerce-fluid-checkout' ) );
		$coupon_code_field_placeholder = apply_filters( 'wfc_coupon_code_field_placeholder', __( 'Enter your code here', 'woocommerce-fluid-checkout' ) );
		$coupon_code_button_label = apply_filters( 'wfc_coupon_code_button_label', _x( 'Apply code', 'Button label for applying coupon codes', 'woocommerce-fluid-checkout' ) );

		$this->checkout_steps()->output_expansible_form_section_start_tag( 'coupon_code', apply_filters( 'wfc_coupon_code_expansible_section_toggle_label', __( 'Add coupon code', 'woocommerce-fluid-checkout' ) ) );
		?>
			<input type="text" class="input-text" name="wfc-couponcode" id="wfc-couponcode" aria-label="<?php echo esc_attr( $coupon_code_field_label ); ?>" placeholder="<?php echo esc_attr( $coupon_code_field_placeholder ); ?>">
			<button type="button" class="wfc-step__substep-coupon-codes-save" data-step-save><?php echo $coupon_code_button_label; ?></button>
		<?php
		$this->checkout_steps()->output_expansible_form_section_end_tag();
	}

	/**
	 * Get coupon codes fields.
	 */
	public function get_substep_coupon_codes_fields() {
		ob_start();
		$this->output_substep_coupon_codes_fields();
		return ob_get_clean();
	}



	/**
	 * Get coupon codes substep added coupon codes.
	 */
	public function get_substep_text_coupon_codes() {
		$html = '<div class="wfc-step__substep-text-content wfc-step__substep-text-content--coupon-codes">';

		// TODO: Display coupons added to the cart, with code value (ie. "CODE-10-OFF"), value of the discount, and option to remove the coupon from the cart/order
		
		$html .= '</div>';

		return apply_filters( 'wfc_substep_coupon_codes_text', $html );
	}

	/**
	 * Add coupon codes text format as checkout fragment.
	 * 
	 * @param array $fragments Checkout fragments.
	 */
	public function add_coupon_codes_text_fragment( $fragments ) {
		$html = $this->get_substep_text_coupon_codes();
		$fragments['.wfc-step__substep-text-content--coupon-codes'] = $html;
		return $fragments;
	}

	/**
	 * Output coupon codes substep in text format for when the step is completed.
	 */
	public function output_substep_text_coupon_codes() {
		echo $this->get_substep_text_coupon_codes();
	}

}

FluidCheckout_CouponCodes::instance();
