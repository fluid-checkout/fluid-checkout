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

		// Header elements
		add_action( 'fc_checkout_header', array( $this, 'maybe_output_woodmart_checkout_steps_section' ), 20 );

		// Template files
		add_filter( 'fc_override_template_with_theme_file', array( $this, 'override_template_with_theme_file' ), 10, 4 );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {
		// Define positions for new settings
		$index = count( $settings ) - 1;

		// Define setting to insert
		$insert_settings = array(
			array(
				'title'           => __( 'Theme Woodmart', 'fluid-checkout' ),
				'desc'            => __( 'Output the checkout steps section from the Woodmart theme when using Fluid Checkout header and footer', 'fluid-checkout' ),
				'id'              => 'fc_compat_theme_woodmart_output_checkout_steps_section',
				'type'            => 'checkbox',
				'default'         => 'no',
				'autoload'        => false,
			),
		);

		// Get token position
		$position_index = count( $settings ) - 1;

		// Insert at token position
		$new_settings = array_slice( $settings, 0, $position_index );
		$new_settings = array_merge( $new_settings, $insert_settings );
		$new_settings = array_merge( $new_settings, array_slice( $settings, $position_index, count( $settings ) ) );

		return $new_settings;
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
		if ( 'yes' !== get_option( 'fc_compat_theme_woodmart_output_checkout_steps_section', 'no' ) ) { return; }

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

}

FluidCheckout_ThemeCompat_Woodmart::instance();
