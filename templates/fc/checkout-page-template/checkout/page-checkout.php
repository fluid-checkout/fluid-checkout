<?php
/**
 * The checkout template file.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/page-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

// Replace header with our distraction free template
if ( class_exists( 'FluidCheckout_CheckoutPageTemplate' ) && FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) {
	wc_get_template( 'checkout/page-checkout-header.php' );
}
// Display the site's default header
else {
	get_header( 'checkout' );
}

// Get page title
/**
 * Filters the checkout page title.
 *
 * @since 4.0.0
 *
 * @param mixed $title Title text.
 */
$esc_title = sanitize_text_field( apply_filters( 'fc_checkout_page_title', get_the_title() ) );
?>

<?php
	/**
	 * Fires before the main checkout section wrapper.
	 *
	 * @since 3.1.9
	 */
	do_action( 'fc_checkout_before_main_section_wrapper' );
?>

<div class="fc-content <?php
	/**
	 * Filters extra CSS classes for the checkout content wrapper.
	 *
	 * @since 1.2.0
	 *
	 * @param string $classes CSS classes. Default empty string.
	 */
	echo esc_attr( apply_filters( 'fc_content_section_class', '' ) );
?>">

	<?php
		/**
		 * Fires before the main checkout section.
		 *
		 * @since 3.1.9
		 */
		do_action( 'fc_checkout_before_main_section' );
	?>

	<h1 class="fc-checkout__title <?php
		/**
		 * Filters whether the checkout page title is visible.
		 *
		 * @since 1.4.2
		 *
		 * @param bool $title Title text. Default false.
		 */
		echo false === apply_filters( 'fc_display_checkout_page_title', false ) ? 'screen-reader-text' : '';
	?>"><?php echo $esc_title; // PHPCS: XSS ok. ?></h1>

	<?php
	// Load the checkout page content
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>

	<?php
		/**
		 * Fires after the main checkout section.
		 *
		 * @since 3.1.9
		 */
		do_action( 'fc_checkout_after_main_section' );
	?>

</div>

<?php
	/**
	 * Fires after the main checkout section wrapper.
	 *
	 * @since 3.1.9
	 */
	do_action( 'fc_checkout_after_main_section_wrapper' );
?>

<?php
// Replace footer with our distraction free template
if ( class_exists( 'FluidCheckout_CheckoutPageTemplate' ) && FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) {
	wc_get_template( 'checkout/page-checkout-footer.php' );
}
// Display the site's default footer
else {
	get_footer( 'checkout' );
}
