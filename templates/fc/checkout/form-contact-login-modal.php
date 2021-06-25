<?php
/**
 * Checkout contact login substep
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/fc/checkout/form-contact-login-modal.php.
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

<div class="fc-login-form" data-flyout data-flyout-modal data-flyout-checkout-login>
	<div class="fc-login-form__inner" data-flyout-content>

		<div class="fc-login-form__close-wrapper">
			<a href="#close" class="button--flyout-close" title="<?php esc_attr_e( 'Close login form', 'fluidtheme' ) ?>" data-flyout-close aria-label="<?php echo esc_html( _x( 'Close', 'Close button aria-label', 'fluid-checkout' ) ); ?>"></a>
		</div>

		<div class="fc-login-form__title"><?php echo esc_html( __( 'Sign in to your account', 'fluid-checkout' ) ); ?></div>

		<?php woocommerce_login_form(); ?>

	</div>
</div>
