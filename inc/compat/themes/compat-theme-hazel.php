<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Hazel (by Select Themes).
 */
class FluidCheckout_ThemeCompat_Flatsome extends FluidCheckout {

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
		// Use theme's logo
		add_action( 'fc_checkout_header_logo', array( $this, 'output_checkout_header_logo' ), 10 );

		// page_header
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_progress_bar_attributes' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sidebar_attributes' ), 20 );
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
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( ! empty( $logo_image_dark ) ) : ?>
				<img src="<?php echo esc_url($logo_image_dark); ?>" alt="<?php esc_attr_e( 'Logo', 'hazel' ); ?>"/>
			<?php else: ?>
				<img src="<?php echo esc_url($logo_image); ?>" alt="<?php esc_attr_e( 'Logo', 'hazel' ); ?>"/>
			<?php endif; ?>
		</a>
		<?php
	}



	/**
	 * Change the attributes of the progress bar element.
	 *
	 * @param   array   $progress_bar_attributes    Progress bar html element attributes.
	 */
	public function change_progress_bar_attributes( $progress_bar_attributes ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $progress_bar_attributes; }

		$progress_bar_attributes['data-sticky-relative-to'] = 'header.page_header';

		return $progress_bar_attributes;
	}

	/**
	 * Change the attributes of the sidebar element.
	 *
	 * @param   array   $sidebar_attributes    Sidebar html element attributes.
	 */
	public function change_sidebar_attributes( $sidebar_attributes ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $sidebar_attributes; }

		$sidebar_attributes['data-sticky-relative-to'] = 'header.page_header';

		return $sidebar_attributes;
	}

}

FluidCheckout_ThemeCompat_Flatsome::instance();
