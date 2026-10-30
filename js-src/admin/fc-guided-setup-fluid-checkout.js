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
		containerSelector:           '.fc-guided-setup',
		newsletterOptInSelector:     '[data-fc-setup-newsletter-opt-in]',
		newsletterEmailSelector:     '[data-fc-setup-newsletter-email]',
		productTypeSelector:         '[data-fc-setup-product-type]',
		deliverySectionSelector:     '[data-fc-setup-delivery-section]',
		deliveryMethodSelector:      '[data-fc-setup-delivery-method]',
		autosaveFormSelector:        '[data-fc-setup-autosave="1"]',
		debounceMs:                  400,
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

			if ( autosaveForm && ( target.matches( _settings.productTypeSelector ) || target.matches( _settings.deliveryMethodSelector ) ) ) {
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
