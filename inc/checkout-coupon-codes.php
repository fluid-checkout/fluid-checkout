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
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_coupon_codes_text_fragment' ), 10 );

		// Apply coupon code
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'maybe_apply_coupon_code' ), 10 );
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

		$coupon_code_field_args = array(
			'required'           => false,
			'class'              => array( 'form-row-first' ),
			'placeholder'        => $coupon_code_field_placeholder,
			'custom_attributes'  => array(
				'aria-label'     => $coupon_code_field_label,
				'data-autofocus' => true,
			),
		);

		$this->checkout_steps()->output_expansible_form_section_start_tag( 'coupon_code', apply_filters( 'wfc_coupon_code_expansible_section_toggle_label', __( 'Add coupon code', 'woocommerce-fluid-checkout' ) ) );
		
		woocommerce_form_field( 'coupon_code', $coupon_code_field_args );
		?>
		<button type="button" class="wfc-step__substep-coupon-codes-save" data-apply-coupon-button><?php echo $coupon_code_button_label; ?></button>
		<input type="hidden" name="apply_coupon_code" id="apply_coupon_code">
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
		ob_start();

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) :
			// Get coupon label with changed "remove" link
			ob_start();
			wc_cart_totals_coupon_html( $coupon );
			$coupon_html = str_replace( __( '[Remove]', 'woocommerce' ), __( 'Remove', 'woocommerce-fluid-checkout' ), ob_get_clean() );
			?>
			<div class="wfc-coupon-codes__coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<span class="wfc-coupon-codes__coupon-code"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
				<span class="wfc-coupon-codes__coupon-amount"><?php echo $coupon_html; ?></span>
			</div>
			<?php
		endforeach;

		$html .= ob_get_clean();
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



	/**
	 * Apply a coupon code when updating the checkout page, if a value is provided.
	 * 
	 * @param array $posted_data Post data for all checkout fields.
	 */
	public function maybe_apply_coupon_code( $posted_data ) {
		// Get parsed posted data
		$parsed_posted_data = $this->get_parsed_posted_data();
		
		// If a coupon code was entered, try to apply it
		if ( array_key_exists( 'apply_coupon_code', $parsed_posted_data ) && $parsed_posted_data[ 'apply_coupon_code' ] === '1' && array_key_exists( 'coupon_code', $parsed_posted_data ) && ! empty( $parsed_posted_data[ 'coupon_code' ] ) ) {
			$coupon_code = wc_format_coupon_code( wp_unslash( $parsed_posted_data[ 'coupon_code' ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			WC()->cart->add_discount( $coupon_code );
		}
		
		return $posted_data;
	}

}

FluidCheckout_CouponCodes::instance();
