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
		add_filter( 'fc_place_order_button_classes', array( $this, 'add_place_order_button_classes' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_substep_save_button_classes' ), 10 );
		add_filter( 'fc_next_step_button_classes', array( $this, 'add_next_step_button_classes' ), 10 );
		remove_filter( 'woocommerce_order_button_html', 'uncode_woocommerce_order_button_html', 10 );

		// Dark mode
		add_filter( 'fc_enable_dark_mode_styles', array( $this, 'maybe_set_is_dark_mode' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Product price HTML
		remove_filter( 'woocommerce_get_price_html', 'uncode_price_html', 10, 2 );
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

		// Get global page width
		if ( '' === $is_page_specific_width ) {
			// Get global page width
			$global_content_full = ot_get_option( '_uncode_page_layout_width' );

			if ( '' === $global_content_full ) {
				$main_content_full = ot_get_option( '_uncode_body_full' );
				if ( $main_content_full !== 'on' ) {
					$container_info['container_width_class'] = ' limit-width';
				}
			} else {
				if ( $global_content_full === 'limit' ) {
					$generic_custom_width = ot_get_option( '_uncode_page_layout_width_custom' );

					if ( 'px' === $generic_custom_width[1] ) {
						if ( '' == $generic_custom_width[0] || ! is_numeric( $generic_custom_width[0] ) ) {
							$generic_custom_width[0] = 1200;
						}
						$generic_custom_width[0] = 12 * round( ( $generic_custom_width[0] ) / 12 );
					}
					if ( is_array( $generic_custom_width ) && !empty( $generic_custom_width ) ) {
						$container_info['custom_styles'] = ' style="max-width: '.implode( '', $generic_custom_width ) . '; margin: auto;"';
					}
				}
			}
		} 
		// Othersiwse, get the page specific layout width
		else {
			if ( 'limit' === $is_page_specific_width ) {
				$container_info['container_width_class'] = ' limit-width';

				$container_info['custom_styles'] = '';
				if ( isset( $metabox_data['_uncode_specific_layout_width_custom'][0] ) ) {
					$container_info['custom_styles'] = unserialize( $metabox_data['_uncode_specific_layout_width_custom'][0] );
				}

				if ( is_array( $container_info['custom_styles'] ) && !empty( $container_info['custom_styles'] ) && '' !== $container_info['custom_styles'][0] ) {
					if ( $container_info['custom_styles'][1] === 'px' ) {
						$container_info['custom_styles'][0] = 12 * round( ( $container_info['custom_styles'][0] ) / 12 );
					}
					$container_info['custom_styles'] = ' style="max-width: ' . implode( "", $container_info['custom_styles'] ) . '; margin: auto;"';
				} else {
					$container_info['custom_styles'] = '';
				}
			}
		}

		// Get color scheme class
		$container_info['color_scheme_class'] = '';
		if ( ! empty( $metabox_data['_uncode_specific_style'][0] ) ) {
			$container_info['color_scheme_class'] = ' style-' . $metabox_data['_uncode_specific_style'][0];
		} else {
			$container_info['color_scheme_class'] = ' style-' . ot_get_option('_uncode_general_style');
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
	 * Add classes to the place order button.
	 *
	 * @param  string  $class  Button classes.
	 */
	public function add_place_order_button_classes( $class ) {
		$class .= ' btn checkout-button btn-default';

		return $class;
	}

	/**
	 * Add classes to the substep save button.
	 *
	 * @param  string  $class  Button classes.
	 */
	public function add_substep_save_button_classes( $class ) {
		$class .= ' btn';

		return $class;
	}

	/**
	 * Add classes to the next step button.
	 *
	 * @param  array  $class  Button classes.
	 */
	public function add_next_step_button_classes( $class ) {
		$class = array_merge( $class, array( 'btn', 'checkout-button', 'btn-default' ) );

		return $class;
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

		$button_accent_background_color = '#303133';
		$button_accent_color = '#ffffff';

		// Get dark mode option from theme
		$theme_color_scheme = ot_get_option( '_uncode_general_style' );

		// Change color values for dark mode
		if ( 'dark' === $theme_color_scheme ) {
			$button_accent_background_color = '#ffffff';
			$button_accent_color = '#303133';
		}

		// Add CSS variables
		$new_css_variables = array(
			// Buttons
			':root' => array(
				'--fluidcheckout--button--primary--border-color' => $button_accent_background_color,
				'--fluidcheckout--button--primary--background-color' => $button_accent_background_color,
				'--fluidcheckout--button--primary--text-color' => $button_accent_color,
				'--fluidcheckout--button--primary--border-color--hover' => $button_accent_background_color,
				'--fluidcheckout--button--primary--background-color--hover' => $button_accent_color,
				'--fluidcheckout--button--primary--text-color--hover' => $button_accent_background_color,

				// Form field styles
				'--fluidcheckout--field--height' => '39.5px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--box-shadow' => 'inset 0 2px 1px rgba( 0, 0, 0, 0.025 )',
				'--fluidcheckout--field--border-radius' => '2px',
				'--fluidcheckout--field--border-color' => '#eaeaea',
				'--fluidcheckout--field--font-size' => '15px',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '20px',

				// Button design styles
				'--fluidcheckout--button--border-radius' => '2px',
				'--fluidcheckout--button--font-size' => '12px',
				'--fluidcheckout--button--font-weight' => '600',

				// Custom theme variables
				'--fluidcheckout--uncode--accent-color' => $button_accent_color,
				'--fluidcheckout--uncode--accent-background-color' => $button_accent_background_color,
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Uncode::instance();
