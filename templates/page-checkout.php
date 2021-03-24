<?php
/**
 * The checkout template file.
 *
 * @package woocommerce-fluid-checkout
 * @version 1.2.0
 */


// Replace site header with our implementation
if ( 'true' === get_option( 'wfc_hide_site_header_at_checkout', 'true' ) ) {
	wc_get_template( 'header-checkout.php' );
}
// Display the site's default header
else {
	get_header( 'checkout' );
}
?>

<div id="content" class="site-content wfc-content">

	<main id="main" class="content-area">

		<?php
		// Load the checkout page content
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>

	</main>

</div>

<?php
// Replace site header with our implementation
if ( 'true' === get_option( 'wfc_hide_site_footer_at_checkout', 'true' ) ) {
	wc_get_template( 'footer-checkout.php' );
}
// Display the site's default header
else {
	get_footer( 'checkout' );
}
