/* global Cookies */
jQuery( function ( $ ) {
	// Orderby
	$( '.woocommerce-ordering' ).on( 'change', 'select.orderby', function () {
		$( this ).closest( 'form' ).trigger( 'submit' );
	} );

	// Target quantity inputs on product pages
	$( 'input.qty:not(.product-quantity input.qty)' ).each( function () {
		var min = parseFloat( $( this ).attr( 'min' ) );

		if ( min >= 0 && parseFloat( $( this ).val() ) < min ) {
			$( this ).val( min );
		}
	} );

	var noticeID = $( '.woocommerce-store-notice' ).data( 'noticeId' ) || '',
		cookieName = 'store_notice' + noticeID;

	// Check the value of that cookie and show/hide the notice accordingly
	if ( 'hidden' === Cookies.get( cookieName ) ) {
		$( '.woocommerce-store-notice' ).hide();
	} else {
		$( '.woocommerce-store-notice' ).show();
		/**
		 * After adding the role="button" attribute to the
		 * .woocommerce-store-notice__dismiss-link element,
		 * we need to add the keydown event listener to it.
		 */
		function store_notice_keydown_handler( event ) {
			if ( [ 'Enter', ' ' ].includes( event.key ) ) {
				event.preventDefault();
				$( '.woocommerce-store-notice__dismiss-link' ).click();
			}
		}

		// Set a cookie and hide the store notice when the dismiss button is clicked
		function store_notice_click_handler( event ) {
			Cookies.set( cookieName, 'hidden', { path: '/' } );
			$( '.woocommerce-store-notice' ).hide();
			event.preventDefault();
			$( '.woocommerce-store-notice__dismiss-link' )
				.off( 'click', store_notice_click_handler )
				.off( 'keydown', store_notice_keydown_handler );
		}

		$( '.woocommerce-store-notice__dismiss-link' )
			.on( 'click', store_notice_click_handler )
			.on( 'keydown', store_notice_keydown_handler );
	}

	// CHANGE: Remove "Make form field descriptions toggle on focus."

	$( '.woocommerce-input-wrapper' ).on( 'click', function( event ) {
		// CHANGE: Exclude the 'show password' button from the event propagation block.
		if ( $( event.target ).closest( '.show-password-input' ).length ) { return; }
		event.stopPropagation();
	} );

	// CHANGE: Remove "Make form field descriptions toggle on focus." for keydown, click and focus events.

	// Common scroll to element code.
	$.scroll_to_notices = function ( scrollElement ) {
		if ( scrollElement.length ) {
			$( 'html, body' ).animate(
				{
					scrollTop: scrollElement.offset().top - 100,
				},
				1000
			);
		}
	};

	// CHANGE: Extract password visibility icon code into a function.
	var handlePasswordVisibility = function() {
		// CHANGE: Only wrap password inputs if they aren't already wrapped.
		// Show password visibility hover icon on woocommerce forms
		// CHANGE: Target all password fields, not only fields with class `woocommerce-Input`
		$( '.woocommerce form input[type="password"]' ).each( function() {
			// Skip if already wrapped
			if ( 0 < $( this ).closest( '.password-input' ).length ) { return; }

			// CHANGE: Apply to each input field separately
			$( this ).wrap(
				'<span class="password-input"></span>'
			);
		} );

		// Add 'password-input' class to the password wrapper in checkout page.
		$(
			$( '.woocommerce form input' )
				.filter( ':password' )
			)
			.each( function() {
				// Skip if already wrapped
				if ( 0 === $( this ).closest( '.password-input' ).length ) { return; }

				$( this ).parent('span').addClass('password-input');
			}
		);

		$( '.password-input' ).each( function () {
			// CHANGE: Only add the password visibility icon if it doesn't already exist.
			if ( 0 < $( this ).find( '.show-password-input' ).length ) { return; }

			const describedBy = $( this ).find( 'input' ).attr( 'id' );
			$( this ).append(
				'<button type="button" class="show-password-input" aria-label="' +
					woocommerce_params.i18n_password_show +
					'" aria-describedBy="' +
					describedBy +
					'"></button>'
			);
		} );
		// CHANGE: END - Only wrap password inputs if they aren't already wrapped.

		// CHANGE: Extracted show password click handler into a reusable function.
	}
	handlePasswordVisibility();
	// CHANGE: END - Extract password visibility icon code into a function.

	// CHANGE: Also run password visibility icon code after replacing checkout fragments.
	$( document.body ).on( 'updated_checkout', handlePasswordVisibility );

	// CHANGE: Extract show password click handler into a reusable function.
	var handleShowPasswordClick = function( event ) {
		event.preventDefault();

		if ( $( this ).hasClass( 'display-password' ) ) {
			$( this ).removeClass( 'display-password' );
			$( this ).attr(
				'aria-label',
				woocommerce_params.i18n_password_show
			);
		} else {
			$( this ).addClass( 'display-password' );
			$( this ).attr(
				'aria-label',
				woocommerce_params.i18n_password_hide
			);
		}
		if ( $( this ).hasClass( 'display-password' ) ) {
			$( this )
				.siblings( [ 'input[type="password"]' ] )
				.prop( 'type', 'text' );
		} else {
			$( this )
				.siblings( 'input[type="text"]' )
				.prop( 'type', 'password' );
		}

		$( this ).siblings( 'input' ).focus();
	}
	// CHANGE: Handle captured show password click handler button click.
	$( document.body ).on( 'click', '.show-password-input', handleShowPasswordClick );
	// CHANGE: END - Extract show password click handler into a reusable function.

	$( 'a.coming-soon-footer-banner-dismiss' ).on( 'click', function ( e ) {
		var target = $( e.target );
		$.ajax( {
			type: 'post',
			url: target.data( 'rest-url' ),
			data: {
				woocommerce_meta: {
					coming_soon_banner_dismissed: 'yes',
				},
			},
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader(
					'X-WP-Nonce',
					target.data( 'rest-nonce' )
				);
			},
			complete: function () {
				$( '#coming-soon-footer-banner' ).hide();
			},
		} );
	} );

	// If the "Enable AJAX add to cart buttons on archives" setting is disabled
	// the add-to-cart.js file won't be loaded, so we need to add the event listener here.
	if ( typeof wc_add_to_cart_params === 'undefined' ) {
		$( document.body ).on(
			'keydown',
			'.remove_from_cart_button',
			on_keydown_remove_from_cart
		);
	}

	$( document.body ).on(
		'item_removed_from_classic_cart updated_wc_div',
		focus_populate_live_region
	);
} );

/**
 * Handle when pressing the Space key on the remove item link.
 * This is necessary because the link has the role="button" attribute
 * and needs to act like a button.
 */
function on_keydown_remove_from_cart( event ) {
	if ( event.key === ' ' ) {
		event.preventDefault();
		event.currentTarget.click();
	}
}

/**
 * Focus on the first notice element on the page.
 *
 * Populated live regions don't always are announced by screen readers.
 * This function focus on the first notice message with the role="alert"
 * attribute to make sure it's announced.
 */
function focus_populate_live_region() {
	var noticeClasses = [
		'woocommerce-message',
		'woocommerce-error',
		'wc-block-components-notice-banner',
	];
	var noticeSelectors = noticeClasses
		.map( function ( className ) {
			return '.' + className + '[role="alert"]';
		} )
		.join( ', ' );
	var noticeElements = document.querySelectorAll( noticeSelectors );

	if ( noticeElements.length === 0 ) {
		return;
	}

	var firstNotice = noticeElements[ 0 ];

	firstNotice.setAttribute( 'tabindex', '-1' );

	// Wait for the element to get the tabindex attribute so it can be focused.
	var delayFocusNoticeId = setTimeout( function () {
		firstNotice.focus();
		clearTimeout( delayFocusNoticeId );
	}, 500 );
}

/**
 * Refresh the sorted by live region.
 *
 * Skips when the Interactivity API product filters are present on the page,
 * as those manage the result count updates themselves.
 */
function refresh_sorted_by_live_region() {
	var sorted_by_live_region = document.querySelector(
		'.woocommerce-result-count'
	);
	var hasInteractivityFilters = document.querySelector(
		'[data-wp-interactive="woocommerce/product-filters"]'
	);

	if (
		! sorted_by_live_region ||
		! window.location.search ||
		hasInteractivityFilters
	) {
		return;
	}

	var text = sorted_by_live_region.innerHTML;
	sorted_by_live_region.setAttribute( 'role', 'alert' );
	sorted_by_live_region.setAttribute( 'aria-hidden', 'true' );

	var sorted_by_live_region_id = setTimeout( function () {
		sorted_by_live_region.setAttribute( 'aria-hidden', 'false' );
		sorted_by_live_region.innerHTML = '';
		sorted_by_live_region.innerHTML = text;
		clearTimeout( sorted_by_live_region_id );
	}, 2000 );
}

function on_document_ready() {
	focus_populate_live_region();
	refresh_sorted_by_live_region();
}

document.addEventListener( 'DOMContentLoaded', on_document_ready );
