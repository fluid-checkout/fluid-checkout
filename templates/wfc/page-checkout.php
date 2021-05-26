<?php
/**
 * The checkout template file.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/checkout/page-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package woocommerce-fluid-checkout
 * @version 1.2.0
 */

// Replace site header with our implementation
if ( FluidCheckout_Steps::instance()->get_hide_site_header_at_checkout() ) {
	wc_get_template( 'wfc/header-checkout.php' );
}
// Display the site's default header
else {
	get_header( 'checkout' );
}
?>
<main id="main" class="content-area wfc-content">

	<?php
	// Load the checkout page content
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>

</main>

<?php
// Replace site header with our implementation
if ( FluidCheckout_Steps::instance()->get_hide_site_footer_at_checkout() ) {
	wc_get_template( 'wfc/footer-checkout.php' );
}
// Display the site's default header
else {
	get_footer( 'checkout' );
}
