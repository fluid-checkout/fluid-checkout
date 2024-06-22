<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Blocksy Companion (Premium) (by CreativeThemes).
 */
class FluidCheckout_BlocksyCompanionPRO extends FluidCheckout {

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
		// Template custom attributes
		add_filter( 'fc_checkout_html_custom_attributes', array( $this, 'add_html_attributes' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add custom attributes to the html element.
	 *
	 * @param  array  $custom_attributes   HTML attributes.
	 */
	public function add_html_attributes( $custom_attributes ) {
		// Bail if theme class is not available
		if ( ! class_exists( 'Blocksy_Header_Builder_Render' ) ) { return $custom_attributes; }

		// Define initial theme value
		$theme = 'light';

		// Get instance of the header builder render class
		$render = new Blocksy_Header_Builder_Render();

		// Try get color mode from the header builder
		if ( $render->contains_item( 'color-mode-switcher' ) ) {
			$atts = $render->get_item_data_for('color-mode-switcher');

			$default_color_mode = blocksy_akg(
				'default_color_mode',
				$atts,
				'light'
			);

			if ($default_color_mode === 'dark') {
				$theme = 'dark';
			}

			if ($default_color_mode === 'system') {
				$theme = 'os-default';
			}
		}

		// Try get color mode from the cookie
		if (isset($_COOKIE['blocksy_current_theme'])) {
			if ($_COOKIE['blocksy_current_theme'] === 'dark') {
				$theme = 'dark';
			}

			if ($_COOKIE['blocksy_current_theme'] === 'light') {
				$theme = 'light';
			}
		}

		// Add color mode to the custom attributes
		$custom_attributes['data-color-mode'] = $theme;

		return $custom_attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get dark mode CSS variables
		$dark_mode_variables = FluidCheckout_DesignTemplates::instance()->get_css_variables_dark_mode();

		// Add CSS variables
		$new_css_variables = array(
			':root[data-color-mode="dark"]' => $dark_mode_variables,
			':root[data-color-mode="dark:updating"]' => $dark_mode_variables,
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_BlocksyCompanionPRO::instance();
