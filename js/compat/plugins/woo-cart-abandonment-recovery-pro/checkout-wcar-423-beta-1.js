/**
 * Compatibility with Woo Cart Abandonment Recovery Pro (by CartFlows)
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCWCARCheckout = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		checkboxBlockSelector: '#wcf_cf_gdpr_phone_message_block',
		checkboxSelector: '#gdpr_phone_consent',
		checkboxBoundAttribute: 'data-fc-wcar-gdpr-bound',
		checkboxFieldName: 'wcf_gdpr_phone_consent',
		phoneSelectors: '#billing_phone, #billing-phone, #shipping_phone, #shipping-phone, #phone',
		fieldWrapperSelector: '.form-row, .wc-block-components-text-input, .wc-block-components-phone-number-input',
		checkoutFormSelector: 'form[name="checkout"]',
		gdprPhoneMessagePlaceholderSelector: '#fc-wcar-gdpr-phone-message-placeholder',
		gdprPhoneConsentHiddenSelector: '#fc-wcar-gdpr-phone-consent-hidden',
		invalidClassNames: [ 'woocommerce-invalid', 'woocommerce-invalid-phone', 'woocommerce-invalid-required-field' ],
		updateCheckoutCooldownMs: 300,
		enableIntlPhoneWcarCompat: false,
		abandonmentTrackingAction: 'cartflows_save_cart_abandonment_data',
	};
	var _isSyncingCheckout = false;
	var _isConsentChecked = false;
	var _hasAbandonmentAjaxPatched = false;

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
	 * Load settings from localized script data.
	 */
	var loadSettings = function() {
		// Bail if localized settings are not available
		if ( typeof root.fcWcarCheckoutSettings === 'undefined' || ! root.fcWcarCheckoutSettings ) { return; }

		// Maybe enable international phone compatibility with WCAR
		if ( root.fcWcarCheckoutSettings.enableIntlPhoneWcarCompat ) {
			_settings.enableIntlPhoneWcarCompat = true;
		}
	};

	/**
	 * Get the active checkout phone input element.
	 */
	var getActivePhoneInput = function() {
		// Try to scope lookup to the checkout form first
		var checkoutForm = document.querySelector( _settings.checkoutFormSelector );
		var phoneInputs = checkoutForm
			? checkoutForm.querySelectorAll( _settings.phoneSelectors )
			: document.querySelectorAll( _settings.phoneSelectors );

		// Bail if no phone inputs are found
		if ( ! phoneInputs || ! phoneInputs.length ) { return; }

		// Prefer the first visible phone field when multiple variants exist
		var selectedPhoneInput = null;
		for ( var i = 0; i < phoneInputs.length; i++ ) {
			// Get the current phone input
			var phoneInput = phoneInputs[ i ];
			var isVisible = phoneInput && phoneInput.offsetParent !== null;

			// Continue if the phone input is not visible
			if ( ! isVisible ) { continue; }

			// Set the selected phone input
			selectedPhoneInput = phoneInput;
			break;
		}

		// Otherwise use the first phone input
		if ( ! selectedPhoneInput && phoneInputs[ 0 ] ) {
			selectedPhoneInput = phoneInputs[ 0 ];
		}

		return selectedPhoneInput;
	};

	/**
	 * Sync FC international phone hidden full-number field for a phone input.
	 *
	 * @param   {HTMLInputElement}  phoneInput  Phone input element.
	 */
	var syncIntlPhoneHiddenField = function( phoneInput ) {
		// Bail if phone input or intl-tel-input instance is not available
		if ( ! phoneInput || ! phoneInput.iti ) { return; }

		// Get phone field instance and hidden full-number field
		var phoneField = phoneInput.iti;
		var hiddenFieldName = phoneInput.getAttribute( 'name' ) + '_full';
		var hiddenField = document.querySelector( 'input[name="' + hiddenFieldName + '"]' );

		// Bail if hidden field is not available
		if ( ! hiddenField ) { return; }

		// Get selected country dial code for fallback value
		var selectedCountry = phoneField.getSelectedCountryData();
		var selectedCountryCode = selectedCountry && selectedCountry.dialCode && 0 !== phoneInput.value.indexOf( '+' ) ? '+' + selectedCountry.dialCode : '';

		// Set hidden field value from validated full number
		hiddenField.value = phoneField.isValidNumber() ? phoneField.getNumber() : '';

		// Maybe append country code to full phone number
		if ( ! hiddenField.value && phoneInput.value ) {
			hiddenField.value = selectedCountryCode + phoneInput.value;
		}
	};

	/**
	 * Get national phone digits and dial code for WCAR abandonment tracking.
	 *
	 * @return  {Object|null}  Phone data for WCAR tracking, or null when unavailable.
	 */
	var getWcarPhoneDataForTracking = function() {
		// Get active phone input
		var phoneInput = getActivePhoneInput();

		// Bail if phone input or intl-tel-input instance is not available
		if ( ! phoneInput || ! phoneInput.iti ) { return null; }

		// Get phone field instance and selected country data
		var phoneField = phoneInput.iti;
		var selectedCountry = phoneField.getSelectedCountryData();

		// Bail if selected country dial code is not available
		if ( ! selectedCountry || ! selectedCountry.dialCode ) { return null; }

		// Sync hidden full-number field before reading phone data
		syncIntlPhoneHiddenField( phoneInput );

		// Get dial code and national number for WCAR tracking
		var dialCode = '+' + selectedCountry.dialCode;
		var nationalNumber = '';

		// Maybe format national number from validated E.164 value
		if ( typeof intlTelInput !== 'undefined' && intlTelInput.utils && phoneField.isValidNumber() ) {
			var e164Number = phoneField.getNumber();
			var formattedNationalNumber = intlTelInput.utils.formatNumber( e164Number, selectedCountry.iso2, intlTelInput.utils.numberFormat.NATIONAL );
			nationalNumber = formattedNationalNumber.replace( /\D/g, '' );
		}

		// Otherwise strip non-digits from visible phone input value
		if ( ! nationalNumber ) {
			nationalNumber = phoneInput.value.replace( /\D/g, '' );
		}

		// Bail if national number is empty
		if ( ! nationalNumber ) { return null; }

		// Return phone data expected by WCAR abandonment tracking
		return {
			wcf_phone: nationalNumber,
			wcf_phone_country_code: dialCode,
		};
	};

	/**
	 * Maybe enrich WCAR abandonment tracking AJAX payload with international phone data.
	 *
	 * @param   {Object}  data  AJAX request data.
	 */
	var maybeEnrichAbandonmentTrackingData = function( data ) {
		// Bail if international phone compatibility is disabled
		if ( ! _settings.enableIntlPhoneWcarCompat ) { return; }

		// Get phone data for WCAR tracking
		var phoneData = getWcarPhoneDataForTracking();

		// Bail if phone data is not available
		if ( ! phoneData ) { return; }

		// Enrich abandonment tracking payload with phone data
		data.wcf_phone = phoneData.wcf_phone;
		data.wcf_phone_country_code = phoneData.wcf_phone_country_code;
	};

	/**
	 * Patch jQuery AJAX to enrich WCAR abandonment tracking requests.
	 */
	var maybePatchAbandonmentTrackingAjax = function() {
		// Bail if jQuery is not available, compatibility is disabled, or AJAX is already patched
		if ( ! _hasJQuery || ! _settings.enableIntlPhoneWcarCompat || _hasAbandonmentAjaxPatched ) { return; }

		// Store original jQuery AJAX handler
		var originalAjax = $.ajax;

		// Patch jQuery AJAX to enrich WCAR abandonment tracking requests
		$.ajax = function( options ) {
			// Maybe enrich abandonment tracking payload before request is sent
			if (
				options
				&& options.data
				&& typeof options.data === 'object'
				&& options.data.action === _settings.abandonmentTrackingAction
			) {
				maybeEnrichAbandonmentTrackingData( options.data );
			}

			// Call original jQuery AJAX handler
			return originalAjax.call( this, options );
		};

		// Set AJAX patched flag
		_hasAbandonmentAjaxPatched = true;
	};

	/**
	 * Get the checkout phone field wrapper element.
	 */
	var getPhoneFieldWrapper = function() {
		// Get active phone input
		var selectedPhoneInput = getActivePhoneInput();

		// Bail if no phone input is found
		if ( ! selectedPhoneInput ) { return; }

		// Return the closest field wrapper used by the active checkout layout.
		return selectedPhoneInput.closest( _settings.fieldWrapperSelector ) || selectedPhoneInput.parentElement;
	};

	/**
	 * Build GDPR checkbox block when not already present.
	 */
	var buildCheckboxBlock = function() {
		// Bail if the consent message is disabled or unavailable.
		if ( ! shouldShow() || ! root.wcf_ca_vars._gdpr_phone_message ) { return; }

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
		message.innerHTML = root.wcf_ca_vars._gdpr_phone_message; // Update with message from the original plugin, which is a trusted source for this.

		label.appendChild( checkbox );
		label.appendChild( message );
		block.appendChild( label );

		return block;
	};

	/**
	 * Get the hidden field that persists consent in checkout post data.
	 */
	var getGdprPhoneConsentHiddenField = function() {
		var checkoutForm = document.querySelector( _settings.checkoutFormSelector );
		if ( ! checkoutForm ) { return null; }

		return checkoutForm.querySelector( _settings.gdprPhoneConsentHiddenSelector );
	};

	/**
	 * Check whether a consent field value represents a checked state.
	 */
	var isConsentValueChecked = function( value ) {
		return 'on' === value || '1' === value || 1 === value;
	};

	/**
	 * Keep the hidden consent field aligned with the checkbox checked state.
	 */
	var syncHiddenConsentField = function( checkbox ) {
		var hiddenField = getGdprPhoneConsentHiddenField();
		if ( ! hiddenField || ! checkbox ) { return; }
		hiddenField.value = checkbox.checked ? 'on' : '';
	};

	/**
	 * Sync internal consent state from the hidden field or checkbox.
	 */
	var syncConsentStateFromPersistedField = function() {
		var hiddenField = getGdprPhoneConsentHiddenField();

		if ( hiddenField ) {
			_isConsentChecked = isConsentValueChecked( hiddenField.value );
			return;
		}

		var currentCheckbox = document.querySelector( _settings.checkboxSelector );
		if ( currentCheckbox ) {
			_isConsentChecked = currentCheckbox.checked;
		}
	};

	/**
	 * Keep all consent checkboxes synced in case duplicate elements exist.
	 */
	var syncCheckboxesState = function() {
		var allCheckboxes = document.querySelectorAll( _settings.checkboxSelector );
		for ( var i = 0; i < allCheckboxes.length; i++ ) {
			allCheckboxes[ i ].checked = _isConsentChecked;
			syncHiddenConsentField( allCheckboxes[ i ] );
		}
	};

	/**
	 * Remove invalid classes from the phone field wrapper.
	 *
	 * @param {Element} fieldWrapper  Phone field wrapper element.
	 */
	var removeInvalidClassesFromWrapper = function( fieldWrapper ) {
		if ( ! fieldWrapper || ! fieldWrapper.classList ) { return; }

		for ( var i = 0; i < _settings.invalidClassNames.length; i++ ) {
			fieldWrapper.classList.remove( _settings.invalidClassNames[ i ] );
		}
	};

	/**
	 * Remove validation classes after WooCommerce validation handlers run.
	 *
	 * @param {Element} fieldWrapper  Phone field wrapper element.
	 */
	var cleanupValidationClasses = function( fieldWrapper ) {
		setTimeout( removeInvalidClassesFromWrapper.bind( null, fieldWrapper ), 10 );
	};

	/**
	 * Reset checkout sync flag after update_checkout cooldown.
	 */
	var resetCheckoutSyncFlag = function() {
		_isSyncingCheckout = false;
	};

	/**
	 * Trigger checkout update when consent checkbox changes from user interaction.
	 *
	 * @param {Event}  event  Change event from the consent checkbox.
	 */
	var maybeTriggerCheckoutUpdate = function( event ) {
		// Bail if event is not a change event or is not trusted
		if ( ! event || 'change' !== event.type ) { return; }

		// Bail if event is not trusted
		if ( event.isTrusted !== true ) { return; }

		// Bail if jQuery is not available or checkout form is not found
		if ( ! _hasJQuery || ! document.querySelector( _settings.checkoutFormSelector ) ) { return; }

		// Bail if checkout is already syncing
		if ( _isSyncingCheckout ) { return; }

		// Set syncing flag
		_isSyncingCheckout = true;

		// Trigger checkout update
		$( document.body ).trigger( 'update_checkout' );

		// Reset syncing flag after cooldown
		setTimeout( resetCheckoutSyncFlag, _settings.updateCheckoutCooldownMs );
	};

	/**
	 * Handle consent checkbox state change.
	 *
	 * @param {HTMLInputElement}  checkbox       Consent checkbox element.
	 * @param {Element}           fieldWrapper   Phone field wrapper element.
	 * @param {Event}             event          Change event from the consent checkbox.
	 */
	var handleCheckboxStateChange = function( checkbox, fieldWrapper, event ) {
		// Set consent checked state
		_isConsentChecked = checkbox.checked;

		// Sync checkboxes state
		syncCheckboxesState();

		// Clean up validation classes
		cleanupValidationClasses( fieldWrapper );

		// Maybe trigger checkout update
		maybeTriggerCheckoutUpdate( event );
	};

	/**
	 * Prevent residual WC field invalid classes from being kept when checking consent.
	 */
	var maybeBindCheckboxValidationCleanup = function( checkbox, fieldWrapper ) {
		// Bail if checkbox is not found
		if ( ! checkbox ) { return; }

		// Bail if checkbox is already bound
		if ( checkbox.getAttribute( _settings.checkboxBoundAttribute ) ) { return; }

		// Bind change event to handle checkbox state change
		checkbox.addEventListener( 'change', handleCheckboxStateChange.bind( null, checkbox, fieldWrapper ) );

		// Set checkbox checked state
		checkbox.checked = _isConsentChecked;

		// Sync hidden consent field
		syncHiddenConsentField( checkbox );

		// Set checkbox bound attribute
		checkbox.setAttribute( _settings.checkboxBoundAttribute, '1' );
	};

	/**
	 * Keep a single checkbox block and a single checkbox input instance.
	 */
	var maybeDedupeCheckboxElements = function() {
		// Get all blocks and checkboxes
		var allBlocks = document.querySelectorAll( _settings.checkboxBlockSelector );
		var allCheckboxes = document.querySelectorAll( _settings.checkboxSelector );

		// Bail if no blocks or checkboxes are found
		if ( ! allBlocks.length && ! allCheckboxes.length ) { return; }

		// Get the primary block
		var primaryBlock = null;

		// Loop through the blocks and look for the primary block
		for ( var i = 0; i < allBlocks.length; i++ ) {
			// Get the current block, and check if it contains the checkbox
			var currentBlock = allBlocks[ i ];
			var hasCheckbox = !! currentBlock.querySelector( _settings.checkboxSelector );

			// Continue if the block does not contain the checkbox
			if ( ! hasCheckbox ) { continue; }

			// Set the primary block
			primaryBlock = currentBlock;
			break;
		}

		// Otherwise use the first block
		if ( ! primaryBlock && allBlocks[ 0 ] ) {
			primaryBlock = allBlocks[ 0 ];
		}

		// Loop through the blocks and remove the non-primary blocks
		for ( var j = 0; j < allBlocks.length; j++ ) {
			// Continue if the block is the primary block
			if ( allBlocks[ j ] === primaryBlock ) { continue; }

			// Remove the block
			allBlocks[ j ].parentNode.removeChild( allBlocks[ j ] );
		}

		// Bail if no primary block is found
		if ( ! primaryBlock ) { return; }

		// Get the primary checkbox
		var primaryCheckbox = primaryBlock.querySelector( _settings.checkboxSelector );

		// If no primary checkbox is found, use the first checkbox
		if ( ! primaryCheckbox && allCheckboxes[ 0 ] ) {
			primaryBlock.appendChild( allCheckboxes[ 0 ] );
			primaryCheckbox = allCheckboxes[ 0 ];
		}

		// Re-query all checkboxes to ensure we have the correct ones
		allCheckboxes = document.querySelectorAll( _settings.checkboxSelector );

		// Loop through checkboxes and remove non-primary checkboxes
		for ( var k = 0; k < allCheckboxes.length; k++ ) {
			// Continue if the checkbox is the primary checkbox
			if ( allCheckboxes[ k ] === primaryCheckbox ) { continue; }

			// Remove the checkbox
			allCheckboxes[ k ].parentNode.removeChild( allCheckboxes[ k ] );
		}

		// Return the primary block
		return primaryBlock;
	};

	/**
	 * Handle checkout update by syncing consent state and repositioning checkbox.
	 */
	var handleUpdatedCheckout = function() {
		syncConsentStateFromPersistedField();
		maybeRepositionCheckbox();
	};

	/**
	 * Ensure checkbox exists and is positioned after phone field.
	 */
	var maybeRepositionCheckbox = function() {
		// Bail when WCAR Pro phone consent should not be shown.
		if ( ! shouldShow() ) { return; }

		// Sync consent state from hidden field or checkbox
		syncConsentStateFromPersistedField();

		// Get phone field wrapper
		var fieldWrapper = getPhoneFieldWrapper();

		// Bail if no field wrapper or parent node is found
		if ( ! fieldWrapper || ! fieldWrapper.parentNode ) { return; }

		// Get checkbox block
		var checkboxBlock = maybeDedupeCheckboxElements();
		if ( ! checkboxBlock ) {
			checkboxBlock = buildCheckboxBlock();
		}

		// Bail if no checkbox block is found
		if ( ! checkboxBlock ) { return; }

		// Get checkout form and shipping fields root
		var checkoutForm = document.querySelector( _settings.checkoutFormSelector );
		var shippingFieldsRoot = checkoutForm ? checkoutForm.querySelector( '.woocommerce-shipping-fields' ) : null;
		var gdprCheckboxPlaceholder = checkoutForm ? checkoutForm.querySelector( _settings.gdprPhoneMessagePlaceholderSelector ) : null;

		// Maybe append checkbox block to shipping fields root
		if ( shippingFieldsRoot && shippingFieldsRoot.contains( fieldWrapper ) ) {
			if ( gdprCheckboxPlaceholder && shippingFieldsRoot.contains( gdprCheckboxPlaceholder ) ) {
				gdprCheckboxPlaceholder.appendChild( checkboxBlock );
			}
			else {
				shippingFieldsRoot.appendChild( checkboxBlock );
			}
		}
		// Otherwise append checkbox block to parent node
		else {
			// Keep consent checkbox right after the resolved phone field wrapper (e.g. billing-only layouts).
			fieldWrapper.parentNode.insertBefore( checkboxBlock, fieldWrapper.nextSibling );
		}

		// Get checkbox
		var checkbox = checkboxBlock.querySelector( _settings.checkboxSelector );

		// Maybe set checkbox checked state
		if ( checkbox ) {
			checkbox.checked = _isConsentChecked;
		}

		// Sync hidden consent field
		syncHiddenConsentField( checkbox );

		// Maybe bind checkbox validation cleanup
		maybeBindCheckboxValidationCleanup( checkbox, fieldWrapper );
	};

	/**
	 * Initialize compatibility script.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized.
		if ( _hasInitialized ) { return; }

		// Load settings from localized script data
		loadSettings();

		// Patch jQuery AJAX for WCAR abandonment tracking
		maybePatchAbandonmentTrackingAjax();

		// Ensure checkbox is positioned on page load.
		maybeRepositionCheckbox();

		// Reposition after checkout updates replace fragments.
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', handleUpdatedCheckout );
		}

		_hasInitialized = true;
	};

	//
	// Public APIs
	//
	// Run before init() to patch AJAX as early as possible, before WCAR triggers abandonment tracking on page load.
	loadSettings();
	maybePatchAbandonmentTrackingAjax();

	return _publicMethods;

});
