/**
 * WooCommerce Script
 * 
 * Replaces the original WooCommerce `woocommerce.js`.
 */

/* global Cookies */
jQuery( function( $ ) {
	// Orderby
	$( '.woocommerce-ordering' ).on( 'change', 'select.orderby', function() {
		$( this ).closest( 'form' ).trigger( 'submit' );
	});

	// Target quantity inputs on product pages
	$( 'input.qty:not(.product-quantity input.qty)' ).each( function() {
		var min = parseFloat( $( this ).attr( 'min' ) );

		if ( min >= 0 && parseFloat( $( this ).val() ) < min ) {
			$( this ).val( min );
		}
	});

	var noticeID   = $( '.woocommerce-store-notice' ).data( 'noticeId' ) || '',
		cookieName = 'store_notice' + noticeID;

	// Check the value of that cookie and show/hide the notice accordingly
	if ( 'hidden' === Cookies.get( cookieName ) ) {
		$( '.woocommerce-store-notice' ).hide();
	} else {
		$( '.woocommerce-store-notice' ).show();
	}

	// Set a cookie and hide the store notice when the dismiss button is clicked
	$( '.woocommerce-store-notice__dismiss-link' ).on( 'click', function( event ) {
		Cookies.set( cookieName, 'hidden', { path: '/' } );
		$( '.woocommerce-store-notice' ).hide();
		event.preventDefault();
	});

	// CHANGE: Remove "Make form field descriptions toggle on focus."

	$( '.woocommerce-input-wrapper' ).on( 'click', function( event ) {
		// CHANGE: Exclude the 'show password' button from the event propagation block.
		if ( $( event.target ).closest( '.show-password-input' ) ) { return; }
		
		event.stopPropagation();
	} );

	// CHANGE: Remove "Make form field descriptions toggle on focus."

	// Common scroll to element code.
	$.scroll_to_notices = function( scrollElement ) {
		if ( scrollElement.length ) {
			$( 'html, body' ).animate( {
				scrollTop: ( scrollElement.offset().top - 100 )
			}, 1000 );
		}
	};

	// CHANGE: Extract password visibility icon code into a function.
	var handlePasswordVisibility = function() {
		// Show password visibility hover icon on woocommerce forms
		// CHANGE: Only wrap password inputs if they aren't already wrapped.
		$( '.woocommerce form .woocommerce-Input[type="password"]' ).each( function() {
			if ( 0 === $( this ).closest( '.password-input' ).length ) {
				$( this ).wrap( '<span class="password-input"></span>' );
			}
		} );

		// Add 'password-input' class to the password wrapper in checkout page.
		// CHANGE: Only wrap password inputs if they aren't already wrapped.
		$( $( '.woocommerce form input' ).filter(':password') ).each( function() {
			if ( 0 === $( this ).closest( '.password-input' ).length ) {
				$( this ).parent('span').addClass('password-input');
			}
		} );

		// CHANGE: Only add the password visibility icon if it doesn't already exist.
		$( '.password-input' ).each( function() {
			if ( 0 === $( this ).find( '.show-password-input' ).length ) {
				$( this ).append( '<span class="show-password-input"></span>' );
			}
		} );

		// CHANGE: Extracted show password click handler into a reusable function.
	}
	handlePasswordVisibility();
	// CHANGE: END - Extract password visibility icon code into a function.

	// CHANGE: Also run password visibility icon code after replacing checkout fragments.
	$( document.body ).on( 'updated_checkout', handlePasswordVisibility );

	// CHANGE: Extract show password click handler into a reusable function.
	var handleShowPasswordClick = function( e ) {
		if ( $( this ).hasClass( 'display-password' ) ) {
			$( this ).removeClass( 'display-password' );
		} else {
			$( this ).addClass( 'display-password' );
		}
		if ( $( this ).hasClass( 'display-password' ) ) {
			$( this ).siblings( ['input[type="password"]'] ).prop( 'type', 'text' );
		} else {
			$( this ).siblings( 'input[type="text"]' ).prop( 'type', 'password' );
		}
	}
	// CHANGE: END - Extract show password click handler into a reusable function.
	// CHANGE: Handle captured show password click handler button click.
	$( document.body ).on( 'click', '.show-password-input', handleShowPasswordClick );
});
