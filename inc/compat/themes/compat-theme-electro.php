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

		// General
		add_filter( 'body_class', array( $this, 'add_body_class' ), 10 );

		// CSS variables
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css_variables' ), 10 );
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
	 * Add page body class for feature detection.
	 *
	 * @param array $classes Classes for the body element.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on affected pages.
		if (
			! function_exists( 'is_checkout' )
			|| (
				! is_checkout() // Checkout page
				&& ! is_wc_endpoint_url( 'add-payment-method' ) // Add payment method page
				&& ! is_wc_endpoint_url( 'edit-address' ) // Edit address page
			)
		) { return $classes; }

		// Add custom button color class
		$classes[] = 'has-fc-button-colors';

		return $classes;
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
	 * Get CSS variables styles.
	 */
	public function get_css_variables_styles() {
		// Get the color palette
		$colors = $this->get_color_palette();

		// Define CSS variables
		$css_variables = ":root {
			--fluidcheckout--button--primary--border-color: {$colors['primary-border-color']};
			--fluidcheckout--button--primary--background-color: {$colors['primary-background-color']};
			--fluidcheckout--button--primary--text-color: {$colors['primary-text-color']};
			--fluidcheckout--button--primary--border-color--hover: {$colors['primary-border-color--hover']};
			--fluidcheckout--button--primary--background-color--hover: {$colors['primary-background-color--hover']};
			--fluidcheckout--button--primary--text-color--hover: {$colors['primary-text-color--hover']};

			--fluidcheckout--button--secondary--border-color: {$colors['secondary-border-color']};
			--fluidcheckout--button--secondary--background-color: {$colors['secondary-background-color']};
			--fluidcheckout--button--secondary--text-color: {$colors['secondary-text-color']};
			--fluidcheckout--button--secondary--border-color--hover: {$colors['secondary-border-color--hover']};
			--fluidcheckout--button--secondary--background-color--hover: {$colors['secondary-background-color--hover']};
			--fluidcheckout--button--secondary--text-color--hover: {$colors['secondary-text-color--hover']};
		}";

		return $css_variables;
	}



	/**
	 * Enqueue inline CSS variables.
	 */
	public function enqueue_css_variables() {
		// Bail if not on affected pages.
		if (
			! function_exists( 'is_checkout' )
			|| (
				! is_checkout() // Checkout page
				&& ! is_wc_endpoint_url( 'add-payment-method' ) // Add payment method page
				&& ! is_wc_endpoint_url( 'edit-address' ) // Edit address page
			)
		) { return; }

		// Enqueue inline style
		wp_add_inline_style( 'electro-style', $this->get_css_variables_styles() );
	}

}

FluidCheckout_ThemeCompat_Electro::instance();
