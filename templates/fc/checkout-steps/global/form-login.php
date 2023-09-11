<?php
/**
 * Login form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     7.0.1
 * @fc-version  3.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( is_user_logged_in() ) {
	return;
}

// CHANGE: Define unique id for the login inputs
$unique_id = '_' . uniqid();
$unique_id = apply_filters( 'fc_checkout_login_fields_unique_id', $unique_id );

?>
<form class="woocommerce-form woocommerce-form-login login" method="post" <?php echo ( $hidden ) ? 'style="display:none;"' : ''; ?>>

	<?php do_action( 'woocommerce_login_form_start' ); ?>

	<?php echo ( $message ) ? wpautop( wptexturize( $message ) ) : ''; // @codingStandardsIgnoreLine ?>

	<?php // CHANGE: Form row class to `form-row-wide` ?>
	<p class="form-row form-row-wide">
		<?php // CHANGE: Add unique id to the fields label and input element ?>
		<label for="username<?php echo esc_attr( $unique_id ); ?>"><?php esc_html_e( 'Username or email', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="input-text" name="username" id="username<?php echo esc_attr( $unique_id ); ?>" autocomplete="username" />
	</p>
    <?php // CHANGE: Form row class to `form-row-wide` ?>
	<p class="form-row form-row-wide">
		<?php // CHANGE: Add unique id to the fields label and input element ?>
		<label for="password<?php echo esc_attr( $unique_id ); ?>"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
		<input class="input-text woocommerce-Input" type="password" name="password" id="password<?php echo esc_attr( $unique_id ); ?>" autocomplete="current-password" />
	</p>
	<div class="clear"></div>

	<?php do_action( 'woocommerce_login_form' ); ?>

	<p class="form-row">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
			<?php // CHANGE: Add unique id to the fields label and input element ?>
			<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme<?php echo esc_attr( $unique_id ); ?>" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
		</label>
	</p>

    <?php // CHANGE: Move login button to its own section ?>
    <p class="fc-login-button">
        <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
		<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ); ?>" />
		<?php // CHANGE: Change login button label to be consistent across checkout ?>
		<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Login', 'woocommerce' ); ?>"><?php echo esc_html( apply_filters( 'fc_checkout_login_button_label', _x( 'Log in', 'Log in link label at checkout contact step', 'fluid-checkout' ) ) ); ?></button>
    </p>

	<p class="lost_password">
		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
	</p>

	<div class="clear"></div>

	<?php do_action( 'woocommerce_login_form_end' ); ?>

</form>
