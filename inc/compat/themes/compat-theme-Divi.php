<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Divi (by Elegant Themes).
 */
class FluidCheckout_ThemeCompat_Divi extends FluidCheckout {

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

		// Page header
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
	}



	/**
	 * Output the theme logo on the plugin's checkout header.
	 */
	public function output_checkout_header_logo() {
        if ( function_exists( 'et_get_option' ) ) {
            $template_directory_uri   = get_template_directory_uri();
            $logo = ( $user_logo = et_get_option( 'divi_logo' ) ) && ! empty( $user_logo )
            ? $user_logo
            : $template_directory_uri . '/images/logo.png';

            // Get logo image size based on attachment URL.
            $logo_size   = et_get_attachment_size_by_url( $logo );
            $logo_width  = ( ! empty( $logo_size ) && is_numeric( $logo_size[0] ) )
                    ? $logo_size[0]
                    : '93'; // 93 is the width of the default logo.
            $logo_height = ( ! empty( $logo_size ) && is_numeric( $logo_size[1] ) )
                    ? $logo_size[1]
                    : '43'; // 43 is the height of the default logo.

            ob_start();
            ?>
                <a href="<?php echo esc_url( apply_filters( 'fc_checkout_header_logo_home_url', home_url( '/' ) ) ); ?>">
                    <img src="<?php echo esc_attr( $logo ); ?>" width="<?php echo esc_attr( $logo_width ); ?>" height="<?php echo esc_attr( $logo_height ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" id="logo" data-height-percentage="<?php echo esc_attr( et_get_option( 'logo_height', '54' ) ); ?>" />
                </a>
            <?php
            $logo_container = ob_get_clean();
            echo $logo_container;
        }
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '#main-header';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Divi::instance();
