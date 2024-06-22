<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Qode Framework (by Qode Interactive).
 */
class FluidCheckout_QodeFramework extends FluidCheckout {

	/**
	 * Button option values from Qode Framework.
	 *
	 * @var array
	 */
	private $button_option_values;

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Buttons
		add_filter( 'fc_apply_button_colors_styles', 'maybe_enable_fc_button_color_styles', 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'maybe_add_css_variables' ), 30 );

		// Fetch button option values
		add_action( 'wp', array( $this, 'fetch_button_option_values' ), 100 );
	}



	/**
	 * Maybe enable Fluid Checkout button color styles.
	 *
	 * @param  boolean  $enable  Whether to enable Fluid Checkout button color styles.
	 */
	public function maybe_enable_fc_button_color_styles( $enable ) {
		// Enable button color styles if at least one button option value is set
		if ( ! empty( $this->button_option_values ) ) {
			$enable = true;
		}

		return $enable;
	}



	/**
	 * Fetch button option values from Qode Framework.
	 */
	public function fetch_button_option_values() {
		// Bail if plugin function is not available
		if ( ! function_exists( 'qode_framework_get_post_value_through_levels' ) ) { return; }

		// Get core plugin options name to use as a scope in a global function from Qode Framework
		$scope = $this->get_core_plugin_options_name();

		// Bail if scope is empty
		if ( ! $scope ) { return; }

		// Option names to fetch
		$option_names = array( 
			'qodef_elements_buttons_simple_color',
			'qodef_elements_buttons_simple_hover_color',
			'qodef_elements_buttons_background_color',
			'qodef_elements_buttons_background_hover_color',
			'qodef_elements_buttons_border_color',
			'qodef_elements_buttons_border_hover_color',
		);

		// Fetch values for each option
		foreach ( $option_names as $option_name ) {
			// Get option value
			$option_value = qode_framework_get_post_value_through_levels( $scope, $option_name );

			// Continue if option value is empty
			if ( empty( $option_value ) ) { continue; }

			// Set option value
			$this->button_option_values[ $option_name ] = $option_value;
		}
	}



	/**
	 * Maybe add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function maybe_add_css_variables( $css_variables ) {
		// Bail if no button option values are set
		if ( empty( $this->button_option_values ) ) { return $css_variables; }

		$selector = ':root';

		// Check if any of the existing selectors in $css_variables already contain variables that start with '--fluidcheckout'
		foreach ( $css_variables as $key => $value ) {
			if ( is_array( $value ) && array_key_exists( '--fluidcheckout--field--height', $value ) ) {
				// Use the selector if found
				$selector = $key;
				break;
			}
		}

		// Add empty array for new CSS variables
		$new_css_variables = array( $selector => array() );

		// Maybe set variables for button text color
		if ( isset( $this->button_option_values[ 'qodef_elements_buttons_simple_color' ] ) ) {
			$button_text_color = $this->button_option_values[ 'qodef_elements_buttons_simple_color' ];

			$new_css_variables[ $selector ][ '--fluidcheckout--button--primary--text-color' ] = $button_text_color;
			$new_css_variables[ $selector ][ '--fluidcheckout--button--secondary--text-color' ] = $button_text_color;
		}

		// Maybe set variables for button text color on hover state
		if ( isset( $this->button_option_values[ 'qodef_elements_buttons_simple_hover_color' ] ) ) {
			$button_text_color_hover = $this->button_option_values[ 'qodef_elements_buttons_simple_hover_color' ];

			$new_css_variables[ $selector ][ '--fluidcheckout--button--primary--text-color--hover' ] = $button_text_color_hover;
			$new_css_variables[ $selector ][ '--fluidcheckout--button--secondary--text-color--hover' ] = $button_text_color_hover;
		}

		// Maybe set variables for button background colors
		if ( isset( $this->button_option_values[ 'qodef_elements_buttons_background_color' ] ) ) {
			$button_bg_color = $this->button_option_values[ 'qodef_elements_buttons_background_color' ];

			$new_css_variables[ $selector ][ '--fluidcheckout--button--primary--background-color' ] = $button_bg_color;
			$new_css_variables[ $selector ][ '--fluidcheckout--button--secondary--background-color' ] = $button_bg_color;
		}

		// Maybe set variables for button background colors on hover state
		if ( isset( $this->button_option_values[ 'qodef_elements_buttons_background_hover_color' ] ) ) {
			$button_bg_color_hover = $this->button_option_values[ 'qodef_elements_buttons_background_hover_color' ];

			$new_css_variables[ $selector ][ '--fluidcheckout--button--primary--background-color--hover' ] = $button_bg_color_hover;
			$new_css_variables[ $selector ][ '--fluidcheckout--button--secondary--background-color--hover' ] = $button_bg_color_hover;
		}

		// Maybe set variables for button border colors
		if ( isset( $this->button_option_values[ 'qodef_elements_buttons_border_color' ] ) ) {
			$button_border_color = $this->button_option_values[ 'qodef_elements_buttons_border_color' ];

			$new_css_variables[ $selector ][ '--fluidcheckout--button--primary--border-color' ] = $button_border_color;
			$new_css_variables[ $selector ][ '--fluidcheckout--button--secondary--border-color' ] = $button_border_color;
		}

		// Maybe set variables for button border colors on hover state
		if ( isset( $this->button_option_values[ 'qodef_elements_buttons_border_hover_color' ] ) ) {
			$button_border_color_hover = $this->button_option_values[ 'qodef_elements_buttons_border_hover_color' ];

			$new_css_variables[ $selector ][ '--fluidcheckout--button--primary--border-color--hover' ] = $button_border_color_hover;
			$new_css_variables[ $selector ][ '--fluidcheckout--button--secondary--border-color--hover' ] = $button_border_color_hover;
		}

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Get core plugin options name.
	 */
	public function get_core_plugin_options_name() {
		// Get theme name
		$theme_name = get_option( 'stylesheet' );

		// Get constant name
		$constant_name = strtoupper( $theme_name ) . '_CORE_OPTIONS_NAME';

		// Bail if constant is not defined
		if ( ! defined( $constant_name ) ) { return; }

		// Retrieve constant value
		$options_name = constant( $constant_name );

		return $options_name;
	}

}

FluidCheckout_QodeFramework::instance();
