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
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_wrapper_classes', array( $this, 'change_wrapper_class' ), 10 );

		// Login form inner class
		add_filter( 'fc_login_form_inner_class', array( $this, 'change_fc_login_form_inner_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
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
	 * Change the wrapper class, to apply theme styles on various sub-elements.
	 *
	 * @param string $class Wrapper class.
	 */
	public function change_wrapper_class( $class ) {
		return $class . ' main_color';
	}

	/**
	 * Change the login form inner class, to apply theme styles on various sub-elements.
	 *
	 * @param string $class Login form inner class.
	 */
	public function change_fc_login_form_inner_class( $class ) {
		return $class . ' main_color';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#header';

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'avia_get_option' ) ) { return $css_variables; }

		// Default color values
		$accent_color = '#83a83d';
		$border_color = '#e1e1e1';
		$background_color = '#fcfcfc';
		$font_family = 'Open Sans:400,600';
		$font_color = '#919191';

		// Get theme options
		$options = avia_get_option();

		// Fetch accent color from theme settings if exist
		if ( ! empty( $options['colorset-main_color-secondary'] ) ) {
			$accent_color = $options['colorset-main_color-secondary'];
		}

		// Fetch border color from theme settings if exist
		if ( ! empty( $options['colorset-main_color-border'] ) ) {
			$border_color = $options['colorset-main_color-border'];
		}

		// Fetch background color from theme settings if exist
		if ( ! empty( $options['colorset-main_color-bg2'] ) ) {
			$background_color = $options['colorset-main_color-bg2'];
		}
		
		// Fetch font family from theme settings if exist
		if ( ! empty( $options['default_font'] ) ) {
			$font_family = $options['default_font'];
		}

		// Fetch font color from theme settings if exist
		if ( ! empty( $options['colorset-main_color-meta'] ) ) {
			$font_color = $options['colorset-main_color-meta'];
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '35.14px',
				'--fluidcheckout--field--padding-left' => '6px',
				'--fluidcheckout--field--border-radius' => '0',
				'--fluidcheckout--field--border-color' => $border_color,
				'--fluidcheckout--field--font-family' => $font_family,
				'--fluidcheckout--field--font-size' => '16px',
				'--fluidcheckout--field--text-color' => $font_color,
				'--fluidcheckout--field--background-color' => $background_color,
				'--fluidcheckout--field--background-color--accent' => $accent_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Enfold::instance();
