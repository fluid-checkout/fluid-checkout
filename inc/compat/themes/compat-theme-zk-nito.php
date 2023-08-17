<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: ZK Nito (By Chinh Duong Manh).
 */
class FluidCheckout_ThemeCompat_ZKNito extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false' );
		add_filter( 'fc_content_section_class', array( $this, 'add_content_section_class' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Form field labels
		remove_filter( 'woocommerce_form_field_args' , 'zk_nito_override_woocommerce_form_field', 10 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function add_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $class; }

		// Maybe add the container class
		$class = $class . ' container';

		return $class;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		global $opt_theme_options;

		// Get default primary/accent colors
		$primary_color = ! empty( $opt_theme_options[ 'wp_nito_primary_color' ][ 'regular' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_primary_color' ][ 'regular' ] ) : '#1f1f1f';
		$accent_color_hover = ! empty( $opt_theme_options[ 'wp_nito_accent_color' ][ 'hover' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_accent_color' ][ 'hover' ] ) : '#1f1f1f';

		// Add CSS variables
		// For default primary/accent colors
		$new_css_variables = array(
			':root' => array(
				'--fluidcheckout--button--primary--border-color' => $primary_color,
				'--fluidcheckout--button--primary--background-color' => $primary_color,
				'--fluidcheckout--button--primary--text-color' => '#fff',
				'--fluidcheckout--button--primary--border-color--hover' => $accent_color_hover,
				'--fluidcheckout--button--primary--background-color--hover' => $accent_color_hover,
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',

				'--fluidcheckout--button--secondary--border-color' => $primary_color,
				'--fluidcheckout--button--secondary--background-color' => 'transparent',
				'--fluidcheckout--button--secondary--text-color' => $primary_color,
				'--fluidcheckout--button--secondary--border-color--hover' => $accent_color_hover,
				'--fluidcheckout--button--secondary--background-color--hover' => $accent_color_hover,
				'--fluidcheckout--button--secondary--text-color--hover' => '#fff',
			),
		);
		
		//
		// Default button styles from theme settings
		//

		// Maybe set colors for default button text colors
		$default_button_color = ! empty( $opt_theme_options[ 'wp_nito_btn_default_color' ][ 'regular' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_default_color' ][ 'regular' ] ) : '';
		if ( ! empty( $default_button_color ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--text-color' ] = $default_button_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--secondary--text-color' ] = $default_button_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--text-color--hover' ] = $default_button_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--secondary--text-color--hover' ] = $default_button_color;
		}
		
		// Maybe set colors for default button text colors on hover state
		$default_button_color_hover = ! empty( $opt_theme_options[ 'wp_nito_btn_default_color' ][ 'hover' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_default_color' ][ 'hover' ] ) : '';
		if ( ! empty( $default_button_color_hover ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--text-color--hover' ] = $default_button_color_hover;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--secondary--text-color--hover' ] = $default_button_color_hover;
		}
		
		// Maybe set colors for default button background colors
		$default_button_background_color = ! empty( $opt_theme_options[ 'wp_nito_btn_default_bg' ][ 'background-color' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_default_bg' ][ 'background-color' ] ) : '';
		if ( ! empty( $default_button_background_color ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--background-color' ] = $default_button_background_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--secondary--background-color' ] = $default_button_background_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--background-color--hover' ] = $default_button_background_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--secondary--background-color--hover' ] = $default_button_background_color;
		}

		// Maybe set colors for default button background colors on hover state
		$default_button_background_color_hover = ! empty( $opt_theme_options[ 'wp_nito_btn_default_bg_hover' ][ 'background-color' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_default_bg_hover' ][ 'background-color' ] ) : '';
		if ( ! empty( $default_button_background_color_hover ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--background-color--hover' ] = $default_button_background_color_hover;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--secondary--background-color--hover' ] = $default_button_background_color_hover;
		}

		// Maybe set colors for default button border colors
		$default_button_border_color = ! empty( $opt_theme_options[ 'wp_nito_btn_default_border' ][ 'border-color' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_default_border' ][ 'border-color' ] ) : '#1f1f1f';
		if ( ! empty( $default_button_border_color ) && '#1f1f1f' !== strtolower( $default_button_border_color ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--border-color' ] = $default_button_border_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--secondary--border-color' ] = $default_button_border_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--border-color--hover' ] = $default_button_border_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--secondary--border-color--hover' ] = $default_button_border_color;
		}

		//
		// END - Default button styles from theme settings
		//
		


		//
		// Primary button styles from theme settings
		//

		// Maybe set colors for default button text colors
		$primary_button_color = ! empty( $opt_theme_options[ 'wp_nito_btn_primary_color' ][ 'regular' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_primary_color' ][ 'regular' ] ) : '';
		if ( ! empty( $primary_button_color ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--text-color' ] = $primary_button_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--text-color--hover' ] = $primary_button_color;
		}
		
		// Maybe set colors for default button text colors on hover state
		$primary_button_color_hover = ! empty( $opt_theme_options[ 'wp_nito_btn_primary_color' ][ 'hover' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_primary_color' ][ 'hover' ] ) : '';
		if ( ! empty( $primary_button_color_hover ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--text-color--hover' ] = $primary_button_color_hover;
		}
		
		// Maybe set colors for default button background colors
		$primary_button_background_color = ! empty( $opt_theme_options[ 'wp_nito_btn_primary_bg' ][ 'background-color' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_primary_bg' ][ 'background-color' ] ) : '';
		if ( ! empty( $primary_button_background_color ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--background-color' ] = $primary_button_background_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--background-color--hover' ] = $primary_button_background_color;
		}

		// Maybe set colors for default button background colors on hover state
		$primary_button_background_color_hover = ! empty( $opt_theme_options[ 'wp_nito_btn_primary_bg_hover' ][ 'background-color' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_primary_bg_hover' ][ 'background-color' ] ) : '';
		if ( ! empty( $primary_button_background_color_hover ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--background-color--hover' ] = $primary_button_background_color_hover;
		}

		// Maybe set colors for default button border colors
		$primary_button_border_color = ! empty( $opt_theme_options[ 'wp_nito_btn_primary_border' ][ 'border-color' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_btn_primary_border' ][ 'border-color' ] ) : '#1f1f1f';
		if ( ! empty( $primary_button_border_color ) && '#1f1f1f' !== strtolower( $primary_button_border_color ) ) {
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--border-color' ] = $primary_button_border_color;
			$new_css_variables[ ':root' ][ '--fluidcheckout--button--primary--border-color--hover' ] = $primary_button_border_color;
		}

		//
		// END - Primary button styles from theme settings
		//

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_ZKNito::instance();
