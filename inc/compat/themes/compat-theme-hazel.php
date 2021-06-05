<?php

/**
 * Compatibility with theme: Flatsome.
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

}

FluidCheckout_ThemeCompat_Flatsome::instance();
