<?php
/**
 * The header for the checkout page.
 *
  * This template can be overridden by copying it to yourtheme/woocommerce/checkout/page-checkout-header.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 3.1.9
 */

defined( 'ABSPATH' ) || exit;

// Get custom attributes for the html element
$html_custom_attributes_esc = '';
$html_custom_attributes = apply_filters( 'fc_checkout_html_custom_attributes', array() );
if ( is_array( $html_custom_attributes ) ) {
	foreach ( $html_custom_attributes as $attribute => $attribute_value ) {
		$html_custom_attributes_esc .= ' ' . esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
	}
}

// Get custom attributes for the body element
$body_custom_attributes_esc = '';
$body_custom_attributes = apply_filters( 'fc_checkout_body_custom_attributes', array() );
if ( is_array( $body_custom_attributes ) ) {
	foreach ( $body_custom_attributes as $attribute => $attribute_value ) {
		$body_custom_attributes_esc .= ' ' . esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> <?php echo $html_custom_attributes_esc; // WPCS: XSS ok. ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<link rel="profile" href="http://gmpg.org/xfn/11">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

<?php if ( isset( $meta_theme_color ) ) : ?>
	<meta name="theme-color" content="<?php echo esc_attr( $meta_theme_color ); ?>">
<?php endif; ?>

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?> <?php echo $body_custom_attributes_esc; // WPCS: XSS ok. ?>>

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'fluid-checkout' ); ?></a>

<?php do_action( 'fc_checkout_header' ); ?>

<main id="main" class="content-area fc-main">
