<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Enfold (by Kriesi).
 */
class FluidCheckout_ThemeCompat_Enfold extends FluidCheckout {

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
		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template_checkout_page_template' ), 100, 3 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false' );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Button styles
		add_filter( 'fc_apply_button_colors_styles', '__return_true' );
		add_filter( 'fc_output_custom_styles', array( $this, 'maybe_add_css_variables_button_styles' ), 10 );
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template_checkout_page_template( $template, $template_name, $template_path ) {
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/themes/enfold/checkout-page-template/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;

			// Look for template file in the theme
			if ( apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
				$_template_override = locate_template( array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				) );
	
				// Check if files exist before changing template
				if ( file_exists( $_template_override ) ) {
					$_template = $_template_override;
				}
			}
		}

		// Use default template
		if ( ! $_template ) {
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $class; }

		return $class . ' container';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#header';

		return $attributes;
	}


	/**
	 * Get the color palette.
	 */
	public function get_color_palette() {
		// global $electro_options;
		global $avia_config, $avia_pages, $avia_elements, $avia_admin_values;
		
		echo '<pre>';
		var_dump( avia_get_option( 'color_scheme' ) );
		echo '</pre>';

		// $color_palette = array(
		// 	'primary--border-color' => $electro_options[ 'custom_primary_color' ],
		// 	'primary--background-color' => $electro_options[ 'custom_primary_color' ],
		// 	'primary--text-color' => $electro_options[ 'custom_primary_text_color' ],
		// 	'primary--border-color--hover' => '#000',
		// 	'primary--background-color--hover' => '#000',
		// 	'primary--text-color--hover' => '#fff',

		// 	'secondary--border-color' => '#efecec',
		// 	'secondary--background-color' => '#efecec',
		// 	'secondary--text-color' => '#333e48',
		// 	'secondary--border-color--hover' => '#fff',
		// 	'secondary--background-color--hover' => '#000',
		// 	'secondary--text-color--hover' => '#fff',
		// );

		// return $color_palette;
		return array();
	}



	/**
	 * Get CSS variables styles.
	 */
	public function get_css_variables_styles() {
		// Get the color palette
		$colors = $this->get_color_palette();

		// Define CSS variables
		$css_variables = ":root {
			--fluidcheckout--button--primary--border-color: {$colors['primary--border-color']};
			--fluidcheckout--button--primary--background-color: {$colors['primary--background-color']};
			--fluidcheckout--button--primary--text-color: {$colors['primary--text-color']};
			--fluidcheckout--button--primary--border-color--hover: {$colors['primary--border-color--hover']};
			--fluidcheckout--button--primary--background-color--hover: {$colors['primary--background-color--hover']};
			--fluidcheckout--button--primary--text-color--hover: {$colors['primary--text-color--hover']};

			--fluidcheckout--button--secondary--border-color: {$colors['secondary--border-color']};
			--fluidcheckout--button--secondary--background-color: {$colors['secondary--background-color']};
			--fluidcheckout--button--secondary--text-color: {$colors['secondary--text-color']};
			--fluidcheckout--button--secondary--border-color--hover: {$colors['secondary--border-color--hover']};
			--fluidcheckout--button--secondary--background-color--hover: {$colors['secondary--background-color--hover']};
			--fluidcheckout--button--secondary--text-color--hover: {$colors['secondary--text-color--hover']};
		}";

		return $css_variables;
	}



	/**
	 * Maybe add CSS variables for button styles.
	 */
	public function maybe_add_css_variables_button_styles( $custom_styles ) {
		// Bail if button styles are not enabled
		if ( ! FluidCheckout_Steps::instance()->is_button_styles_enabled() ) { return $custom_styles; }

		// Add css variables for button styles
		$custom_styles .= $this->get_css_variables_styles();

		return $custom_styles;
	}

}

FluidCheckout_ThemeCompat_Enfold::instance();
