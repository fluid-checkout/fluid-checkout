<?php
/**
 * The header for the checkout page.
 *
  * This template can be overridden by copying it to yourtheme/woocommerce/checkout/header-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

// CHANGED: Copied code from theme template file header.php
global $avia_config;

$lightbox_option = avia_get_option( 'lightbox_active' );
$avia_config['use_standard_lightbox'] = empty( $lightbox_option ) || ( 'lightbox_active' == $lightbox_option ) ? 'lightbox_active' : 'disabled';

/**
 * Allow to overwrite the option setting for using the standard lightbox
 * Make sure to return 'disabled' to deactivate the standard lightbox - all checks are done against this string
 *
 * @added_by Günter
 * @since 4.2.6
 * @param string $use_standard_lightbox				'lightbox_active' | 'disabled'
 * @return string									'lightbox_active' | 'disabled'
 */
$avia_config['use_standard_lightbox'] = apply_filters( 'avf_use_standard_lightbox', $avia_config['use_standard_lightbox'] );


$style = $avia_config['box_class'];
$responsive = avia_get_option( 'responsive_active' ) != 'disabled' ? 'responsive' : 'fixed_layout';
$blank = isset( $avia_config['template'] ) ? $avia_config['template'] : '';
$preloader = avia_get_option( 'preloader' ) == 'preloader' ? 'av-preloader-active av-preloader-enabled' : 'av-preloader-disabled';

$html_classes = array();
$body_classes = array();

$html_classes[] = "html_{$style}";
$html_classes[] = $responsive;
$html_classes[] = $preloader;
$html_classes[] = avia_header_class_filter( avia_header_class_string() );
$html_classes[] = $avia_config['use_standard_lightbox'] != 'disabled' ? 'av-default-lightbox' : 'av-custom-lightbox';
$html_classes[] = 'av-no-preview'; /*required for live previews*/

$body_classes[] = $style;
$body_classes[] = $blank;
$body_classes[] = avia_get_option( 'sidebar_styling' );

/**
 * Get footer stylings and post overrides
 */
$the_id = avia_get_the_id(); //use avia get the id instead of default get id. prevents notice on 404 pages
$body_layout = avia_get_option( 'color-body_style' );
$footer_options = avia_get_option( 'display_widgets_socket', 'all' );
$footer_behavior = avia_get_option( 'footer_behavior' );
$footer_media = avia_get_option( 'curtains_media' );

$footer_post = get_post_meta( $the_id, 'footer', true );
$footer_behavior_post = get_post_meta( $the_id, 'footer_behavior', true );

/**
 * Reset individual page override to defaults if widget or page settings are different (user might have changed theme options)
 * (if user wants a page as footer he must select this in main options - on individual page it's only possible to hide the page)
 */
if( false !== strpos( $footer_options, 'page' ) )
{
    /**
     * User selected a page as footer in main options
     */
    if( ! in_array( $footer_post, array( 'page_in_footer_socket', 'page_in_footer', 'nofooterarea' ) ) )
    {
        $footer_post = '';
    }
}
else
{
    /**
     * User selected a widget based footer in main options
     */
    if( in_array( $footer_post, array( 'page_in_footer_socket', 'page_in_footer' ) ) )
    {
        $footer_post = '';
    }
}

$footer_option = ! empty( $footer_post ) ? $footer_post : $footer_options;

switch ( $footer_behavior_post )
{
    case 'scroll':
        $footer_behavior = '';
        break;
    case 'curtain_footer':
        $footer_behavior = 'curtain_footer';
        break;
    default:
        break;
}

if( 'stretched' != $body_layout )
{
    $footer_behavior = '';
    $footer_media = '';
}
else
{
    if( empty( $footer_media ) )
    {
        $footer_media = '70';
    }
}

$avia_config['footer_option'] = $footer_option;
$avia_config['footer_behavior'] = $footer_behavior;
$avia_config['footer_media'] = $footer_media;

/**
 * If title attribute is missing for an image default lightbox displays the alt attribute
 *
 * @since 4.7.6.2
 * @param bool
 * @return false|mixed			anything except false will activate this feature
 */
$body_classes[] = false !== apply_filters( 'avf_lightbox_show_alt_text', false ) ? 'avia-mfp-show-alt-text' : '';

/**
 * Allows to alter default settings Enfold-> Main Menu -> General -> Menu Items for Desktop
 * @since 4.4.2
 */
$is_burger_menu = apply_filters( 'avf_burger_menu_active', avia_is_burger_menu(), 'header' );
$html_classes[] = $is_burger_menu ? 'html_burger_menu_active' : 'html_text_menu_active';

if( ! $is_burger_menu )
{
    $handling = avia_get_option( 'header_mobile_device_handling' );
    $html_classes[] = 'mobile_switch_portrait' != $handling ? 'av-mobile-menu-switch-default' : 'av-mobile-menu-switch-portrait';
}

/**
 * Add additional custom body classes
 * e.g. to disable default image hover effect add av-disable-avia-hover-effect
 *
 * @since 4.4.2
 */
$body_classes[] = apply_filters( 'avf_custom_body_classes', '' );

/**
 * @since 4.2.3 we support columns in rtl order (before they were ltr only). To be backward comp. with old sites use this filter.
 */
$body_classes[] = 'yes' == apply_filters( 'avf_rtl_column_support', 'yes' ) ? 'rtl_columns' : '';

/**
 * @since 4.8.6.3
 */
$body_classes[] = 'curtain_footer' == $avia_config['footer_behavior'] ? 'av-curtain-footer' : '';
$body_classes[] = is_numeric( $footer_media ) || empty( $footer_media ) ? 'av-curtain-numeric' : "av-curtain-screen {$footer_media}";


$html_classes = implode( ' ', array_unique( array_filter( $html_classes ) ) );

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="<?php echo $html_classes; ?>">
<?php // CHANGED: END - Copied code from theme template file header.php ?>

<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<link rel="profile" href="http://gmpg.org/xfn/11">

<?php // CHANGED: Remove meta `viewport` as is supposed to be added by the theme, code from theme copied below ?>

<?php if ( isset( $meta_theme_color ) ) : ?>
    <meta name="theme-color" content="<?php echo esc_attr( $meta_theme_color ); ?>">
<?php endif; ?>

<?php // CHANGED: Copied code from theme template file header.php ?>
<?php
/*
 * outputs a rel=follow or nofollow tag to circumvent google duplicate content for archives
 * located in framework/php/function-set-avia-frontend.php
 */
if( function_exists( 'avia_set_follow' ) )
{
    echo avia_set_follow();
}
?>

<!-- mobile setting -->
<?php
$meta_viewport = ( strpos( $responsive, 'responsive' ) !== false ) ?  '<meta name="viewport" content="width=device-width, initial-scale=1">' : '';

/**
 * @since 4.7.6.4
 * @param string
 * @return string
 */
echo apply_filters( 'avf_header_meta_viewport', $meta_viewport );
?>
<?php // CHANGED: END - Copied code from theme template file header.php ?>

<?php wp_head(); ?>
</head>

<?php // CHANGED: Copied code from theme template file header.php ?>
<?php
$body_classes[] = $avia_config['font_stack'];
$body_classes = implode( ' ', array_unique( array_filter( $body_classes ) ) );
?>
<body id="top" <?php body_class( $body_classes ); avia_markup_helper( array( 'context' => 'body' ) ); ?>>

    <?php
	/**
	 * WP 5.2 add a new function - stay backwards compatible with older WP versions and support plugins that use this hook
	 * https://make.wordpress.org/themes/2019/03/29/addition-of-new-wp_body_open-hook/
	 *
	 * @since 4.5.6
	 */
	if( function_exists( 'wp_body_open' ) )
	{
		wp_body_open();
	}
	else
	{
		do_action( 'wp_body_open' );
	}

	do_action( 'ava_after_body_opening_tag' );
	?>

<?php // CHANGED: END - Copied code from theme template file header.php ?>

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'fluid-checkout' ); ?></a>

<?php do_action( 'fc_checkout_header' ); ?>

<?php // CHANGED: Added class and attributes from theme ?>
<main id="main" class="content-area fc-main all_colors" data-scroll-offset="<?php echo avia_header_setting( 'header_scroll_offset' ); ?>">
