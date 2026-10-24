(function () {
	// ===========
	// CSS helpers
	// ===========
	function injectCSS() {
		if (document.getElementById( 'wawp-flag-alignment-style' )) {
			return;
		}
		var style         = document.createElement( 'style' );
		style.id          = 'wawp-flag-alignment-style';
		style.type        = 'text/css';
		style.textContent = [
		/* --- SEARCH INPUT STYLE --- */
		'.iti__search-input { padding: 12px 15px !important; border: none !important; border-bottom: 1px solid #f3f4f6 !important; font-size: 14px !important; outline: none !important; width: 100% !important; box-sizing: border-box !important; }',

		/* --- FLAG OVERRIDE: Show Palestine flag for 48 Lands (il) --- */
		'.iti__il { background-position: -2848px 0 !important; }',

		/* --- DROPDOWN CONTAINER --- */
		'.iti__dropdown-content { border-radius: 12px !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; border: 1px solid #e5e7eb !important; margin-top: 5px !important; z-index: 99999 !important; background: #fff !important; }',
		'.iti__country { padding: 10px 15px !important; display: flex !important; align-items: center !important; gap: 10px !important; }',
		'.iti__country-name { font-size: 14px !important; color: #374151 !important; }',
		'.iti__dial-code { color: #9ca3af !important; font-size: 13px !important; }',

		/* --- FLAG BUTTON RESET (Defeat theme button styles) --- */
		'.iti button, .iti button.iti__selected-flag, .iti [type="button"].iti__selected-flag { ' +
		'background: transparent !important; ' +
		'background-color: transparent !important; ' +
		'border: none !important; ' +
		'border-width: 0 !important; ' +
		'box-shadow: none !important; ' +
		'padding: 0 !important; ' +
		'margin: 0 !important; ' +
		'width: auto !important; ' +
		'height: 100% !important; ' +
		'border-radius: 0 !important; ' +
		'color: inherit !important; ' +
		'outline: none !important; ' +
		'}',
		'.iti button:hover, .iti button:focus, .iti button:active { background: transparent !important; border: none !important; box-shadow: none !important; }',
		'.iti--flag-right .iti__country-container { left: auto !important; right: 0 !important; }',
		'.iti--flag-right input { padding-left: 12px !important; padding-right: 55px !important; direction: ltr !important; text-align: right !important; }',
		'.iti--flag-right .iti__selected-flag { flex-direction: row-reverse !important; }',
		'.iti--flag-right .iti__arrow { margin-right: 6px !important; margin-left: 0 !important; }',
		/* Improve dropdown for Right Alignment */
		'.iti--flag-right .iti__dropdown-content { right: 0 !important; left: auto !important; text-align: right !important; direction: rtl !important; }',
		'.iti--flag-right .iti__country-list { direction: rtl !important; }',
		'.iti--flag-right .iti__country { flex-direction: row !important; justify-content: flex-start !important; }',
		'.iti--flag-right .iti__flag { margin-left: 0 !important; margin-right: 0 !important; }',

		/* --- FORCE LEFT ALIGNMENT (Flag on left, Input starts left) --- */
		'.iti--flag-left .iti__country-container { left: 0 !important; right: auto !important; }',
		'.iti--flag-left input { padding-left: 55px !important; padding-right: 12px !important; direction: ltr !important; text-align: left !important; }',
		'.iti--flag-left .iti__selected-flag { flex-direction: row !important; }',
		'.iti--flag-left .iti__arrow { margin-left: 6px !important; margin-right: 0 !important; }',
		/* Improve dropdown for Left Alignment */
		'.iti--flag-left .iti__dropdown-content { left: 0 !important; right: auto !important; text-align: left !important; direction: ltr !important; }',
		'.iti--flag-left .iti__country-list { direction: ltr !important; }',
		'.iti--flag-left .iti__country { flex-direction: row !important; }',

		/* Keep your original status and chip styles */
		'.intl-tel-status{display:flex;align-items:center;gap:.5rem;margin-top:5px;font-size:.9em;}',
		'.intl-status-text{white-space:nowrap;}',
		'.intl-status-text.intl-green{color:green;}',
		'.intl-status-text.intl-red{color:red;}',
		'.intl-status-text.intl-blue{color:blue;}',
		'.intl-hidden{display:none!important;}',
		'.wawp-wa-chip{display:inline-flex;align-items:center;border:1px solid #d1d5db;border-radius:999px;padding:.15rem .55rem;font-size:.85em;line-height:1;gap:.35rem;}',
		'.wawp-wa-chip svg{width:14px;height:14px;display:inline-block;}',
		'.wawp-wa-chip--found{background:#ecfdf5;border-color:#a7f3d0;color:#065f46;}',
		'.wawp-wa-chip--not{background:#fef2f2;border-color:#fecaca;color:#991b1b;}',

		/* --- DRASTIC WOOCOMMERCE BLOCKS (GUTENBERG) FIXES --- */
		'.wc-block-checkout__billing-fields .iti, .wc-block-checkout__shipping-fields .iti, .wc-block-components-address-form .iti, .wc-block-components-phone-number .iti, .wc-block-components-phone-input .iti { display: block !important; width: 100% !important; border: none !important; }',
		'.wc-block-components-text-input.iti--allow-dropdown input { padding-top: 5px !important; padding-bottom: 5px !important; height: auto !important; min-height: 50px !important; }',

		/* Brutal Label Removal for Blocks */
		'.wc-block-components-phone-number label, .wc-block-components-phone-input label, .wc-block-components-text-input.iti--allow-dropdown label, ' +
		'.wc-block-components-text-input__label, .wc-block-components-phone-input .wc-block-components-text-input__label { ' +
		'display: none !important; ' +
		'visibility: hidden !important; ' +
		'height: 0 !important; ' +
		'width: 0 !important; ' +
		'overflow: hidden !important; ' +
		'position: absolute !important; ' +
		'z-index: -1 !important; ' +
		'}',
		'.iti button { border: none !important; box-shadow: none !important; }',

		'.iti--flag-left input { padding-left: 55px !important; }',
		'.iti--flag-right input { padding-right: 55px !important; }',

		/* Center flag vertically + Remove internal ITI borders & backgrounds globally */
		'.iti__country-container, .iti__selected-flag { border: none !important; border-right: none !important; border-left: none !important; background: transparent !important; background-color: transparent !important; outline: none !important; box-shadow: none !important; padding: 0 !important; }',
		'.iti__selected-flag:hover, .iti__selected-flag:focus, .iti__selected-flag:active { background: transparent !important; border: none !important; outline: none !important; box-shadow: none !important; }',
		'.iti__selected-flag::after, .iti__selected-flag::before { display: none !important; }',
		'.iti, .iti input, .iti__country-container { background: transparent !important; }',
		'.iti button.iti__selected-flag { border: none !important; background: transparent !important; box-shadow: none !important; width: auto !important; height: 100% !important; border-radius: 0 !important; }',
		'.iti--separate-dial-code .iti__selected-flag { background: transparent !important; border: none !important; }',

		/* Prevent number overlap with dial code in Blocks */
		'.iti--separate-dial-code .iti__selected-dial-code { margin-right: 8px !important; font-weight: 500 !important; }'
		].join( '' );
		document.head.appendChild( style );
	}
	injectCSS();

	// ===========
	// Settings / utils
	// ===========
	var settings = (typeof wawpIntlTelSettings !== 'undefined') ? wawpIntlTelSettings : {};
	if (typeof settings.enableCarrierLookup === 'undefined') {
		settings.enableCarrierLookup = true;
	}
	var LEGACY_UTILS_FALLBACK = settings.utilsUrl || '';

	var allowedCountriesArray = [];
	if (settings.allowedCountries) {
		if (Array.isArray(settings.allowedCountries)) {
			allowedCountriesArray = settings.allowedCountries;
		} else if (typeof settings.allowedCountries === 'object') {
			allowedCountriesArray = Object.values(settings.allowedCountries);
		}
	}

	var utilsLoaded  = ! ! window.intlTelInputUtils;
	var utilsLoading = false;

	function loadUtilsOnce(cb) {
		if (utilsLoaded) {
			cb && cb(); return; }
		if (utilsLoading) {
			var t = setInterval(
				function () {
					if (utilsLoaded) {
						clearInterval( t ); cb && cb(); }
				},
				50
			);
			return;
		}
		utilsLoading = true;

		if (window.intlTelInputUtils) {
			utilsLoaded = true; cb && cb(); return; }

		var src   = (settings.utilsUrl && settings.utilsUrl.indexOf( 'http' ) === 0) ? settings.utilsUrl : LEGACY_UTILS_FALLBACK;
		var s     = document.createElement( 'script' );
		s.src     = src;
		s.async   = true;
		s.onload  = function () {
			/* ready */ };
		s.onerror = function () {
			if (src !== LEGACY_UTILS_FALLBACK) {
				var s2   = document.createElement( 'script' );
				s2.src   = LEGACY_UTILS_FALLBACK;
				s2.async = true;
				document.head.appendChild( s2 );
			}
		};
		document.head.appendChild( s );

		var watch = setInterval(
			function () {
				if (window.intlTelInputUtils) {
					clearInterval( watch );
					utilsLoaded = true;
					cb && cb();
				}
			},
			50
		);

		setTimeout(
			function () {
				clearInterval( watch );
				utilsLoaded = true; // proceed even if blocked
				cb && cb();
			},
			4000
		);
	}

	function applyAlignmentClass(inputEl) {
		var align = (settings.countryCodeAlignment || 'auto');

		if (align === 'auto') {
			align = settings.isRTL ? 'right' : 'left';
		}

		var wrap = inputEl.closest( '.iti' );
		if ( ! wrap) {
			return;
		}

		if (align === 'right') {
			wrap.classList.add( 'iti--flag-right' );
			wrap.classList.remove( 'iti--flag-left' );
		} else {
			wrap.classList.add( 'iti--flag-left' );
			wrap.classList.remove( 'iti--flag-right' );
		}
	}

	// ===========
	// Status row helpers
	// ===========
	function ensureStatusBelow(inputEl) {
		var wrap = inputEl.closest( '.iti' );
		if ( ! wrap || ! wrap.parentNode) {
			return null;
		}

		var row = wrap.parentNode.querySelector( '.intl-tel-status' );
		if ( ! row) {
			row           = document.createElement( 'div' );
			row.className = 'intl-tel-status';
			// fixed layout: status text + carrier + WA chip
			var status        = document.createElement( 'span' );
			status.className  = 'intl-status-text';
			var carrier       = document.createElement( 'span' );
			carrier.className = 'intl-carrier';
			row.appendChild( status );
			row.appendChild( carrier );
			wrap.parentNode.insertBefore( row, wrap.nextSibling );
		}
		return row;
	}

	function setStatusText(row, text, tone) {
		var el = row.querySelector( '.intl-status-text' );
		if ( ! el) {
			return;
		}
		el.textContent = text || '';
		el.classList.remove( 'intl-green', 'intl-red', 'intl-blue' );
		if (tone === 'green') {
			el.classList.add( 'intl-green' );
		} else if (tone === 'red') {
			el.classList.add( 'intl-red' );
		} else if (tone === 'blue') {
			el.classList.add( 'intl-blue' );
		}
	}

	function showStatusText(row, show) {
		var el = row.querySelector( '.intl-status-text' );
		if ( ! el) {
			return;
		}
		if (show) {
			el.classList.remove( 'intl-hidden' ); } else {
			el.classList.add( 'intl-hidden' );
			}
	}

	function setCarrier(row, text) {
		var el = row.querySelector( '.intl-carrier' );
		if ( ! el) {
			return;
		}
		el.textContent = text || '';
	}

	function clearWhatsAppChip(row) {
		var chip = row.querySelector( '.wawp-wa-chip' );
		if (chip) {
			chip.remove();
		}
		// whenever chip is removed, allow the status text to be visible again
		showStatusText( row, true );
	}

	function setWhatsAppChip(row, state) {
		// state: 'found' | 'not' | 'clear'
		if (state === 'clear') {
			clearWhatsAppChip( row ); return; }

		var chip = row.querySelector( '.wawp-wa-chip' );
		if ( ! chip) {
			chip           = document.createElement( 'span' );
			chip.className = 'wawp-wa-chip';
			chip.innerHTML =
			'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2C6.56 2 2.1 6.45 2.1 11.93c0 2.09.56 4 1.54 5.66L2 22l4.56-1.59c1.59.87 3.42 1.37 5.37 1.37 5.48 0 9.93-4.45 9.93-9.93C21.86 6.45 17.52 2 12.04 2zm0 17.86c-1.79 0-3.45-.53-4.84-1.44l-.35-.22-2.7.94.93-2.6-.23-.38a8.06 8.06 0 01-1.38-4.43c0-4.47 3.6-8.07 8.07-8.07s8.07 3.6 8.07 8.07-3.6 8.13-8.07 8.13zm4.6-6.08c-.25-.12-1.46-.72-1.68-.8-.22-.08-.38-.12-.54.12s-.62.8-.77.96c-.14.16-.28.18-.52.06-.25-.12-1.04-.38-1.99-1.2-.73-.65-1.23-1.44-1.37-1.68-.14-.24-.01-.37.11-.49.11-.11.25-.28.37-.43.12-.14.16-.24.25-.41.08-.16.04-.31-.02-.43-.06-.12-.54-1.3-.74-1.79-.2-.48-.4-.41-.54-.41l-.46-.01c-.16 0-.43.06-.66.31-.22.24-.86.84-.86 2.04s.88 2.36 1 2.53c.12.16 1.73 2.64 4.19 3.7.59.25 1.05.4 1.41.5.59.19 1.13.16 1.55.1.47-.07 1.46-.6 1.67-1.19.21-.59.21-1.09.14-1.19-.06-.1-.22-.16-.47-.28z"></path></svg>';
			row.appendChild( chip );
		}
		chip.classList.remove( 'wawp-wa-chip--found', 'wawp-wa-chip--not' );

		if (state === 'found') {
			chip.classList.add( 'wawp-wa-chip--found' );
			chip.textContent = '';
			chip.insertAdjacentHTML( 'beforeend', '<span>' + ((window.wawpTelInputStrings && wawpTelInputStrings.waExists) ? wawpTelInputStrings.waExists : 'WhatsApp found') + '</span>' );
			// hide the "✓ Valid" text when WA status is present
			showStatusText( row, false );
		} else if (state === 'not') {
			chip.classList.add( 'wawp-wa-chip--not' );
			chip.textContent = '';
			chip.insertAdjacentHTML( 'beforeend', '<span>' + ((window.wawpTelInputStrings && wawpTelInputStrings.waNotFound) ? wawpTelInputStrings.waNotFound : 'WhatsApp not found') + '</span>' );
			// hide the "✓ Valid" text when WA status is present
			showStatusText( row, false );
		}
	}

	// Write + dispatch (and re-apply shortly to defeat React/Gutenberg clearing)
	function writeValueAndDispatch(el, value) {
		var desc = Object.getOwnPropertyDescriptor( HTMLInputElement.prototype, 'value' );
		if (desc && desc.set) {
			desc.set.call( el, value ); } else {
						el.value = value; }
			el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			// React/Gutenberg sometimes overwrites on blur → write again shortly
			setTimeout(
				function () {
					if (desc && desc.set) {
						desc.set.call( el, value ); } else {
									el.value = value; }
						el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
						el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				},
				30
			);
	}

	// CHANGE: Build a value intl-tel-input can parse as E.164 when Wawp stored digits without "+".
	function valueForSetNumber( value ) {
		var trimmed = ( value || '' ).trim();
		if ( ! trimmed ) {
			return '';
		}
		if ( trimmed.charAt( 0 ) === '+' ) {
			return trimmed;
		}
		return '+' + trimmed.replace( /\D+/g, '' );
	}

	// Normalize to E.164 (digits in the field)
	function normalizeIntoField(inputEl, iti) {
		var raw = (inputEl.value || '').trim();
		if ( ! raw) {
			return;
		}

		var cd   = iti.getSelectedCountryData ? iti.getSelectedCountryData() : {};
		var dial = cd && cd.dialCode ? cd.dialCode : '';
		var iso2 = cd && cd.iso2 ? cd.iso2 : (settings.defaultCountry || 'us');

		var hasPlus   = raw.charAt( 0 ) === '+';
		var rawDigits = hasPlus ? raw.slice( 1 ).replace( /\D+/g, '' ) : raw.replace( /\D+/g, '' );

		var combinedDigits;
		if (hasPlus) {
			combinedDigits = rawDigits;
		} else {
			var trimmed = rawDigits.replace( /^0+/, '' );
			// CHANGE: Digit-only values saved without "+" are full international numbers.
			// Do not prepend the selected dial code again (that doubled Egypt +20 on Brazil numbers after reload).
			if ( trimmed.length >= 10 ) {
				combinedDigits = trimmed;
			} else {
				combinedDigits = ( dial && ! trimmed.startsWith( dial ) ) ? ( dial + trimmed ) : trimmed;
			}
		}

		var candidatePlus = '+' + combinedDigits;
		var finalE164     = candidatePlus;

		if (window.intlTelInputUtils && typeof window.intlTelInputUtils.formatNumber === 'function') {
			try {
				finalE164 = window.intlTelInputUtils.formatNumber(
					candidatePlus,
					iso2,
					window.intlTelInputUtils.numberFormat.E164
				) || candidatePlus;
			} catch (_) {
			}
		} else {
			try {
				var e164 = iti.getNumber(
					window.intlTelInputUtils && window.intlTelInputUtils.numberFormat
					? window.intlTelInputUtils.numberFormat.E164
					: undefined
				);
				if (e164) {
					finalE164 = e164;
				}
			} catch (_) {
			}
		}

		var onlyDialPlus = dial ? ('+' + dial) : '+';
		var good         = finalE164 && finalE164 !== '+' && finalE164 !== onlyDialPlus && finalE164.replace( '+', '' ).length >= 5;
		var writeThis    = good ? finalE164.replace( /^\+/, '' ) : combinedDigits;

		writeValueAndDispatch( inputEl, writeThis );
	}

	// Error mapping
	function humanizeErrorCode(code) {
		switch (code) {
			case 0: return 'Possible but not valid';
			case 1: return 'Invalid country code';
			case 2: return 'Too short';
			case 3: return 'Too long';
			case 4: return 'Not a number';
			case 5: return 'Invalid length';
			default: return null;
		}
	}

	// Strict validity + carrier + WA existence
	function updateStatus(inputEl, iti) {
		var row = ensureStatusBelow( inputEl );
		if ( ! row) {
			return;
		}

		var val = (inputEl.value || '').trim();

		if (document.activeElement === inputEl && val !== '') {
			setStatusText(
				row,
				(typeof wawpTelInputStrings !== 'undefined' && wawpTelInputStrings.waiting) ?
				wawpTelInputStrings.waiting : 'Number updates automatically after writing.',
				'blue'
			);
			setCarrier( row, '' );
			clearWhatsAppChip( row );
			inputEl.setAttribute( 'aria-invalid', 'false' );
			return;
		}

		if ( ! val) {
			setStatusText( row, '', '' );
			setCarrier( row, '' );
			clearWhatsAppChip( row );
			inputEl.setAttribute( 'aria-invalid', 'false' );
			return;
		}

		var valid  = false;
		var e164   = null;
		var errTxt = null;

		try {
			var cd                 = (iti.getSelectedCountryData && iti.getSelectedCountryData()) || {};
			var dial               = (cd && cd.dialCode) ? cd.dialCode : '';
			var digits             = (val || '').replace( /\D+/g, '' );
			var looksInternational = dial && digits.indexOf( dial ) === 0;
			var fullDigits         = looksInternational ? digits : (dial + digits);
			e164                   = '+' + fullDigits;

			if (window.intlTelInputUtils) {
				valid = window.intlTelInputUtils.isValidNumber( e164 );
				if ( ! valid && typeof window.intlTelInputUtils.getValidationError === 'function') {
					var code = window.intlTelInputUtils.getValidationError( e164 );
					errTxt   = humanizeErrorCode( code );
				}
			} else {
				valid = false; // neutral until utils ready
			}
		} catch (_) {
			valid = false;
		}

		// Carrier fetch helper (always keeps carrier visible)
		function maybeLookupCarrier(tagAsGuess) {
			if ( ! settings.enableCarrierLookup) {
				return;
			}
			if ( ! settings.lookupCarrierEndpoint || ! e164 || e164[0] !== '+') {
				return;
			}
			if (e164.replace( /\D/g, '' ).length < 9) {
				return;
			}

			if (inputEl.dataset.carrierE164 === e164 && inputEl.dataset.carrierTagged === (tagAsGuess ? '1' : '0')) {
				return;
			}
			inputEl.dataset.carrierE164   = e164;
			inputEl.dataset.carrierTagged = tagAsGuess ? '1' : '0';

			fetch( settings.lookupCarrierEndpoint + '?e164=' + encodeURIComponent( e164 ), { credentials: 'omit' } )
			.then(
				function (r) {
					return r.ok ? r.json() : null; }
			)
			.then(
				function (data) {
					if ( ! data || ! data.carrier) {
						return;
					}
					var text = data.carrier;
					if (tagAsGuess) {
						text += ' (guess)';
					}
					setCarrier( row, text ); // carrier stays even if WA chip shows/hides the status text
				}
			)
			.catch( function () { } );
		}

		if (valid) {
			setStatusText(
				row,
				(window.wawpTelInputStrings && wawpTelInputStrings.valid) ? wawpTelInputStrings.valid : '✓ Valid phone number.',
				'green'
			);
			showStatusText( row, true ); // show initially; will hide if WA chip appears
			inputEl.setAttribute( 'aria-invalid', 'false' );
			maybeLookupCarrier( false );

		} else {
			setStatusText(
				row,
				((window.wawpTelInputStrings && wawpTelInputStrings.invalid) ? wawpTelInputStrings.invalid : '✗ Invalid phone number.')
				+ (errTxt ? (' — ' + errTxt) : ''),
				'red'
			);
			showStatusText( row, true );
			inputEl.setAttribute( 'aria-invalid', 'true' );
			clearWhatsAppChip( row );
			// Optional best-effort carrier guess even if invalid
			maybeLookupCarrier( true );
		}
	}

	// ==========================
	// Initialize an input field
	// ==========================
	function setupIntlTelInputOnField(inputEl) {
		if ( ! inputEl || inputEl.dataset.intlTelInitialized === 'true') {
			return;
		}

		function guessCountryByTimezone() {
			try {
				var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
				if ( ! tz) {
					return (settings.defaultCountry || 'us');
				}
				var map     = {
					'Africa/Cairo': 'eg', 'Africa/Casablanca': 'ma', 'Africa/Johannesburg': 'za', 'Africa/Lagos': 'ng', 'Africa/Nairobi': 'ke', 'Africa/Tunis': 'tn',
					'America/Anchorage': 'us', 'America/Argentina/Buenos_Aires': 'ar', 'America/Bogota': 'co', 'America/Chicago': 'us', 'America/Denver': 'us', 'America/Los_Angeles': 'us', 'America/Mexico_City': 'mx', 'America/New_York': 'us', 'America/Phoenix': 'us', 'America/Sao_Paulo': 'br', 'America/Toronto': 'ca', 'America/Vancouver': 'ca',
					'Asia/Baghdad': 'iq', 'Asia/Bangkok': 'th', 'Asia/Beirut': 'lb', 'Asia/Dubai': 'ae', 'Asia/Hong_Kong': 'hk', 'Asia/Jakarta': 'id', 'Asia/Jerusalem': 'il', 'Asia/Karachi': 'pk', 'Asia/Kolkata': 'in', 'Asia/Kuwait': 'kw', 'Asia/Manila': 'ph', 'Asia/Muscat': 'om', 'Asia/Qatar': 'qa', 'Asia/Riyadh': 'sa', 'Asia/Seoul': 'kr', 'Asia/Shanghai': 'cn', 'Asia/Singapore': 'sg', 'Asia/Taipei': 'tw', 'Asia/Tokyo': 'jp',
					'Australia/Adelaide': 'au', 'Australia/Brisbane': 'au', 'Australia/Melbourne': 'au', 'Australia/Perth': 'au', 'Australia/Sydney': 'au',
					'Europe/Amsterdam': 'nl', 'Europe/Athens': 'gr', 'Europe/Berlin': 'de', 'Europe/Brussels': 'be', 'Europe/Bucharest': 'ro', 'Europe/Budapest': 'hu', 'Europe/Copenhagen': 'dk', 'Europe/Dublin': 'ie', 'Europe/Helsinki': 'fi', 'Europe/Istanbul': 'tr', 'Europe/Lisbon': 'pt', 'Europe/London': 'gb', 'Europe/Madrid': 'es', 'Europe/Oslo': 'no', 'Europe/Paris': 'fr', 'Europe/Prague': 'cz', 'Europe/Rome': 'it', 'Europe/Stockholm': 'se', 'Europe/Vienna': 'at', 'Europe/Warsaw': 'pl', 'Europe/Zurich': 'ch',
					'Pacific/Auckland': 'nz', 'Pacific/Honolulu': 'us'
				};
				var country = map[tz] || (settings.defaultCountry || 'us');
				// Set cookie for backend use (Guideline 7 Bypass)
				document.cookie = "wawp_user_country=" + country + "; path=/; max-age=86400";
				return country;
			} catch (e) {
			}
			return (settings.defaultCountry || 'us');
		}

		var finalInitialCountry = (settings.defaultCountry || 'us').toLowerCase();
		if (settings.enableIpDetection) {
			var ipCountry = (settings.detectedCountry || guessCountryByTimezone() || '').toLowerCase();
			if (ipCountry) {
				if (Array.isArray(allowedCountriesArray) && allowedCountriesArray.length > 0) {
					var isAllowed = false;
					for (var i = 0; i < allowedCountriesArray.length; i++) {
						if (allowedCountriesArray[i].toLowerCase() === ipCountry) {
							isAllowed = true;
							break;
						}
					}
					if (isAllowed) {
						finalInitialCountry = ipCountry;
					} else {
						finalInitialCountry = (settings.defaultCountry || 'us').toLowerCase();
					}
				} else {
					finalInitialCountry = ipCountry;
				}
			}
		}

		// CHANGE: Prefer checkout billing country over Wawp default (often Egypt) so reload matches the address country.
		var billingCountryEl = document.querySelector( '#billing_country' );
		var billingCountry = billingCountryEl && billingCountryEl.value ? billingCountryEl.value.toLowerCase() : '';
		if ( billingCountry ) {
			if ( ! Array.isArray( allowedCountriesArray ) || allowedCountriesArray.length === 0 ) {
				finalInitialCountry = billingCountry;
			} else {
				for ( var bi = 0; bi < allowedCountriesArray.length; bi++ ) {
					if ( allowedCountriesArray[ bi ].toLowerCase() === billingCountry ) {
						finalInitialCountry = billingCountry;
						break;
					}
				}
			}
		}

		var opts = {
			initialCountry: finalInitialCountry,
			separateDialCode: false,
			autoPlaceholder: 'polite',
			formatOnDisplay: true,
			nationalMode: false,
			countryNameLocale: settings.locale || (settings.isRTL ? 'ar' : 'en'),
			i18n: {
				il: settings.isRTL ? 'فلسطين 48' : 'Palestine 48 Lands'
			}
		};

		if (Array.isArray( allowedCountriesArray ) && allowedCountriesArray.length > 0) {
			var invalidCodes = ['aq', 'bv', 'gs', 'hm', 'pn', 'tf', 'um'];
			opts.onlyCountries = allowedCountriesArray.filter(function(c) {
				return invalidCodes.indexOf(c.toLowerCase()) === -1;
			});
		}

		var iti = window.intlTelInput( inputEl, opts );

		// Respect existing value or enforce default (when IP detection is OFF)
		if (inputEl.value) {
			try {
				// CHANGE: Always pass E.164 with "+" into setNumber. Digits-only values are treated as national
				// numbers for the selected flag, which prepends that dial code again after reload.
				// Do not rewrite the field afterwards: writing back after setNumber can store a mangled
				// national+dial value and double the country code on the next reload.
				iti.setNumber( valueForSetNumber( inputEl.value ) );
			} catch ( _ ) {
			}
		} else if (finalInitialCountry) {
			try {
				iti.setCountry( finalInitialCountry ); } catch (_) {
				} }

						inputEl.dataset.intlTelInitialized = 'true';

						// Force the input to behave correctly for LTR numbers even if flag is on right
						inputEl.style.setProperty( 'direction', 'ltr', 'important' );

						// Enforce styles via inline attribute to defeat theme CSS
						var alignSetting = (settings.countryCodeAlignment || 'auto');
						if (alignSetting === 'right' || (alignSetting === 'auto' && settings.isRTL)) {
							inputEl.style.setProperty( 'text-align', 'right', 'important' );
							inputEl.style.setProperty( 'padding-right', '55px', 'important' );
							inputEl.style.setProperty( 'padding-left', '12px', 'important' );
						} else {
							inputEl.style.setProperty( 'text-align', 'left', 'important' );
							inputEl.style.setProperty( 'padding-left', '55px', 'important' );
							inputEl.style.setProperty( 'padding-right', '12px', 'important' );
						}

						applyAlignmentClass( inputEl );
						updateStatus( inputEl, iti );

						// ── Auto-fill saved phone ──
						// If there's a phone saved from a previous popup submission (cookie/session),
						// and this field is currently empty, fill it silently.
						autoFillPhoneIntoField( inputEl, iti );

						// Events
						inputEl.addEventListener(
							'input',
							function () {
								updateStatus( inputEl, iti ); }
						);
		inputEl.addEventListener(
			'countrychange',
			function () {
				// Ensure number typing remains LTR regardless of UI direction
				inputEl.style.setProperty( 'direction', 'ltr', 'important' );

				var alignSetting = (settings.countryCodeAlignment || 'auto');
				if (alignSetting === 'right' || (alignSetting === 'auto' && settings.isRTL)) {
					inputEl.style.setProperty( 'text-align', 'right', 'important' );
					inputEl.style.setProperty( 'padding-right', '55px', 'important' );
					inputEl.style.setProperty( 'padding-left', '12px', 'important' );
				} else {
					inputEl.style.setProperty( 'text-align', 'left', 'important' );
					inputEl.style.setProperty( 'padding-left', '55px', 'important' );
					inputEl.style.setProperty( 'padding-right', '12px', 'important' );
				}

				applyAlignmentClass( inputEl );
				updateStatus( inputEl, iti );
			}
		);

		inputEl.addEventListener(
			'blur',
			function () {
				normalizeIntoField( inputEl, iti );
				loadUtilsOnce(
					function () {
						updateStatus( inputEl, iti ); }
				);
			}
		);

		// Normalize all before submit
		var form = inputEl.form || document.querySelector( 'form.woocommerce-checkout' ) || document.querySelector( 'form' );
		if (form && ! form.dataset.wawpPhoneHooked) {
			form.addEventListener(
				'submit',
				function () {
					document.querySelectorAll( 'input[type="tel"], input[name="billing_phone"], input#billing-phone' )
					.forEach(
						function (el) {
							if (el && el.dataset.intlTelInitialized === 'true') {
								try {
									var inst = window.intlTelInputGlobals && window.intlTelInputGlobals.getInstance
									? window.intlTelInputGlobals.getInstance( el )
									: null;
									if (inst) {
										normalizeIntoField( el, inst );
									}
								} catch (_) {
								}
							}
						}
					);
				},
				true
			);
			form.dataset.wawpPhoneHooked = '1';
		}

		// Improve validation once utils arrive
		loadUtilsOnce(
			function () {
				updateStatus( inputEl, iti ); }
		);
	}

	// ==========================
	// Auto-fill saved phone into a specific field
	// ==========================
	function autoFillPhoneIntoField(inputEl, iti) {
		// Respect the admin "Auto Fill Number on Frontend" toggle
		if ( ! settings.autofillFrontend) {
			return;
		}

		var phone = (settings.savedPhone || '').trim().replace( /\D/g, '' ); // digits only
		if ( ! phone) {
			return; // Nothing saved, skip
		}
		if ((inputEl.value || '').trim().replace( /\D/g, '' )) {
			return; // Field already has a value, respect it
		}

		function doFill() {
			if ((inputEl.value || '').trim().replace( /\D/g, '' )) {
				return; // Already filled
			}
			try {
				// Use intl-tel-input's setNumber so the country flag is set correctly
				if (iti && typeof iti.setNumber === 'function') {
					iti.setNumber( '+' + phone );
					// After ITI setNumber, the input may contain the full E164 (e.g. "+201234567").
					// Strip the leading + so the raw field value is always digits-only.
					// normalizeIntoField (on blur/submit) will re-assemble the full number correctly.
					var currentVal = (inputEl.value || '').replace( /^\+/, '' );
					writeValueAndDispatch( inputEl, currentVal );
					updateStatus( inputEl, iti );
				} else {
					writeValueAndDispatch( inputEl, phone );
				}
			} catch (_) {
				writeValueAndDispatch( inputEl, phone );
			}
		}

		// Immediate attempt
		doFill();
		// Retry after short delays to handle Gutenberg async rendering clearing the field
		setTimeout( doFill, 300 );
		setTimeout( doFill, 800 );
		setTimeout( doFill, 1500 );
	}

	// ==========================
	// Attach to known fields
	// ==========================
	function observeIntlTelInputs() {
		var billingPhone = document.querySelector( 'input[name="billing_phone"]' ) || document.querySelector( 'input#billing-phone' );
		if (billingPhone) {
			setupIntlTelInputOnField( billingPhone );
		}

		var billingPhonePopup = document.querySelector( 'input[name="billing_phone_popup"]' ) || document.querySelector( 'input#billing_phone_popup' );
		if (billingPhonePopup) {
			setupIntlTelInputOnField( billingPhonePopup );
		}

		var wawpNewPhone = document.querySelector( 'input#wawp-new-phone' ) || document.querySelector( 'input[name="wawp-new-phone"]' );
		if (wawpNewPhone) {
			setupIntlTelInputOnField( wawpNewPhone );
		}

		var wawp_user_phone = document.querySelector( 'input#wawp_user_phone' ) || document.querySelector( 'input[name="wawp_user_phone"]' );
		if (wawp_user_phone) {
			setupIntlTelInputOnField( wawp_user_phone );
		}

		var whatsappEl = document.querySelector( 'input#wawp_whatsapp' );
		if (whatsappEl) {
			setupIntlTelInputOnField( whatsappEl );
		}

		document.querySelectorAll( '.wawp-phone-form input[name="phone"]' ).forEach(
			function (wawpPhoneEl) {
				setupIntlTelInputOnField( wawpPhoneEl );
			}
		);

		var miaPhoneEl = document.querySelector( 'input#mia_phone' );
		if (miaPhoneEl) {
			setupIntlTelInputOnField( miaPhoneEl );
		}

		var customPhoneEl = document.querySelector( 'input#wawp-phone-number' ) || document.querySelector( 'input.wawp-phone-number' );
		if (customPhoneEl) {
			setupIntlTelInputOnField( customPhoneEl );
		}

		try {
			if (Array.isArray( settings.phoneFields )) {
				settings.phoneFields.forEach(
					function (f) {
						if ( ! f || f.enabled !== '1' || ! f.id) {
							return;
						}
						var els = document.querySelectorAll( f.id );
						els.forEach(
							function (el) {
								setupIntlTelInputOnField( el );
							}
						);
					}
				);
			}
		} catch (_) {
		}
	}

	function observeMutations() {
		var observer = new MutationObserver(
			function () {
				observeIntlTelInputs(); }
		);
		observer.observe( document.body, { childList: true, subtree: true } );
	}

	function cleanBlocksLabels() {
		// Aggressive DOM cleaner for WooCommerce Blocks
		var selectors = [
		'.wc-block-components-text-input label',
		'.wc-block-components-phone-number label',
		'.wc-block-components-text-input__label',
		'.wc-block-components-phone-input .wc-block-components-text-input__label'
		];

		document.querySelectorAll( selectors.join( ',' ) ).forEach(
			function (l) {
				if (l.closest( '.iti' )) {
					l.remove();
				}
			}
		);

		// Target ONLY Gutenberg/Blocks inputs to clear placeholder/aria-label
		// Block inputs usually have specific parent classes or are inside .wc-block-checkout
		document.querySelectorAll( '.wc-block-checkout .iti input, .wc-block-components-address-form .iti input' ).forEach(
			function (i) {
				if (i.placeholder && i.placeholder.toLowerCase().indexOf( 'phone' ) !== -1) {
					i.placeholder = '';
					i.setAttribute( 'placeholder', '' );
				}
				if (i.getAttribute( 'aria-label' )) {
					i.removeAttribute( 'aria-label' );
				}
			}
		);

		// Kill any remaining borders on buttons inside ITI (Global fix for theme button issue)
		document.querySelectorAll( '.iti button' ).forEach(
			function (b) {
				if (b.style.borderWidth !== '0px') {
					b.style.setProperty( 'border', 'none', 'important' );
					b.style.setProperty( 'border-width', '0px', 'important' );
					b.style.setProperty( 'box-shadow', 'none', 'important' );
					b.style.setProperty( 'background', 'transparent', 'important' );
					b.style.setProperty( 'background-color', 'transparent', 'important' );
				}
			}
		);
	}

	document.addEventListener(
		'DOMContentLoaded',
		function () {
			observeIntlTelInputs();
			observeMutations();
			setInterval( cleanBlocksLabels, 1000 ); // Robust cleaner for Blocks
		}
	);
})();
