<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Uncode (by Undsgn).
 */
class FluidCheckout_ThemeCompat_Uncode extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );
		remove_filter( 'woocommerce_order_button_html', 'uncode_woocommerce_order_button_html', 10 );

		// Dark mode
		add_filter( 'fc_enable_dark_mode_styles', array( $this, 'maybe_set_is_dark_mode' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Product price HTML
		remove_filter( 'woocommerce_get_price_html', 'uncode_price_html', 10, 2 );

		// Enhanced select2 styles
		add_filter( 'uncode_deregister_select2_style', array( $this, 'maybe_set_uncode_deregister_select2_style' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}



	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Checkout template hooks
		$this->checkout_template_hooks();

		// Select2 styles
		add_filter( 'uncode_deregister_select2_style', '__return_false', 10 );

		// Order summary section
		remove_action( 'woocommerce_review_order_before_cart_contents', 'uncode_woocommerce_activate_thumbs_on_order_review_table', 10 );
	}



	/**
	 * Add or remove checkout template hooks.
	 */
	public function checkout_template_hooks() {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Theme's inner containers
		add_action( 'fc_checkout_before_main_section', array( $this, 'add_inner_container_opening_tags' ), 10 );
		add_action( 'fc_checkout_after_main_section', array( $this, 'add_inner_container_closing_tags' ), 10 );
	}



	/**
	 * Maybe set theme to not deregister select2 styles.
	 *
	 * @param  bool  $deregister  Whether to deregister select2 styles.
	 */
	public function maybe_set_uncode_deregister_select2_style( $deregister ) {
		// Maybe set to not deregister select2
		// if using enhanced select components, as the select2 styles are replaced with an empty file for better compatibility
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_use_enhanced_select_components' ) ) {
			$deregister = false;
		}

		return $deregister;
	}



	/**
	 * Add opening tags for inner container from the theme.
	 */
	public function add_inner_container_opening_tags() {
		// Get container info from the theme
		$container_info = $this->get_container_info();

		// Get container class from the plugin
		$container_width_class = '';
		if ( isset( $container_info['container_width_class'] ) ) {
			$container_width_class = $container_info['container_width_class'];
		}

		// Get color scheme class
		$color_scheme_class = '';
		if ( isset( $container_info['color_scheme_class'] ) ) {
			$color_scheme_class = $container_info['color_scheme_class'];
		}

		$custom_styles = '';
		if ( isset( $container_info['custom_styles'] ) ) {
			$custom_styles = $container_info['custom_styles'];
		}

		?>
		<div class="row-container">
			<div class="row row-parent <?php echo $container_width_class . $color_scheme_class; ?>" <?php echo $custom_styles; ?>>
			<?php
	}

	/**
	 * Add closing tags for inner container from the theme.
	 */
	public function add_inner_container_closing_tags() {
			?>
			</div>
		</div>
		<?php
	}



	/**
	 * Get required container info from the theme.
	 * The function contains modified code from the theme's template 'page.php'.
	 */
	public function get_container_info() {
		// Bail if function is not available
		if ( ! function_exists( 'ot_get_option' ) ) { return; }
		
		// Get page settings
		global $metabox_data;
		$container_info = array();

		// Check if the page has a page specific layout width
		$is_page_specific_width = '';
		if ( isset( $metabox_data['_uncode_specific_layout_width'][0] ) ) {
			$is_page_specific_width = $metabox_data['_uncode_specific_layout_width'][0];
		}

		// Maybe get global page width
		if ( '' === $is_page_specific_width ) {
			// Get global page width
			$global_content_full = ot_get_option( '_uncode_page_layout_width' );

			// Check if the page width is set to full width
			if ( '' === $global_content_full && 'on' !== ot_get_option( '_uncode_body_full' ) ) {
				$container_info['container_width_class'] = ' limit-width';
			} 
			// Otherwise, maybe set custom global page width
			elseif ( 'limit' === $global_content_full ) {
				$generic_custom_width = ot_get_option( '_uncode_page_layout_width_custom' );

				// Check if the units are set to 'px'
				if ( 'px' === $generic_custom_width[1] ) {
					// Maybe set default value
					if ( '' == $generic_custom_width[0] || ! is_numeric( $generic_custom_width[0] ) ) {
						$generic_custom_width[0] = 1200;
					}

					// Round width in 12 columns grid
					$generic_custom_width[0] = 12 * round( ( $generic_custom_width[0] ) / 12 );
				}
				
				// Set custom width attribute
				if ( is_array( $generic_custom_width ) && ! empty( $generic_custom_width ) ) {
					$container_info['custom_styles'] = ' style="max-width: ' . implode( '', $generic_custom_width ) . '; margin: auto;"';
				}
			}
		} 
		// Othersiwse, get the page specific layout width
		else {
			if ( 'limit' === $is_page_specific_width ) {
				$container_info['container_width_class'] = ' limit-width';
				$container_info['custom_styles'] = '';
				$page_settings_value = '';

				// Check if custom width is set in the page settings
				if ( isset( $metabox_data['_uncode_specific_layout_width_custom'][0] ) ) {
					$page_settings_value = unserialize( $metabox_data['_uncode_specific_layout_width_custom'][0] );
				}

				// Check if custom width is set
				if ( is_array( $page_settings_value ) && ! empty( $page_settings_value ) && '' !== $page_settings_value[0] ) {
					// Round width in 12 columns grid if the units are set to 'px'
					if ( $page_settings_value[1] === 'px' ) {
						$page_settings_value[0] = 12 * round( ( $page_settings_value[0] ) / 12 );
					}

					// Set custom width attribute
					$container_info['custom_styles'] = ' style="max-width: ' . implode( '', $page_settings_value ) . '; margin: auto;"';
				}
			}
		}

		// Get color scheme class
		$container_info['color_scheme_class'] = '';
		if ( ! empty( $metabox_data['_uncode_specific_style'][0] ) ) {
			$container_info['color_scheme_class'] = ' style-' . $metabox_data['_uncode_specific_style'][0];
		} else {
			$container_info['color_scheme_class'] = ' style-' . ot_get_option( '_uncode_general_style' );
		}

		return $container_info;
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if plugin function isn't available
		if ( ! function_exists( 'ot_get_option' ) ) { return $attributes; }

		// Desktop settings
		$desktop_settings = '';
		if ( 'on' === ot_get_option( '_uncode_menu_sticky' ) ) {
			$desktop_settings = '"md": { "breakpointInitial": 960, "breakpointFinal": 10000, "selector": ".menu-sticky .menu-container" }';
		}

		// Mobile settings
		$mobile_settings = '';
		if ( 'on' === ot_get_option( '_uncode_menu_sticky_mobile' ) ) {
			$mobile_settings = '"xs": { "breakpointInitial": 0, "breakpointFinal": 959, "selector": ".menu-sticky .menu-container" }';
		}

		// Only keep non-empty values
		$settings = array_filter( array( $mobile_settings, $desktop_settings ), function( $value ) {
			return ! empty( $value );
		} );

		// Concatenate values with a comma
		$settings = implode( ', ', $settings );

		// Add the settings to the data attribute
		$attributes['data-sticky-relative-to'] = "{ {$settings} }";

		return $attributes;
	}



	/**
	 * Maybe set dark mode enabled.
	 * 
	 * @param  array  $is_dark_mode  Whether it is dark mode or not.
	 */
	public function maybe_set_is_dark_mode( $is_dark_mode ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'ot_get_option' ) ) { return $is_dark_mode; }

		// Get dark mode option from theme
		$theme_color_scheme = ot_get_option( '_uncode_general_style' );

		// Bail if not using the dark mode
		if ( 'dark' !== $theme_color_scheme ) { return $is_dark_mode; }

		$is_dark_mode = true;
		return $is_dark_mode;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme function is not available
		if ( ! function_exists( 'ot_get_option' ) ) { return $css_variables; }

		// Default values
		$accent_background_color = '#303133';
		$accent_color = '#ffffff';
		$button_font_size = '12px';
		$button_font_weight = '600';
		$button_text_transform = 'uppercase';

		// Change color values for dark mode
		if ( 'dark' === ot_get_option( '_uncode_general_style' ) ) {
			$accent_background_color = '#ffffff';
			$accent_color = '#303133';
		}

		// Maybe get values from the theme settings
		if ( ! empty( ot_get_option( '_uncode_buttons_font_size' ) ) ) {
			$button_font_size = ot_get_option( '_uncode_buttons_font_size' ) . 'px';
		}
		if ( ! empty( ot_get_option( '_uncode_buttons_font_weight' ) ) ) {
			$button_font_weight = ot_get_option( '_uncode_buttons_font_weight' );
		}
		if ( ! empty( ot_get_option( '_uncode_buttons_text_transform' ) ) ) {
			$button_text_transform = ot_get_option( '_uncode_buttons_text_transform' );
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '39.5px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--box-shadow' => 'inset 0 2px 1px rgba( 0, 0, 0, 0.025 )',
				'--fluidcheckout--field--border-width' => '1px',
				'--fluidcheckout--field--border-radius' => '2px',
				'--fluidcheckout--field--border-color' => '#eaeaea',
				'--fluidcheckout--field--font-size' => '15px',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '20px',

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => $accent_background_color,
				'--fluidcheckout--button--primary--background-color' => $accent_background_color,
				'--fluidcheckout--button--primary--text-color' => $accent_color,
				'--fluidcheckout--button--primary--border-color--hover' => $accent_background_color,
				'--fluidcheckout--button--primary--background-color--hover' => $accent_color,
				'--fluidcheckout--button--primary--text-color--hover' => $accent_background_color,

				// Button design styles
				'--fluidcheckout--button--border-radius' => '2px',
				'--fluidcheckout--button--font-size' => $button_font_size,
				'--fluidcheckout--button--font-weight' => $button_font_weight,

				// Custom theme variables
				'--fluidcheckout--uncode--button--text-transform' => $button_text_transform,
				'--fluidcheckout--uncode--accent-color' => $accent_color,
				'--fluidcheckout--uncode--accent-background-color' => $accent_background_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Uncode::instance();
