<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage the design templates.
 */
class FluidCheckout_DesignTemplates extends FluidCheckout {

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
		add_filter( 'body_class', array( $this, 'maybe_add_body_class' ), 10 );

		// Custom styles
		add_filter( 'wp_head', array( $this, 'maybe_output_custom_styles' ), 10 );
		add_filter( 'fc_output_custom_styles', array( $this, 'maybe_add_checkout_header_custom_styles' ), 10 );
		add_filter( 'fc_output_custom_styles', array( $this, 'maybe_add_checkout_page_custom_styles' ), 10 );
		add_filter( 'fc_output_custom_styles', array( $this, 'maybe_add_checkout_footer_custom_styles' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'maybe_add_css_variables_dark_mode' ), 5 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// General
		remove_filter( 'body_class', array( $this, 'maybe_add_body_class' ), 10 );

		// Custom styles
		remove_filter( 'wp_head', array( $this, 'maybe_output_custom_styles' ), 10 );
		remove_filter( 'fc_output_custom_styles', array( $this, 'maybe_add_checkout_header_custom_styles' ), 10 );
		remove_filter( 'fc_output_custom_styles', array( $this, 'maybe_add_checkout_page_custom_styles' ), 10 );
		remove_filter( 'fc_output_custom_styles', array( $this, 'maybe_add_checkout_footer_custom_styles' ), 10 );

		// CSS variables
		remove_action( 'fc_css_variables', array( $this, 'maybe_add_css_variables_dark_mode' ), 5 );
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Add design template class
		$add_classes = array(
			'has-fc-design-template--' . FluidCheckout_Settings::instance()->get_option( 'fc_design_template' ),
		);

		// Add dark mode class
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_enable_dark_mode_styles' ) ) {
			$add_classes[] = 'has-fc-dark-mode';
		}

		// Add custom button color class
		if ( $this->is_button_styles_enabled() ) {
			$add_classes[] = 'has-fc-button-colors';
		}

		return array_merge( $classes, $add_classes );
	}

	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function maybe_add_body_class( $classes ) {
		// Bail if not on affected pages.
		if (
			! function_exists( 'is_checkout' )
			|| (
				( ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) // Checkout page
				&& ! is_wc_endpoint_url( 'add-payment-method' ) // Add payment method page
				&& ! is_wc_endpoint_url( 'edit-address' ) // Edit address page
			)
		) { return $classes; }

		return $this->add_body_class( $classes );
	}



	/**
	 * Get the design template option arguments.
	 *
	 * @return  array  Design templates arguments.
	 */
	public function get_design_template_options() {
		return array(
			'classic'     => array( 'label' => __( 'Classic', 'fluid-checkout' ) ),
			'modern'      => array( 'label' => __( 'Modern', 'fluid-checkout' ), 'disabled' => true ),
			'minimalist'  => array( 'label' => __( 'Minimalist', 'fluid-checkout' ), 'disabled' => true ),
		);
	}

	/**
	 * Return the list of values accepted for design templates.
	 *
	 * @return  array  List of values accepted for design templates.
	 */
	public function get_allowed_design_templates() {
		return array_keys( $this->get_design_template_options() );
	}



	/**
	 * Check whether custom buttons styles are enabled for the page.
	 */
	public function is_button_styles_enabled() {
		// Bail if button styles not enabled
		if ( false === apply_filters( 'fc_apply_button_colors_styles', false ) ) { return false; }

		return true;
	}



	/**
	 * Get CSS variables styles.
	 */
	public function get_css_variables_styles() {
		// Get CSS variables
		$css_variables = apply_filters( 'fc_css_variables', array( ':root' => array() ) );

		// Bail if no scope for CSS variables
		if ( ! is_array( $css_variables ) || empty( $css_variables ) ) { return ''; }

		// Define return styles
		$css_variables_styles = '';

		// Iterate through CSS variables scopes
		foreach ( $css_variables as $scope => $properties ) {
			// Maybe skip empty scope
			if ( empty( $properties ) ) { continue; }
		
			// Transform array into string of CSS variables
			$css_variables_str = join( ';', array_map( function( $value, $key ) {
				return esc_attr( $key ) . ':' . esc_attr( $value );
			}, $properties, array_keys( $properties ) ) );

			// Define CSS variables styles
			$css_variables_styles .= $scope . ' {' . $css_variables_str . '}';
		}

		return $css_variables_styles;
	}

	/**
	 * Merge CSS variables within the scopes.
	 */
	public function merge_css_variables( $css_variables, $css_variables_to_merge ) {
		// Start with CSS variables
		$merged_css_variables = $css_variables;

		// Iterate through scopes CSS variables to merge
		foreach ( $css_variables_to_merge as $scope => $properties ) {
			// Maybe skip empty scope
			if ( empty( $properties ) ) { continue; }

			// Maybe create scope if not defined
			if ( ! array_key_exists( $scope, $merged_css_variables ) ) {
				$merged_css_variables[ $scope ] = array();
			}

			// Merge properties of the scope
			$merged_css_variables[ $scope ] = array_merge( $merged_css_variables[ $scope ], $properties );
		}

		return $merged_css_variables;
	}



	/**
	 * Output custom styles to the checkout page.
	 */
	public function output_custom_styles() {
		// Get styles
		$custom_styles = apply_filters( 'fc_output_custom_styles', '' );

		// Prepend CSS variables
		$custom_styles = $this->get_css_variables_styles() . $custom_styles . "\n";

		// Bail if styles are empty
		if ( empty( $custom_styles ) ) { return; }

		echo '<style id="fc-custom-styles">' . sanitize_text_field( $custom_styles ) . '</style>';
	}

	/**
	 * Maybe output custom styles to the checkout page.
	 */
	public function maybe_output_custom_styles() {
		// Bail if not on affected pages.
		if (
			! function_exists( 'is_checkout' )
			|| (
				( ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) // Checkout page
				&& ! is_wc_endpoint_url( 'add-payment-method' ) // Add payment method page
				&& ! is_wc_endpoint_url( 'edit-address' ) // Edit address page
			)
		) { return; }

		$this->output_custom_styles();
	}



	/**
	 * Add the custom styles for the checkout header background color.
	 */
	public function add_checkout_header_custom_styles( $custom_styles ) {		
		// Get header background color
		$header_background_color = trim( FluidCheckout_Settings::instance()->get_option( 'fc_checkout_header_background_color' ) );

		// Bail if color is empty
		if ( empty( $header_background_color ) ) { return $custom_styles; }

		// TODO: Use CSS variables to change color
		$custom_styles .= 'header.fc-checkout-header{background-color:'. esc_attr( $header_background_color ) .'}';

		return $custom_styles;
	}

	/**
	 * Add the custom styles for the checkout page background color.
	 */
	public function add_checkout_page_custom_styles( $custom_styles ) {		
		// Get header background color
		$page_background_color = trim( FluidCheckout_Settings::instance()->get_option( 'fc_checkout_page_background_color' ) );

		// Bail if color is empty
		if ( empty( $page_background_color ) ) { return $custom_styles; }

		// TODO: Use CSS variables to change color
		$custom_styles .= 'body.has-fluid-checkout{background-color:'. esc_attr( $page_background_color ) .'}';

		return $custom_styles;
	}

	/**
	 * Add the custom styles for the checkout footer background color.
	 */
	public function add_checkout_footer_custom_styles( $custom_styles ) {
		// Get footer background color
		$footer_background_color = trim( FluidCheckout_Settings::instance()->get_option( 'fc_checkout_footer_background_color' ) );

		// Bail if color is empty
		if ( empty( $footer_background_color ) ) { return $custom_styles; }

		// TODO: Use CSS variables to change color
		$custom_styles .= 'footer.fc-checkout-footer{background-color:'. esc_attr( $footer_background_color ) .'}';

		return $custom_styles;
	}



	/**
	 * Maybe add the custom styles for the checkout header background color.
	 */
	public function maybe_add_checkout_header_custom_styles( $custom_styles ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $custom_styles; }

		return $this->add_checkout_header_custom_styles( $custom_styles );
	}

	/**
	 * Maybe add the custom styles for the checkout page background color.
	 */
	public function maybe_add_checkout_page_custom_styles( $custom_styles ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $custom_styles; }

		return $this->add_checkout_page_custom_styles( $custom_styles );
	}

	/**
	 * Maybe add the custom styles for the checkout footer background color.
	 */
	public function maybe_add_checkout_footer_custom_styles( $custom_styles ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return $custom_styles; }

		return $this->add_checkout_footer_custom_styles( $custom_styles );
	}


	
	/**
	 * Get the CSS variables for dark mode.
	 */
	public function get_css_variables_dark_mode() {
		return array(
			'--fluidcheckout--color--black'             => '#fff',
			'--fluidcheckout--color--darker-grey'       => '#f3f3f3',
			'--fluidcheckout--color--dark-grey'         => '#d8d8d8',
			'--fluidcheckout--color--grey'              => '#7b7575',
			'--fluidcheckout--color--light-grey'        => '#323234',
			'--fluidcheckout--color--lighter-grey'      => '#28282a',
			'--fluidcheckout--color--white'             => '#000',

			'--fluidcheckout--color--success'           => '#00cc66',
			'--fluidcheckout--color--error'             => '#ec5b5b',
			'--fluidcheckout--color--alert'             => '#ff781f',
			'--fluidcheckout--color--info'              => '#2184fd',

			'--fluidcheckout--shadow-color--darker'     => 'rgba( 255, 255, 255, .30 )',
			'--fluidcheckout--shadow-color--dark'       => 'rgba( 255, 255, 255, .15 )',
			'--fluidcheckout--shadow-color--light'      => 'rgba( 0, 0, 0, .15 )',
		);
	}

	/**
	 * Maybe add CSS variables for dark mode.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function maybe_add_css_variables_dark_mode( $css_variables ) {
		// Bail if dark mode is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_enable_dark_mode_styles' ) ) { return $css_variables; }

		return array_merge( $css_variables, array( ':root' => $this->get_css_variables_dark_mode() ) );
	}

}

FluidCheckout_DesignTemplates::instance();
