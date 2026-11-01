/**
 * Fluid Checkout checkout guided setup (Phase 1 shell).
 *
 * DEPENDS ON:
 * - FCGuidedSetup // Shared guided setup shell
 */
( function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCGuidedSetupFluidCheckout = factory( root );
	}
} )( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _autosaveTimer = null;
	var _settings = {
		containerSelector:              '.fc-guided-setup',
		newsletterOptInSelector:        '[data-fc-setup-newsletter-opt-in]',
		newsletterEmailSelector:        '[data-fc-setup-newsletter-email]',
		industrySelector:               'input[name="business_industry"]',
		customerAudienceSelector:       'input[name="customer_audience"]',
		customerLocationSelector:       'input[name="customer_location"]',
		productTypeSelector:            '[data-fc-setup-product-type]',
		deliverySectionSelector:        '[data-fc-setup-delivery-section]',
		deliveryMethodSelector:         '[data-fc-setup-delivery-method]',
		autosaveFormSelector:           '[data-fc-setup-autosave="1"]',
		recommendationsMountSelector:   '[data-fc-setup-recommendations]',
		showAppliedSelector:            '[data-fc-setup-show-applied-recommendations]',
		appliedRecommendationsSelector: '[data-fc-setup-applied-recommendations]',
		showMoreSelector:               '[data-fc-setup-show-more-recommendations]',
		moreRecommendationsSelector:    '[data-fc-setup-more-recommendations]',
		debounceMs:                     400,
		i18n: {
			showAlreadyApplied: 'Show already applied',
			hideAlreadyApplied: 'Hide already applied',
			showMoreRecommendations: 'Show more recommendations',
			showFewerRecommendations: 'Show fewer recommendations',
		},
	};

	var _guidedSetupSettings = null;



	var getContainer = function() {
		return document.querySelector( _settings.containerSelector );
	};



	var getGuidedSetupSettings = function() {
		if ( _guidedSetupSettings ) {
			return _guidedSetupSettings;
		}

		if ( root.fcSettingsAdmin && root.fcSettingsAdmin.guidedSetup ) {
			_guidedSetupSettings = root.fcSettingsAdmin.guidedSetup;

			// Maybe merge localized i18n into local defaults
			if ( _guidedSetupSettings.i18n ) {
				_settings.i18n = Object.assign( {}, _settings.i18n, _guidedSetupSettings.i18n );
			}
		}

		return _guidedSetupSettings;
	};



	var normalizeFormFieldKey = function( key ) {
		if ( key.length > 2 && '[]' === key.slice( -2 ) ) {
			return key.slice( 0, -2 );
		}

		return key;
	};



	var serializeFormData = function( form ) {
		var formData = new FormData( form );
		var data = { };

		formData.forEach( function( value, key ) {
			var dataKey = normalizeFormFieldKey( key );

			if ( Object.prototype.hasOwnProperty.call( data, dataKey ) ) {
				if ( ! Array.isArray( data[ dataKey ] ) ) {
					data[ dataKey ] = [ data[ dataKey ] ];
				}

				data[ dataKey ].push( value );
			}
			else {
				data[ dataKey ] = value;
			}
		} );

		return data;
	};



	var getI18n = function( key, fallback ) {
		var guidedSetupSettings = getGuidedSetupSettings();

		if ( guidedSetupSettings && guidedSetupSettings.i18n && guidedSetupSettings.i18n[ key ] ) {
			return guidedSetupSettings.i18n[ key ];
		}

		return fallback;
	};



	var isValidEmail = function( email ) {
		if ( ! email ) {
			return false;
		}

		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
	};



	var formDataHasPhysical = function( formData ) {
		var types = formData.product_types;

		if ( ! types ) {
			return false;
		}

		if ( ! Array.isArray( types ) ) {
			types = [ types ];
		}

		return types.indexOf( 'physical' ) !== -1;
	};



	var validateAboutStore = function( formData ) {
		if ( ! formData.business_industry ) {
			window.alert( getI18n( 'industryRequired', 'Please select what type of products or services you sell.' ) );
			return false;
		}

		if ( ! formData.customer_audience ) {
			window.alert( getI18n( 'customerAudienceRequired', 'Please select who you sell to.' ) );
			return false;
		}

		if ( ! formData.customer_location ) {
			window.alert( getI18n( 'customerLocationRequired', 'Please select where your customers are located.' ) );
			return false;
		}

		if ( formData.newsletter_opt_in && ! isValidEmail( formData.email ) ) {
			window.alert( getI18n( 'emailRequired', 'Please enter a valid email address for the newsletter.' ) );
			return false;
		}

		return true;
	};



	var validateProductTypes = function( formData ) {
		var types = formData.product_types;

		if ( ! types || ( Array.isArray( types ) && types.length === 0 ) ) {
			window.alert( getI18n( 'productTypesRequired', 'Please select at least one product type.' ) );
			return false;
		}

		if ( formDataHasPhysical( formData ) ) {
			var delivery = formData.delivery_methods;

			if ( ! delivery || ( Array.isArray( delivery ) && delivery.length === 0 ) ) {
				window.alert( getI18n( 'deliveryMethodsRequired', 'Please select at least one delivery method for physical products.' ) );
				return false;
			}
		}

		return true;
	};



	var isShellStep = function( stepSlug ) {
		return 'recommended_setup' === stepSlug || 'wrap_up' === stepSlug;
	};



	var handleBeforeContinue = function( step, formData ) {
		if ( isShellStep( step ) ) {
			return true;
		}

		if ( 'about_store' === step ) {
			return validateAboutStore( formData );
		}

		if ( 'product_types' === step ) {
			return validateProductTypes( formData );
		}

		return true;
	};



	var isContinueEnabled = function( stepSlug, card ) {
		if ( isShellStep( stepSlug ) ) {
			return true;
		}

		var form = card ? card.querySelector( 'form' ) : null;

		if ( ! form ) {
			return true;
		}

		var formData = serializeFormData( form );

		if ( 'about_store' === stepSlug ) {
			if ( ! formData.business_industry ) {
				return false;
			}

			if ( ! formData.customer_audience ) {
				return false;
			}

			if ( ! formData.customer_location ) {
				return false;
			}

			if ( formData.newsletter_opt_in && ! isValidEmail( formData.email ) ) {
				return false;
			}

			return true;
		}

		if ( 'product_types' === stepSlug ) {
			if ( ! formData.product_types || ( Array.isArray( formData.product_types ) && formData.product_types.length === 0 ) ) {
				return false;
			}

			if ( formDataHasPhysical( formData ) ) {
				if ( ! formData.delivery_methods || ( Array.isArray( formData.delivery_methods ) && formData.delivery_methods.length === 0 ) ) {
					return false;
				}
			}

			return true;
		}

		return true;
	};



	var updateNewsletterEmailState = function( container ) {
		var optIn = container.querySelector( _settings.newsletterOptInSelector );
		var email = container.querySelector( _settings.newsletterEmailSelector );

		if ( ! optIn || ! email ) {
			return;
		}

		email.disabled = ! optIn.checked;
	};



	var getProductTypesStepCard = function( scope ) {
		if ( scope && scope.matches && scope.matches( '[data-fc-setup-step="product_types"]' ) ) {
			return scope;
		}

		if ( scope && scope.closest ) {
			var fromScope = scope.closest( '[data-fc-setup-step="product_types"]' );

			if ( fromScope ) {
				return fromScope;
			}
		}

		var container = getContainer();

		return container ? container.querySelector( '[data-fc-setup-step="product_types"]' ) : null;
	};



	var updateDeliverySectionVisibility = function( scope ) {
		var card = getProductTypesStepCard( scope );
		var section = card ? card.querySelector( _settings.deliverySectionSelector ) : null;
		var physicalChecked = card ? card.querySelector( _settings.productTypeSelector + '[value="physical"]:checked' ) : null;
		var deliveryInputs = section ? section.querySelectorAll( _settings.deliveryMethodSelector ) : [];

		if ( ! section ) {
			return;
		}

		if ( physicalChecked ) {
			section.removeAttribute( 'hidden' );
		}
		else {
			section.setAttribute( 'hidden', '' );
		}

		deliveryInputs.forEach( function( input ) {
			var optionLabel = input.closest( '.fc-settings-radio-option' );

			input.disabled = ! physicalChecked;

			if ( optionLabel ) {
				if ( physicalChecked ) {
					optionLabel.classList.remove( 'is-disabled' );
				}
				else {
					optionLabel.classList.add( 'is-disabled' );
				}
			}
		} );
	};



	/**
	 * Whether the changed field should trigger a debounced answers autosave.
	 *
	 * @param   Element  target  Changed form control.
	 * @return  boolean
	 */
	var shouldAutosaveField = function( target ) {
		return target.matches( _settings.industrySelector )
			|| target.matches( _settings.customerAudienceSelector )
			|| target.matches( _settings.customerLocationSelector )
			|| target.matches( _settings.productTypeSelector )
			|| target.matches( _settings.deliveryMethodSelector );
	};



	/**
	 * Replace the recommended_setup toggles markup from an AJAX response.
	 *
	 * @param   Object|null  response  Parsed JSON response.
	 */
	var updateRecommendationsFromResponse = function( response ) {
		var container = getContainer();
		var mount = container ? container.querySelector( _settings.recommendationsMountSelector ) : null;
		var html = response && response.data ? response.data.recommendationsHtml : null;

		// Bail if the recommendations mount or HTML payload is missing
		if ( ! mount || 'string' !== typeof html ) {
			return;
		}

		mount.innerHTML = html;
	};



	/**
	 * Toggle a collapsed recommendation list behind a show/hide link.
	 *
	 * @param   Event    e              Click event.
	 * @param   Element  button         Toggle link.
	 * @param   string   listSelector   Selector for the collapsed list inside the mount.
	 * @param   string   showLabelKey   i18n key for the collapsed label.
	 * @param   string   hideLabelKey   i18n key for the expanded label.
	 * @param   string   showFallback   Fallback label when collapsed.
	 * @param   string   hideFallback   Fallback label when expanded.
	 */
	var toggleCollapsedRecommendationsList = function( e, button, listSelector, showLabelKey, hideLabelKey, showFallback, hideFallback ) {
		var mount = button.closest( _settings.recommendationsMountSelector );
		var list = mount ? mount.querySelector( listSelector ) : null;
		var expanded;

		e.preventDefault();

		// Bail if the list is missing
		if ( ! list ) {
			return;
		}

		expanded = 'true' === button.getAttribute( 'aria-expanded' );

		if ( expanded ) {
			button.setAttribute( 'aria-expanded', 'false' );
			button.textContent = ( _settings.i18n && _settings.i18n[ showLabelKey ] ) ? _settings.i18n[ showLabelKey ] : showFallback;
			list.setAttribute( 'hidden', '' );
			list.classList.add( 'is-collapsed' );
		}
		else {
			button.setAttribute( 'aria-expanded', 'true' );
			button.textContent = ( _settings.i18n && _settings.i18n[ hideLabelKey ] ) ? _settings.i18n[ hideLabelKey ] : hideFallback;
			list.removeAttribute( 'hidden' );
			list.classList.remove( 'is-collapsed' );
		}
	};

	/**
	 * Toggle visibility of already-applied recommendation rows on PRO.
	 *
	 * @param   Event    e       Click event.
	 * @param   Element  button  Toggle link.
	 */
	var handleShowAppliedRecommendationsClick = function( e, button ) {
		toggleCollapsedRecommendationsList(
			e,
			button,
			_settings.appliedRecommendationsSelector,
			'showAlreadyApplied',
			'hideAlreadyApplied',
			'Show already applied',
			'Hide already applied'
		);
	};

	/**
	 * Toggle visibility of overflow PRO recommendation rows on Lite.
	 *
	 * @param   Event    e       Click event.
	 * @param   Element  button  Toggle link.
	 */
	var handleShowMoreRecommendationsClick = function( e, button ) {
		toggleCollapsedRecommendationsList(
			e,
			button,
			_settings.moreRecommendationsSelector,
			'showMoreRecommendations',
			'showFewerRecommendations',
			'Show more recommendations',
			'Show fewer recommendations'
		);
	};



	/**
	 * Schedule a debounced answers autosave for the given form.
	 *
	 * @param   Element  form  Autosave-enabled step form.
	 */
	var scheduleAutosave = function( form ) {
		var guidedSetupSettings = getGuidedSetupSettings();

		if ( ! guidedSetupSettings || ! guidedSetupSettings.ajaxSaveAnswersAction ) {
			return;
		}

		if ( _autosaveTimer ) {
			window.clearTimeout( _autosaveTimer );
		}

		_autosaveTimer = window.setTimeout( function() {
			sendAutosave( form, guidedSetupSettings );
		}, _settings.debounceMs );
	};



	/**
	 * Persist step answers via AJAX and refresh recommendations markup.
	 *
	 * @param   Element  form                 Autosave-enabled step form.
	 * @param   Object   guidedSetupSettings  Guided setup script settings.
	 */
	var sendAutosave = function( form, guidedSetupSettings ) {
		var formData = serializeFormData( form );
		var params = new URLSearchParams();
		var nonceField = document.getElementById( guidedSetupSettings.nonceFieldId );

		params.append( 'action', guidedSetupSettings.ajaxSaveAnswersAction );
		params.append( guidedSetupSettings.nonceParamName, nonceField ? nonceField.value : '' );
		params.append( 'guided_setup_id', guidedSetupSettings.guidedSetupId );

		Object.keys( formData ).forEach( function( key ) {
			var value = formData[ key ];

			if ( Array.isArray( value ) ) {
				value.forEach( function( item ) {
					params.append( 'form_data[' + key + '][]', item );
				} );
				return;
			}

			params.append( 'form_data[' + key + ']', value );
		} );

		fetch( guidedSetupSettings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: params.toString(),
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( response ) {
				// Bail if the autosave failed
				if ( ! response || ! response.success ) {
					return;
				}

				updateRecommendationsFromResponse( response );
			} )
			.catch( function() {
				// Ignore autosave network errors; continue still saves answers
			} );
	};



	var syncContinueButtonForCard = function( card ) {
		if ( ! card || ! root.FCGuidedSetup ) {
			return;
		}

		var submitWrapper = card.querySelector( '.fc-setup-submit' );

		if ( ! submitWrapper ) {
			return;
		}

		if ( isContinueEnabled( card.getAttribute( 'data-fc-setup-step' ), card ) ) {
			root.FCGuidedSetup.enableContinueButton( submitWrapper );
		}
		else {
			root.FCGuidedSetup.disableContinueButton( submitWrapper );
		}
	};



	var bindFieldListeners = function( container ) {
		container.addEventListener( 'change', function( e ) {
			var target = e.target;
			var card = target ? target.closest( '[data-fc-setup-step]' ) : null;

			if ( ! target ) {
				return;
			}

			if ( target.matches( _settings.newsletterOptInSelector ) ) {
				updateNewsletterEmailState( container );
			}

			if ( target.matches( _settings.productTypeSelector ) ) {
				updateDeliverySectionVisibility( card );
			}

			if ( target.matches( 'input, select, textarea' ) ) {
				syncContinueButtonForCard( card );
			}

			var autosaveForm = target.closest( _settings.autosaveFormSelector );

			// Maybe autosave recommendation-relevant answers
			if ( autosaveForm && shouldAutosaveField( target ) ) {
				scheduleAutosave( autosaveForm );
			}
		} );

		container.addEventListener( 'input', function( e ) {
			var target = e.target;
			var card = target ? target.closest( '[data-fc-setup-step]' ) : null;

			if ( target && target.matches( _settings.newsletterEmailSelector ) ) {
				syncContinueButtonForCard( card );
			}
		} );

		container.addEventListener( 'click', function( e ) {
			var appliedButton = e.target.closest( _settings.showAppliedSelector );
			var moreButton = e.target.closest( _settings.showMoreSelector );

			// SHOW ALREADY APPLIED
			if ( appliedButton ) {
				handleShowAppliedRecommendationsClick( e, appliedButton );
				return;
			}

			// SHOW MORE PRO RECOMMENDATIONS
			if ( moreButton ) {
				handleShowMoreRecommendationsClick( e, moreButton );
			}
		} );
	};



	var init = function() {
		if ( _hasInitialized ) {
			return;
		}

		var container = getContainer();
		var guidedSetupSettings = getGuidedSetupSettings();

		if ( ! container || ! guidedSetupSettings || ! root.FCGuidedSetup ) {
			return;
		}

		_hasInitialized = true;

		updateNewsletterEmailState( container );
		updateDeliverySectionVisibility( container.querySelector( '[data-fc-setup-step="product_types"]' ) );
		bindFieldListeners( container );

		guidedSetupSettings.isContinueButtonEnabled = isContinueEnabled;
		guidedSetupSettings.onBeforeContinue = handleBeforeContinue;
		guidedSetupSettings.onStepProcessSuccess = function( _step, _formData, response ) {
			updateRecommendationsFromResponse( response );
		};
		guidedSetupSettings.onStepVisible = function( stepSlug, card ) {
			syncContinueButtonForCard( card );
		};

		root.FCGuidedSetup.init( guidedSetupSettings );

		container.querySelectorAll( '[data-fc-setup-step]' ).forEach( function( card ) {
			syncContinueButtonForCard( card );
		} );
	};



	return {
		init: init,
	};

} );
