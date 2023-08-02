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
		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false' );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Header elements
		add_action( 'fc_checkout_header', array( $this, 'maybe_output_woodmart_checkout_steps_section' ), 20 );

		// Template files
		add_filter( 'fc_override_template_with_theme_file', array( $this, 'override_template_with_theme_file' ), 10, 4 );

		// Free shipping bar
		add_action( 'wp', array( $this, 'init_free_shipping_bar_hooks' ), 150 );
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
		if ( woodmart_get_opt( 'shipping_progress_bar_location_checkout' ) ) {
			remove_action( 'woocommerce_checkout_before_customer_details', array( $free_shipping_bar_instance, 'render_shipping_progress_bar_with_wrapper' ), 10 );
			add_action( 'fc_checkout_before_steps', array( $free_shipping_bar_instance, 'render_shipping_progress_bar_with_wrapper' ), 5 ); // Right before coupon code section
		}
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
				'desc'            => __( 'Output the checkout steps section from the Woodmart theme when using Fluid Checkout header and footer', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_woodmart_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_compat_theme_woodmart_output_checkout_steps_section' ),
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
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if theme functions and classes are not available
		if ( ! function_exists( 'woodmart_get_opt' ) ) { return $css_variables; }

		// Get dark mode option from theme
		$dark = woodmart_get_opt( 'dark_version' );

		// Bail if not using the dark mode
		if ( ! $dark ) { return $css_variables; }

		// Add CSS variables
		$new_css_variables = array(
			':root' => FluidCheckout_DesignTemplates::instance()->get_css_variables_dark_mode(),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Woodmart::instance();
