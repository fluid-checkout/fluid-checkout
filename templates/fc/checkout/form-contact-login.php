<?php
/**
 * Checkout contact login substep
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/fc/checkout/form-contact-login.php.
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
?>

<div class="fc-contact-login">

	<div class="fc-contact-login__cta-text"><?php echo esc_html( apply_filters( 'fc_checkout_login_cta_text', __( 'Already have an account?', 'fluid-checkout' ) ) ); ?></div>

	<div class="fc-contact-login__action">
		<a href="<?php echo esc_url( add_query_arg( '_redirect', 'checkout', wc_get_account_endpoint_url( 'dashboard' ) ) ); ?>" class="fc-contact-login__action-login" data-flyout-toggle data-flyout-target="[data-flyout-checkout-login]"><?php echo esc_html( apply_filters( 'fc_checkout_login_button_label', __( 'Log in for faster checkout', 'Log in link label at checkout contact step', 'fluid-checkout' ) ) ); ?></a>
	</div>

	<div class="fc-contact-login__separator">
		<?php if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ) : ?>
			<span class="fc-contact-login__separator-text"><?php echo esc_html( apply_filters( 'fc_checkout_login_separator_text', __( 'Or continue as a guest', 'fluid-checkout' ) ) ); ?></span>
		<?php endif; ?>
	</div>

</div>
