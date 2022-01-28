<?php
/**
 * Checkout contact form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/fc/checkout/form-contact.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package fluid-checkout
 * @version 1.5.0
 * @wc-version 3.6.0
 * @wc-original checkout/form-billing.php
 */

defined( 'ABSPATH' ) || exit;
?>

<?php do_action( 'fc_checkout_before_contact_fields' ); ?>

<div class="fc-contact-fields fc-clearfix">

	<div class="fc-contact-fields__wrapper">
		<?php do_action( 'fc_checkout_contact_before_fields' ); ?>

		<?php // CHANGE: Only display fields in the contact substep fields list, field priority is set by the order in which the field keys are added to the contact field ids list ?>
		<?php
		// Check contact field ids list
		if ( is_array( $contact_field_ids ) ) {
			// Get all checkout fields
			$field_groups = $checkout->get_checkout_fields();
			
			// Iterate contact field ids
			foreach( $contact_field_ids as $field_key ) {
				foreach ( $field_groups as $group_key => $fields ) {
					// Check field exists
					if ( array_key_exists( $field_key, $fields ) ) {
						woocommerce_form_field( $field_key, $fields[ $field_key ], $checkout->get_value( $field_key ) );
					}
				}
			}
		}
		?>

		<?php do_action( 'fc_checkout_contact_after_fields' ); ?>
	</div>

</div>

<?php do_action( 'fc_checkout_after_contact_fields' ); ?>
