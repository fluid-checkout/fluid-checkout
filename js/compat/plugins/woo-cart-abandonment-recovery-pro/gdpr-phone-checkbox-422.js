/**
 * Keep WCAR Pro phone GDPR checkbox visible and positioned after the phone field.
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCWooCartAbandonmentRecoveryProGdprPhoneCheckbox = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _isConsentChecked = false;
	var _publicMethods = {};
	var _settings = {
		checkboxBlockSelector: '#wcf_cf_gdpr_phone_message_block',
		checkboxSelector: '#gdpr_phone_consent',
		checkboxBoundAttribute: 'data-fc-wcar-gdpr-bound',
		phoneSelectors: '#billing_phone, #billing-phone, #shipping_phone, #shipping-phone, #phone',
		fieldWrapperSelector: '.form-row, .wc-block-components-text-input, .wc-block-components-phone-number-input',
		checkoutFormSelector: 'form[name="checkout"]',
		invalidClassNames: [ 'woocommerce-invalid', 'woocommerce-invalid-phone', 'woocommerce-invalid-required-field' ],
	};

	/**
	 * Whether the WCAR Pro phone checkbox should be displayed.
	 */
	var shouldShow = function() {
		return (
			typeof root.wcf_ca_vars !== 'undefined'
			&& root.wcf_ca_vars
			&& root.wcf_ca_vars._show_gdpr_phone_message === 'on'
		);
	};

	/**
	 * Get the checkout phone field wrapper element.
	 */
	var getPhoneFieldWrapper = function() {
		// Try to scope lookup to the checkout form first.
		var checkoutForm = document.querySelector( _settings.checkoutFormSelector );
		var phoneInputs = checkoutForm
			? checkoutForm.querySelectorAll( _settings.phoneSelectors )
			: document.querySelectorAll( _settings.phoneSelectors );
		if ( ! phoneInputs || ! phoneInputs.length ) { return null; }

		// Prefer the first visible phone field when multiple variants exist.
		var selectedPhoneInput = null;
		for ( var i = 0; i < phoneInputs.length; i++ ) {
			var phoneInput = phoneInputs[ i ];
			var isVisible = phoneInput && phoneInput.offsetParent !== null;

			if ( isVisible ) {
				selectedPhoneInput = phoneInput;
				break;
			}
		}

		if ( ! selectedPhoneInput && phoneInputs[ 0 ] ) {
			selectedPhoneInput = phoneInputs[ 0 ];
		}
		if ( ! selectedPhoneInput ) { return null; }

		// Return the closest field wrapper used by the active checkout layout.
		return selectedPhoneInput.closest( _settings.fieldWrapperSelector ) || selectedPhoneInput.parentElement;
	};

	/**
	 * Build GDPR checkbox block when not already present.
	 */
	var buildCheckboxBlock = function() {
		// Bail if the consent message is disabled or unavailable.
		if ( ! shouldShow() || ! root.wcf_ca_vars._gdpr_phone_message ) { return null; }

		// Build the same checkbox structure expected by checkout styles.
		var block = document.createElement( 'p' );
		block.className = 'wcar-gdpr-phone-checkbox form-row form-row-wide fc-checkbox-field fc-no-validation-icon';
		block.id = 'wcf_cf_gdpr_phone_message_block';

		var label = document.createElement( 'label' );
		label.className = 'checkbox';
		label.style.fontWeight = '400';

		var checkbox = document.createElement( 'input' );
		checkbox.type = 'checkbox';
		checkbox.id = 'gdpr_phone_consent';
		checkbox.className = 'input-checkbox';
		checkbox.value = 'on';

		var message = document.createElement( 'span' );
		message.className = 'fc-checkbox-label-text';
		message.innerHTML = root.wcf_ca_vars._gdpr_phone_message;

		label.appendChild( checkbox );
		label.appendChild( message );
		block.appendChild( label );

		return block;
	};

	/**
	 * Keep consent field value aligned with checked state.
	 */
	var syncCheckboxValue = function( checkbox ) {
		if ( ! checkbox ) { return; }
		checkbox.value = checkbox.checked ? 'on' : '';
	};

	/**
	 * Sync internal consent state from currently rendered checkbox.
	 */
	var syncConsentStateFromDOM = function() {
		var currentCheckbox = document.querySelector( _settings.checkboxSelector );
		if ( currentCheckbox ) {
			_isConsentChecked = currentCheckbox.checked;
		}
	};

	/**
	 * Prevent residual WC field invalid classes from being kept when checking consent.
	 */
	var maybeBindCheckboxValidationCleanup = function( checkbox, fieldWrapper ) {
		if ( ! checkbox ) { return; }
		if ( checkbox.getAttribute( _settings.checkboxBoundAttribute ) ) { return; }

		// Keep all consent checkboxes synced in case duplicate elements exist.
		var syncCheckboxesState = function() {
			var allCheckboxes = document.querySelectorAll( _settings.checkboxSelector );
			for ( var i = 0; i < allCheckboxes.length; i++ ) {
				allCheckboxes[ i ].checked = _isConsentChecked;
				syncCheckboxValue( allCheckboxes[ i ] );
			}
		};

		// Remove validation classes after WooCommerce validation handlers run.
		var cleanupValidationClasses = function() {
			setTimeout( function() {
				if ( fieldWrapper && fieldWrapper.classList ) {
					for ( var i = 0; i < _settings.invalidClassNames.length; i++ ) {
						fieldWrapper.classList.remove( _settings.invalidClassNames[ i ] );
					}
				}
			}, 10 );
		};

		var handleCheckboxStateChange = function() {
			_isConsentChecked = checkbox.checked;
			syncCheckboxesState();
			cleanupValidationClasses();
		};

		checkbox.addEventListener( 'click', handleCheckboxStateChange );
		checkbox.addEventListener( 'change', handleCheckboxStateChange );
		checkbox.addEventListener( 'blur', handleCheckboxStateChange );
		checkbox.checked = _isConsentChecked;
		syncCheckboxValue( checkbox );
		checkbox.setAttribute( _settings.checkboxBoundAttribute, '1' );
	};

	/**
	 * Ensure checkbox exists and is positioned after phone field.
	 */
	var maybeRepositionCheckbox = function() {
		// Bail when WCAR Pro phone consent should not be shown.
		if ( ! shouldShow() ) { return; }
		syncConsentStateFromDOM();

		// Bail when the phone field wrapper cannot be resolved.
		var fieldWrapper = getPhoneFieldWrapper();
		if ( ! fieldWrapper || ! fieldWrapper.parentNode ) { return; }

		// Prefer the block that contains the first visible checkbox, if present.
		var checkboxBlock = null;
		var allCheckboxes = document.querySelectorAll( _settings.checkboxSelector );
		for ( var i = 0; i < allCheckboxes.length; i++ ) {
			var currentCheckbox = allCheckboxes[ i ];
			var isVisible = currentCheckbox && currentCheckbox.offsetParent !== null;
			if ( isVisible ) {
				checkboxBlock = currentCheckbox.closest( _settings.checkboxBlockSelector );
				break;
			}
		}

		// Fallback to the first existing block in the DOM.
		if ( ! checkboxBlock ) {
			checkboxBlock = document.querySelector( _settings.checkboxBlockSelector );
		}

		// Build checkbox block when no block is available.
		if ( ! checkboxBlock ) {
			checkboxBlock = buildCheckboxBlock();
		}

		if ( ! checkboxBlock ) { return; }

		// Keep consent checkbox right after the resolved phone field wrapper.
		fieldWrapper.parentNode.insertBefore( checkboxBlock, fieldWrapper.nextSibling );

		var checkbox = checkboxBlock.querySelector( _settings.checkboxSelector );
		if ( checkbox ) {
			checkbox.checked = _isConsentChecked;
		}
		syncCheckboxValue( checkbox );
		maybeBindCheckboxValidationCleanup( checkbox, fieldWrapper );
	};

	/**
	 * Initialize compatibility script.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized.
		if ( _hasInitialized ) { return; }

		// Ensure checkbox is positioned on page load.
		maybeRepositionCheckbox();

		// Reposition after checkout updates replace fragments.
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeRepositionCheckbox );
		}

		_hasInitialized = true;
	};

	//
	// Public APIs
	//
	return _publicMethods;

});
