<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Electro (by MandrasThemes).
 */
class FluidCheckout_ThemeCompat_Electro extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		remove_action( 'woocommerce_checkout_shipping', 'electro_shipping_details_header', 0 );
		remove_action( 'woocommerce_checkout_before_order_review', 'electro_wrap_order_review', 0 );
		remove_action( 'woocommerce_checkout_after_order_review', 'electro_wrap_order_review_close', 0 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.site-header.stick-this';

		return $attributes;
	}



	/**
	 * Get the color palette.
	 */
	public function get_color_palette() {
		global $electro_options;

		$default_colors = array(
			'primary-border-color--hover' => '#000',
			'primary-background-color--hover' => '#000',
			'primary-text-color--hover' => '#fff',

			'secondary-border-color' => '#efecec',
			'secondary-background-color' => '#efecec',
			'secondary-text-color' => '#333e48',
			'secondary-border-color--hover' => '#fff',
			'secondary-background-color--hover' => '#000',
			'secondary-text-color--hover' => '#fff',
		);

		// Maybe return custom colors from theme options
		if ( ! apply_filters( 'electro_use_predefined_colors', true ) ) {
			// Return custom colors
			return array_merge(
				$default_colors,
				array(
					'primary-border-color' => $electro_options[ 'custom_primary_color' ],
					'primary-background-color' => $electro_options[ 'custom_primary_color' ],
					'primary-text-color' => $electro_options[ 'custom_primary_text_color' ],
				)
			);
		}

		// Otherwise, continue to use predefined colors

		// Get color scheme from theme options
		$color_scheme = apply_filters( 'electro_primary_color', 'yellow' );

		// Define color schemes
		$color_palettes = array(
			// Default color scheme (`yellow`)
			'yellow' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#fed700',
					'primary-background-color' => '#fed700',
					'primary-text-color' => '#000',
				)
			),
			'black' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#000',
					'primary-background-color' => '#000',
					'primary-text-color' => '#fff',
				)
			),
			'blue' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#0787ea',
					'primary-background-color' => '#0787ea',
					'primary-text-color' => '#fff',
				)
			),
			'flat-blue' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#00abc5',
					'primary-background-color' => '#00abc5',
					'primary-text-color' => '#fff',
				)
			),
			'gold' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#dab26d',
					'primary-background-color' => '#dab26d',
					'primary-text-color' => '#fff',
				)
			),
			'green' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#a3d133',
					'primary-background-color' => '#a3d133',
					'primary-text-color' => '#fff',
				)
			),
			'orange' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#f89a20',
					'primary-background-color' => '#f89a20',
					'primary-text-color' => '#fff',
				)
			),
			'pink' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#ce1d76',
					'primary-background-color' => '#ce1d76',
					'primary-text-color' => '#fff',
				)
			),
			'red' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => '#ea1b25',
					'primary-background-color' => '#ea1b25',
					'primary-text-color' => '#fff',
				)
			),
			'grey' => array_merge(
				$default_colors,
				array(
					'primary-border-color' => 'grey',
					'primary-background-color' => 'grey',
					'primary-text-color' => '#fff',
				)
			),
		);

		return $color_palettes[ $color_scheme ];
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get the color palette
		$colors = $this->get_color_palette();

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Primary button styles
				'--fluidcheckout--button--primary--border-color' => $colors['primary-border-color'],
				'--fluidcheckout--button--primary--background-color' => $colors['primary-background-color'],
				'--fluidcheckout--button--primary--text-color' => $colors['primary-text-color'],
				'--fluidcheckout--button--primary--border-color--hover' => $colors['primary-border-color--hover'],
				'--fluidcheckout--button--primary--background-color--hover' => $colors['primary-background-color--hover'],
				'--fluidcheckout--button--primary--text-color--hover' => $colors['primary-text-color--hover'],

				// Secondary button styles
				'--fluidcheckout--button--secondary--border-color' => $colors['secondary-border-color'],
				'--fluidcheckout--button--secondary--background-color' => $colors['secondary-background-color'],
				'--fluidcheckout--button--secondary--text-color' => $colors['secondary-text-color'],
				'--fluidcheckout--button--secondary--border-color--hover' => $colors['secondary-border-color--hover'],
				'--fluidcheckout--button--secondary--background-color--hover' => $colors['secondary-background-color--hover'],
				'--fluidcheckout--button--secondary--text-color--hover' => $colors['secondary-text-color--hover'],

				// Form field styles
				'--fluidcheckout--field--height' => '44.98px',
				'--fluidcheckout--field--padding-left' => '16px',
				'--fluidcheckout--field--border-radius' => '22.49px',
				'--fluidcheckout--field--border-color' => '#ddd',
				'--fluidcheckout--field-item--border-radius' => '12px',
				'--fluidcheckout--field--background-color' => '#fff',
				'--fluidcheckout--field--background-color--accent' => $colors['primary-background-color'],
				'--fluidcheckout--field--text-color--accent' => $colors['primary-text-color'],
				'--fluidcheckout--field--text-color--focus' => $colors['primary-text-color'],

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing' => '10px',
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '30px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '40px',
			),
			':root body.electro-dark' => array_merge(
				FluidCheckout_DesignTemplates::instance()->get_css_variables_dark_mode(),
				array(
					'--fluidcheckout--field--border-color' => '#000',
					'--fluidcheckout--field--background-color' => '#000',
				)
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Electro::instance();
