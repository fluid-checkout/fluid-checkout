<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Woodmart (by XTemos).
 */
class FluidCheckout_ThemeCompat_Woodmart extends FluidCheckout {

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
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_apply_button_colors_styles', '__return_true', 10 );
		add_filter( 'fc_apply_button_design_styles', '__return_true', 10 );

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Dark mode
		add_filter( 'fc_enable_dark_mode_styles', array( $this, 'maybe_set_is_dark_mode' ), 10 );

		// Header elements
		add_action( 'fc_checkout_header', array( $this, 'maybe_output_woodmart_checkout_steps_section' ), 20 );

		// Template files
		add_filter( 'fc_override_template_with_theme_file', array( $this, 'override_template_with_theme_file' ), 10, 4 );

		// Theme options
		add_action( 'wp', array( $this, 'init_theme_options_hooks' ), 150 );

		// Free shipping bar
		add_action( 'wp', array( $this, 'init_free_shipping_bar_hooks' ), 150 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'maybe_add_free_shipping_bar_fragment' ), 10 );
	}

	/**
	 * Initialize theme options hooks.
	 */
	public function init_theme_options_hooks() {
		// Bail if theme functions and classes are not available
		if ( ! class_exists( 'XTS\Modules\Checkout_Order_Table' ) ) { return; }

		// Check whether to disable theme checkout options
		if ( 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_woodmart_disable_theme_checkout_options' ) || true === apply_filters( 'fc_compat_theme_woodmart_disable_theme_checkout_options', false ) ) {
			// Get theme class instances
			$checkout_module_instance = XTS\Modules\Checkout_Order_Table::get_instance();

			// Checkout cart items template
			remove_action( 'woocommerce_review_order_before_cart_contents', array( $checkout_module_instance, 'checkout_table_content_replacement' ), 10 );
		}
	}

	/**
	 * Initialize free shipping bar hooks.
	 */
	public function init_free_shipping_bar_hooks() {
		// Bail if theme functions and classes are not available
		if ( ! function_exists( 'woodmart_get_opt' ) || ! class_exists( 'XTS\Modules\Shipping_Progress_Bar\Main' ) || ! class_exists( 'XTS\Modules\Layouts\Main' ) ) { return; }

		// Get theme class instances
		$free_shipping_bar_instance = XTS\Modules\Shipping_Progress_Bar\Main::get_instance();
		$builder_instance = XTS\Modules\Layouts\Main::get_instance();

		// Checkout page
		if ( woodmart_get_opt( 'shipping_progress_bar_enabled' ) && woodmart_get_opt( 'shipping_progress_bar_location_checkout' ) ) {
			remove_action( 'woocommerce_checkout_before_customer_details', array( $free_shipping_bar_instance, 'render_shipping_progress_bar_with_wrapper' ), 10 );
			remove_action( 'woocommerce_checkout_billing', array( $free_shipping_bar_instance, 'render_shipping_progress_bar_with_wrapper' ), 10 );
			add_action( 'fc_checkout_before_steps', array( $free_shipping_bar_instance, 'render_shipping_progress_bar_with_wrapper' ), 5 ); // Right before coupon code section
		}
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Get theme colors

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => 'var( --wd-form-height, 42px )',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-radius' => 'var( --wd-form-brd-radius, 2px )',
				'--fluidcheckout--field--border-width' => 'var( --wd-form-brd-width, 3px )',
				'--fluidcheckout--field--border-color' => 'var( --wd-form-brd-color, rgba( 0, 0, 0, 0.1 ) )',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--background-color--accent' => 'var( --wd-primary-color, #83b735 )',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '20px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '25px',

				// Primary button colors
				'--fluidcheckout--button--primary--border-color' => 'var(--btn-accented-bgcolor)',
				'--fluidcheckout--button--primary--background-color' => 'var(--btn-accented-bgcolor)',
				'--fluidcheckout--button--primary--text-color' => 'var(--btn-accented-color)',
				'--fluidcheckout--button--primary--border-color--hover' => 'var(--btn-accented-bgcolor-hover)',
				'--fluidcheckout--button--primary--background-color--hover' => 'var(--btn-accented-bgcolor-hover)',
				'--fluidcheckout--button--primary--text-color--hover' => 'var(--btn-accented-color)',

				// Secondary button colors
				'--fluidcheckout--button--secondary--border-color' => 'var(--btn-default-bgcolor)',
				'--fluidcheckout--button--secondary--background-color' => 'var(--btn-default-bgcolor)',
				'--fluidcheckout--button--secondary--text-color' => 'var(--btn-default-color)',
				'--fluidcheckout--button--secondary--border-color--hover' => 'var(--btn-default-bgcolor-hover)',
				'--fluidcheckout--button--secondary--background-color--hover' => 'var(--btn-default-bgcolor-hover)',
				'--fluidcheckout--button--secondary--text-color--hover' => 'var(--btn-default-color)',

				// Button design styles
				'--fluidcheckout--button--height' => 'var(--btn-height)',
				'--fluidcheckout--button--font-weight' => 'var(--btn-font-weight)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
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
				'title' => __( 'Theme Woodmart', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_theme_woodmart_options',
			),

			array(
				'title'           => __( 'Checkout progress', 'fluid-checkout' ),
				'desc'            => __( 'Output the checkout steps section from the Woodmart theme when using Fluid Checkout header and footer.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/compat-theme-woodmart/' ),
				'id'              => 'fc_compat_theme_woodmart_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_woodmart_output_checkout_steps_section' ),
				'autoload'        => false,
			),

			array(
				'title'           => __( 'Checkout options', 'fluid-checkout' ),
				'desc'            => __( 'Disable the theme checkout options', 'fluid-checkout' ),
				'desc_tip'        => __( 'The options display product image, quantity field, remove button and link to product page added by the theme are disabled by default.', 'fluid-checkout' ) . ' ' . FluidCheckout_Admin::instance()->get_documentation_link_html( 'https://fluidcheckout.com/docs/compat-theme-woodmart/' ),
				'id'              => 'fc_compat_theme_woodmart_disable_theme_checkout_options',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_woodmart_disable_theme_checkout_options' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_theme_woodmart_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Override plugin template files with the theme version.
	 */
	public function override_template_with_theme_file( $override, $template, $template_name, $template_path ) {
		if ( 'global/form-login.php' === $template_name ) {
			$override = true;
		}

		return $override;
	}



	/**
	 * Maybe output the checkout steps section from the Woodmart theme.
	 */
	public function maybe_output_woodmart_checkout_steps_section() {
		// Bail if Woodmart section output is disabled in the plugin settings
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_compat_theme_woodmart_output_checkout_steps_section' ) ) { return; }

		// Bail if Woodmart checkout steps function not available
		if ( ! function_exists( 'woodmart_checkout_steps' ) ) { return; }

		$title_type = 'default';
		$title_design = woodmart_get_opt( 'page-title-design' );
		$title_size = woodmart_get_opt( 'page-title-size', 'default' );
		$title_color = woodmart_get_opt( 'page-title-color', 'default' );
		
		$title_class = 'page-title-';
		$title_class .= $title_type;
		$title_class .= ' title-size-' . $title_size;
		$title_class .= ' title-design-' . $title_design;
		$title_class .= ' color-scheme-' . $title_color;

		woodmart_enqueue_inline_style( 'page-title' );

		// Bail if title disabled
		if ( 'disabled' === $title_design ) { return; }
		?>
		<div class="page-title <?php echo esc_attr( $title_class ); ?>">
			<div class="container"><?php woodmart_checkout_steps(); ?></div>
		</div>
		<?php
	}



	/**
	 * Maybe set dark mode enabled.
	 * 
	 * @param  array  $is_dark_mode  Whether it is dark mode or not.
	 */
	public function maybe_set_is_dark_mode( $is_dark_mode ) {
		// Bail if theme functions and classes are not available
		if ( ! function_exists( 'woodmart_get_opt' ) ) { return $is_dark_mode; }

		// Get dark mode option from theme
		$dark = woodmart_get_opt( 'dark_version' );

		// Bail if not using the dark mode
		if ( ! $dark ) { return $is_dark_mode; }

		$is_dark_mode = true;
		return $is_dark_mode;
	}



	/**
	 * Maybe add free shipping bar a checkout fragment.
	 *
	 * @param   array  $fragments  Checkout fragments.
	 */
	public function maybe_add_free_shipping_bar_fragment( $fragments ) {
		// Bail if theme functions and classes are not available
		if ( ! function_exists( 'woodmart_get_opt' ) || ! class_exists( 'XTS\Modules\Shipping_Progress_Bar\Main' ) || ! class_exists( 'XTS\Modules\Layouts\Main' ) ) { return $fragments; }

		// Get theme class instances
		$free_shipping_bar_instance = XTS\Modules\Shipping_Progress_Bar\Main::get_instance();
		$builder_instance = XTS\Modules\Layouts\Main::get_instance();

		// Bail if shipping bar is disabled for the checkout page
		if ( ! woodmart_get_opt( 'shipping_progress_bar_location_checkout' ) ) { return $fragments; }

		// Get HTML for the free shipping bar
		ob_start();
		$free_shipping_bar_instance->render_shipping_progress_bar_with_wrapper();
		$html = ob_get_clean();

		$fragments['.wd-shipping-progress-bar'] = $html;
		return $fragments;
	}

}

FluidCheckout_ThemeCompat_Woodmart::instance();
