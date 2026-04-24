/* global wp, pwsL10n, wc_password_strength_meter_params */
jQuery( function ( $ ) {
	'use strict';
	/**
	 * Password Strength Meter class.
	 */
	var wc_password_strength_meter = {
		/**
		 * Initialize strength meter actions.
		 */
		init: function () {
			$( document.body ).on(
				'keyup change',
				'form.register #reg_password, form.checkout #account_password, ' +
					'form.edit-account #password_1, form.lost_reset_password #password_1',
				this.strengthMeter
			);
			$( 'form.checkout #createaccount' ).trigger( 'change' );
		},

		/**
		 * Strength Meter.
		 */
		strengthMeter: function () {
			var wrapper = $(
					'form.register, form.checkout, form.edit-account, form.lost_reset_password'
				),
				// CHANGE: Remove stop checkout logic as it does not apply correctly with Fluid Checkout
				field = $(
					'#reg_password, #account_password, #password_1',
					wrapper
				);
				// CHANGE: Remove stop checkout logic as it does not apply correctly with Fluid Checkout

			wc_password_strength_meter.includeMeter( wrapper, field );

			// CHANGE: Trigger password strength meter without associating result to a variable
			wc_password_strength_meter.checkPasswordStrength(
				wrapper,
				field
			);

			// CHANGE: Remove stop checkout logic as it does not apply correctly with Fluid Checkout
		},

		/**
		 * Include meter HTML.
		 *
		 * @param {Object} wrapper
		 * @param {Object} field
		 */
		includeMeter: function ( wrapper, field ) {
			var meter = wrapper.find( '.woocommerce-password-strength' );

			if ( '' === field.val() ) {
				meter.hide();
				$( document.body ).trigger( 'wc-password-strength-hide' );
				field.removeAttr( 'aria-describedby' );
			} else if ( 0 === meter.length ) {
				var meter =
					'<div id="password_strength" class="woocommerce-password-strength" role="alert"></div>';
				var wrapper = field.parent();
				// If the field is inside a password-input, inject the meter after the password-input.
				if ( wrapper.is( '.password-input' ) ) {
					wrapper.after( meter );
				} else {
					field.after( meter );
				}
				field.attr( 'aria-describedby', 'password_strength' );
				$( document.body ).trigger( 'wc-password-strength-added' );
			} else {
				meter.show();
				$( document.body ).trigger( 'wc-password-strength-show' );
			}
		},

		/**
		 * Check password strength.
		 *
		 * @param {Object} field
		 *
		 * @return {Int}
		 */
		checkPasswordStrength: function ( wrapper, field ) {
			var meter = wrapper.find( '.woocommerce-password-strength' ),
				hint = wrapper.find( '.woocommerce-password-hint' ),
				hint_html =
					'<small class="woocommerce-password-hint">' +
					wc_password_strength_meter_params.i18n_password_hint +
					'</small>',
				strength = wp.passwordStrength.meter(
					field.val(),
					wp.passwordStrength.userInputDisallowedList()
				),
				error = '';

			// Reset.
			meter.removeClass( 'short bad good strong' );
			hint.remove();

			if ( meter.is( ':hidden' ) ) {
				return strength;
			}

			// Error to append
			if (
				strength <
				wc_password_strength_meter_params.min_password_strength
			) {
				error =
					' - ' +
					wc_password_strength_meter_params.i18n_password_error;
			}

			switch ( strength ) {
				case 0:
					meter
						.addClass( 'short' )
						.html( pwsL10n[ 'short' ] + error );
					meter.after( hint_html );
					break;
				case 1:
					meter.addClass( 'bad' ).html( pwsL10n.bad + error );
					meter.after( hint_html );
					break;
				case 2:
					meter.addClass( 'bad' ).html( pwsL10n.bad + error );
					meter.after( hint_html );
					break;
				case 3:
					meter.addClass( 'good' ).html( pwsL10n.good + error );
					break;
				case 4:
					meter.addClass( 'strong' ).html( pwsL10n.strong + error );
					break;
				case 5:
					meter.addClass( 'short' ).html( pwsL10n.mismatch );
					break;
			}

			return strength;
		},
	};

	wc_password_strength_meter.init();
} );
