<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Hazel (by Select Themes).
 */
class FluidCheckout_ThemeCompat_Hazel extends FluidCheckout {

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
		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Use theme's logo
		add_action( 'fc_checkout_header_logo', array( $this, 'output_checkout_header_logo' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Checkout page template
		add_filter( 'template_include', array( $this, 'checkout_page_template' ), 100 );

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template_checkout_page_template' ), 100, 3 );

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $settings; }

		// Add settings
		$settings[ 'utils' ][ 'scrollOffsetSelector' ] = 'header.page_header';

		return $settings;
	}



	/**
	 * Output the theme logo on the plugin's checkout header.
	 */
	public function output_checkout_header_logo() {
		if ( ! function_exists( 'hazel_qode_return_global_options' ) ) { return; }

		// Get theme options
		$qode_options_hazel = hazel_qode_return_global_options();
		if ( isset( $qode_options_hazel['logo_image'] ) && $qode_options_hazel['logo_image'] != '' ) { $logo_image = $qode_options_hazel['logo_image']; } else { $logo_image =  get_template_directory_uri().'/img/logo.png'; };
		if ( isset( $qode_options_hazel['logo_image_dark'] ) && $qode_options_hazel['logo_image_dark'] != '' ) { $logo_image_dark = $qode_options_hazel['logo_image_dark']; } else { $logo_image_dark =  get_template_directory_uri().'/img/logo_black.png'; };
		?>
		<a href="<?php echo esc_url( apply_filters( 'fc_checkout_header_logo_home_url', home_url( '/' ) ) ); ?>">
			<?php if ( ! empty( $logo_image_dark ) ) : ?>
				<img src="<?php echo esc_url($logo_image_dark); ?>" alt="<?php esc_attr_e( 'Logo', 'hazel' ); ?>"/>
			<?php else: ?>
				<img src="<?php echo esc_url($logo_image); ?>" alt="<?php esc_attr_e( 'Logo', 'hazel' ); ?>"/>
			<?php endif; ?>
		</a>
		<?php
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = 'header.page_header';

		return $attributes;
	}



	/**
	 * Replace the checkout page template with our own file.
	 *
	 * @param   String  $template  Template file path.
	 */
	public function checkout_page_template( $template ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $template; }

		// Bail if checkout page template is not enabled
		if ( true !== apply_filters( 'fc_enable_checkout_page_template', true ) ) { return $template; }

		// Bail if not on checkout page.
		if( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $template; }

		// Locate new checkout page template
		$_template = $this->locate_template_checkout_page_template( $template, 'checkout/page-checkout.php', null );

		// Check if the file exists
		if ( file_exists( $_template ) ) {
			$template = $_template;
		}

		return $template;
	}



	/**
	 * Locate template files from this plugin.
	 */
	public function locate_template_checkout_page_template( $template, $template_name, $template_path ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $template; }
		
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = 'woocommerce/'; };

		// Get plugin path
		$plugin_path = self::$directory_path . 'templates/compat/themes/hazel/fc/checkout-page-template/';

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
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' container';
	}



	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '47px',
				'--fluidcheckout--field--padding-left' => '17px',
				'--fluidcheckout--field--background-color--accent' => '#ecae80',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '24px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Hazel::instance();
