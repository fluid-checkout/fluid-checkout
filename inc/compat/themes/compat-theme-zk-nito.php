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
		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'add_content_section_class' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );

		// Form fields
		remove_filter( 'woocommerce_form_field_args' , 'zk_nito_override_woocommerce_form_field', 10 );
		remove_filter( 'woocommerce_checkout_fields', 'zk_nito_order_fields', 10 );
		remove_filter( 'woocommerce_checkout_fields', 'zk_nito_shipping_order_fields', 10 );
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_zk_nito_display_field_labels' ) ) {
			remove_filter( 'woocommerce_checkout_fields', 'zk_nito_override_checkout_fields', 10 );
			add_filter( 'woocommerce_shipping_fields', array( $this, 'maybe_add_extra_checkout_fields' ), 10 );
		}
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout or edit address pages
		if( ! function_exists( 'is_checkout' ) || ( ( ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) && ! is_wc_endpoint_url( 'edit-address' ) ) ) { return $classes; }

		// Add extra class to highlight the billing section
		$add_classes = array();
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_zk_nito_display_field_labels' ) || is_wc_endpoint_url( 'edit-address' ) ) {
			$add_classes[] = 'has-visible-form-field-labels';
		}

		return array_merge( $classes, $add_classes );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {

		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Theme ZK Nito', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_zk_nito_options',
			),

			array(
				'title'           => __( 'Checkout fields', 'fluid-checkout' ),
				'desc'            => __( 'Display the form field labels visible on the page', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_zk_nito_display_field_labels',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_zk_nito_display_field_labels' ),
				'autoload'        => false,
			),

			array(
				'desc'            => __( 'Add extra fields to the checkout form', 'fluid-checkout' ),
				'desc_tip'        => __( 'Adds an extra shipping email and shipping phone field to the shipping address section.', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_zk_nito_add_extra_fields',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_zk_nito_add_extra_fields' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_zk_nito_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function add_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

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
		$accent_color = ! empty( $opt_theme_options[ 'wp_nito_accent_color' ][ 'active' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_accent_color' ][ 'active' ] ) : '#1f1f1f';
		$accent_color_hover = ! empty( $opt_theme_options[ 'wp_nito_accent_color' ][ 'hover' ] ) ? esc_attr( $opt_theme_options[ 'wp_nito_accent_color' ][ 'hover' ] ) : '#1f1f1f';

		// Add CSS variables
		// For default primary/accent colors
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '40.14px',
				'--fluidcheckout--field--padding-left' => '25px',
				'--fluidcheckout--field--background-color--accent' => $accent_color,

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => $primary_color,
				'--fluidcheckout--button--primary--background-color' => $primary_color,
				'--fluidcheckout--button--primary--text-color' => '#fff',
				'--fluidcheckout--button--primary--border-color--hover' => $accent_color_hover,
				'--fluidcheckout--button--primary--background-color--hover' => $accent_color_hover,
				'--fluidcheckout--button--primary--text-color--hover' => '#fff',

				// Secondary button colors
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



	/**
	 * Add extra checkout fields which were originaly added by the theme.
	 */
	public function maybe_add_extra_checkout_fields( $fields ) {
		// Bail if option to add extra fields from theme integration is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_zk_nito_add_extra_fields' ) ) { return $fields; }

		// ADAPTED FROM ZK NITO THEME
		/* Add Email/ Phone on Shipping fields */
		$fields['shipping_phone'] = array(
			'label'         => esc_html__( 'Phone', 'zk-nito' ),
			'placeholder'   => _x( 'Phone', 'placeholder', 'zk-nito' ),
			'required'      => false,
			'class'         => array( 'form-row-first' ),
			'clear'         => true,
			'priority'      => 20,
		);

		$fields['shipping_email'] = array(
			'label'         => esc_html__( 'Email Address', 'zk-nito' ),
			'placeholder'   => _x( 'Email Address', 'placeholder', 'zk-nito' ),
			'required'      => false,
			'class'         => array( 'form-row-last' ),
			'clear'         => false,
			'priority'      => 25,
		);

		return $fields;
	}

}

FluidCheckout_ThemeCompat_ZKNito::instance();
