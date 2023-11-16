/**
 * Rebuild floating labels for Goya theme.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.GoyaFloatLabels = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};



	/**
	 * METHODS
	 */



	/**
	 * Maybe rebuild floating labels component.
	 */
	var maybeRebuildFloatLabels = function() {
		var self = this,
				wpFormsEl = '.form-row input[type=text], .form-row input[type=password], .form-row input[type=email], .form-row input[type=number], .form-row input[type=tel], .form-row input[type=date], .form-row textarea, .form-row select, .comment-form textarea, .comment-form input[type=text], .comment-form input[type=password], .comment-form input[type=email]';
		
		$(wpFormsEl).each(function() {

			/// Skip some inputs
			if ( $(this).attr("id") == 'rating' || ( $(this).parents('form.cart').length && !$(this).hasClass('wc-pao-addon-field') ) || $(this).parents('.woocommerce-checkout-payment').length )
				return false;

			// Add form-row if not exist
			if (! $(this).parent().hasClass('form-row') && ! $(this).parents().hasClass('woocommerce-input-wrapper') ) {
				$(this).parent().addClass('form-row');
			}

			$(this).parents('.form-row').addClass('float-label');

			var $placeholder = $(this).attr('placeholder'),
					$label = $(this).parents('.form-row').find('label'),
					$val = $(this).val();

			$placeholder = (typeof $placeholder === 'undefined' || $placeholder === false) ? '' : $placeholder;

			// WC Add-ons plugin
			if ($(this).hasClass('wc-pao-addon-field')) {

				if ($(this).hasClass('wc-pao-addon-image-swatch-select')) return;

				$label = $(this).parent('.form-row').siblings('label.wc-pao-addon-name');
				if ($label.length) {
					$label.insertAfter($(this));
				}

			} else {

				$(this).insertBefore($label);

				if(! $label.length && $placeholder != '') {
					$(this).after('<label for="'+$(this).attr('name')+'" class="fl-label">'+ $placeholder+'</label>');
				} else {
					$label.addClass('fl-label');
				}
			}

			// Always floating for select boxes
			if ($val || $(this).is('select')) { $(this).parent('.form-row').addClass('has-val'); }

		});

		// Open select2 elements
		// CHANGE: Get reference to body element directly
		$( document.body ).on( 'click', '.fl-label', function() {
			$(this).parent().find('.select2-hidden-accessible').select2('open');
		});

		// CHANGE: Get reference to body element directly
		$( document.body ).on( 'blur change', wpFormsEl, function() {
			var $val = $(this).val();

			validateFields($(this));

			// Check for autofilled fields in checkout
			if ($(this).closest('form').hasClass('checkout')) {
				validateFields( $('.checkout .form-row input[type=text]'));
			}

		});
	
		function validateFields (element) {
			element.each(function() {
				var $val = $(this).val();

				if ($val || $(this).is('select')) { 
					$(this).parent('.form-row').addClass('has-val'); 
				} else {
					$(this).parent('.form-row').removeClass('has-val');
				}
			});
		}

		// CHANGE: Remove handling of NinjaForms as these are not supposed to be used on the checkout page

		// CHANGE: Get reference to body element directly
		$( document.body ).on( 'updated_cart_totals', function(){
			jQuery('input#coupon_code').each(function() {
				$(this).parents('.form-row').addClass('deplace');
				var $label = $(this).parents('.form-row').find('label');
				$(this).insertBefore($label);
				var $val = $(this).val();
				if ($val) { $(this).parent('.form-row').addClass('has-val'); }
			});
		});
	}

	/**
	 * Re-focus and keep value of the element that was previously focused.
	 */
	var maybe_refocus_element = function( currentFocusedElement, currentValue ) {
		// Bail if no element to focus
		if ( null === currentFocusedElement ) { return; }

		requestAnimationFrame( function() {
			var elementToFocus;

			// Try findind the `select2` focusable element
			if ( currentFocusedElement.matches( '.fc-select2-field' ) ) {
				elementToFocus = document.querySelector( '.form-row[id="' + currentFocusedElement.id + '"] .select2-selection' );
			}
			// Try findind the the current focused element after updating updated element by ID
			else if ( currentFocusedElement.id ) {
				elementToFocus = document.getElementById( currentFocusedElement.id );
			}
			// Try findind the updated element by classes
			else if ( currentFocusedElement.getAttribute( 'name' ) ) {
				var nameAttr = currentFocusedElement.getAttribute( 'name' );
				elementToFocus = document.querySelector( '[name="'+nameAttr+'"]' );
			}

			// Try setting focus if element is found
			if ( elementToFocus && elementToFocus !== document.activeElement ) {
				elementToFocus.focus();

				// Set keyboard track position back to that previously to update
				setTimeout( function(){
					// Try to set the same track position
					if( null !== elementToFocus.selectionStart && null !== elementToFocus.selectionEnd ) {
						if ( currentFocusedElement.selectionStart && currentFocusedElement.selectionEnd ) {
							elementToFocus.selectionStart = currentFocusedElement.selectionStart;
							elementToFocus.selectionEnd = currentFocusedElement.selectionEnd;
						}
						// Otherwise try set the track position to the end of the field
						// @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/setSelectionRange
						// @see https://html.spec.whatwg.org/multipage/input.html#concept-input-apply
						else {
							elementToFocus.selectionStart = elementToFocus.selectionEnd = Number.MAX_SAFE_INTEGER || 10000;
						}
					}
				}, 0 );
			}
		} );
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		if ( _hasJQuery ) {
			// Rebuild on updates
			// CHANGE: Get reference to body element directly
			$( document.body ).on( 'init_checkout updated_checkout', maybeRebuildFloatLabels );
		}

		// Rebuild on initialization
		setTimeout( maybeRebuildFloatLabels, 100 );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
