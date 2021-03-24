<?php
/**
 * The header for the checkout page.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package woocommerce-fluid-checkout
 * @version 1.2.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<link rel="profile" href="http://gmpg.org/xfn/11">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

<?php if ( isset( $meta_theme_color ) ) : ?>
    <meta name="theme-color" content="<?php echo esc_attr( $meta_theme_color ); ?>">
<?php endif; ?>

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'woocommerce-fluid-checkout' ); ?></a>
