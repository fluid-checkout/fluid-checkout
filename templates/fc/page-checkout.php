<?php
/**
 * The checkout template file.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/fc/checkout/page-checkout.php.
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

// Replace site header with our implementation
if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) {
	wc_get_template( 'fc/header-checkout.php' );
}
// Display the site's default header
else {
	get_header( 'checkout' );
}
?>
<div class="fc-content <?php echo esc_attr( apply_filters( 'fc_content_section_class', '' ) ); ?>">

	<h1 class="fc-checkout__title <?php echo false === apply_filters( 'fc_display_checkout_page_title', false ) ? 'screen-reader-text' : ''; ?>"><?php the_title(); ?></h1>

	<?php
	// Load the checkout page content
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>

</div>

<?php
// Replace site footer with our implementation
if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) {
	wc_get_template( 'fc/footer-checkout.php' );
}
// Display the site's default footer
else {
	get_footer( 'checkout' );
}
