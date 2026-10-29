/**
 * Admin site report preview and send-now actions.
 *
 * DEPENDS ON:
 * - FCUtils // Settings merge, keyboard keys
 */
(function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminTelemetry = factory( root );
	}
})( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		modalSelector:                        '#fc-telemetry-modal',
		modalPayloadSelector:                 '.fc-telemetry-modal__payload',
		modalFeedbackSelector:                '.fc-telemetry-modal__feedback',
		modalSendButtonSelector:              '.fc-telemetry-modal__send-button',
		previewButtonSelector:                '.fc-telemetry-preview-button',
		inlineSendButtonSelector:             '.fc-telemetry-send-now-button',
		inlineFeedbackSelector:               '.fc-telemetry-enable-actions__feedback',
		enableCheckboxSelector:               '#fc_telemetry_enabled',
		dataGroupsCheckboxSelector:           'input[name="fc_telemetry_data_groups[]"]:checked',
		closeAttributeSelector:               '[data-fc-telemetry-close]',
		bodySelector:                         'body',

		isOpenClass:                          'is-open',
		isHiddenClass:                        'is-hidden',
		isErrorClass:                         'is-error',
		isSuccessClass:                       'is-success',
		modalOpenBodyClass:                   'fc-telemetry-modal-open',
		feedbackTypeClassTemplate:            'is-###TYPE###',

		ariaHiddenAttribute:                  'aria-hidden',

		previewAction:                        'fc_telemetry_preview',
		sendNowAction:                        'fc_telemetry_send_now',
		basicEnvironmentGroup:                'basic_environment',
		sendContextModal:                     'modal',
		sendContextInline:                    'inline',
		enabledYesValue:                      'yes',
		enabledNoValue:                       'no',
		isDisabledByConstant:                 false,

		ajaxUrl:                              '',
		nonce:                                '',
		i18n: {
			loading:                          'Loading preview...',
			loadError:                        'Could not load the preview. Try again.',
			sendError:                        'Could not send the usage data. Try again.',
			sendSuccess:                      'Usage data sent.',
			sendNow:                          'Send now',
			enableAndSendNow:                 'Turn ON and send now',
			inProgress:                       'Usage data is already being sent. Try again in a moment.',
			disabled:                         'Usage tracking is turned off.',
			emptyPayload:                     'There is no usage data to send.',
			ineligibleDomain:                 'This site cannot send usage data. Local and development sites are excluded.',
			rateLimited:                      'Usage data was sent recently. Try again later.',
			requestFailed:                    'The usage data could not be sent. Try again later.',
			disabledByConstant:               'Usage tracking is turned off by the FC_DISABLE_TELEMETRY constant in wp-config.php.',
		},
	};

	var _isEnabled = false;
	var _modal = null;
	var _payload = null;
	var _feedback = null;
	var _sendButton = null;
	var _inlineSendButton = null;
	var _inlineFeedback = null;



	/**
	 * METHODS
	 */



	/**
	 * Build a modifier class from a template setting.
	 *
	 * @param  {string} template Class template with ###TYPE### placeholder.
	 * @param  {string} type     Modifier type value.
	 * @return {string} Resolved class name.
	 */
	var getModifierClass = function( template, type ) {
		return template.replace( '###TYPE###', type );
	};



	/**
	 * Collect the current site report form state.
	 *
	 * @return {Object} Form state with enabled flag and data groups.
	 */
	var getFormState = function() {
		var enableCheckbox = document.querySelector( _settings.enableCheckboxSelector );
		var enabled = !!( enableCheckbox && enableCheckbox.checked );
		var groups = [ _settings.basicEnvironmentGroup ];
		var groupCheckboxes;
		var i;
		var value;

		// Maybe collect checked data groups when reporting is enabled
		if ( enabled ) {
			groupCheckboxes = document.querySelectorAll( _settings.dataGroupsCheckboxSelector );

			// Iterate checked data group checkboxes
			for ( i = 0; i < groupCheckboxes.length; i++ ) {
				value = groupCheckboxes[ i ].value;

				// Maybe add a new group value
				if ( value && -1 === groups.indexOf( value ) ) {
					groups.push( value );
				}
			}
		}

		return {
			enabled:     enabled ? _settings.enabledYesValue : _settings.enabledNoValue,
			data_groups: groups,
		};
	};



	/**
	 * Get user-facing message for a failed preview response.
	 *
	 * @param  {Object|string} data Error payload from the server.
	 * @return {string}        Preview error message.
	 */
	var getPreviewErrorMessage = function( data ) {
		data = data || {};

		// Maybe return a string error payload as-is
		if ( 'string' === typeof data ) {
			return data;
		}

		// Maybe return the server message
		if ( data.message ) {
			return data.message;
		}

		return _settings.i18n.loadError || 'Could not load the preview. Try again.';
	};



	/**
	 * Get user-facing message for a failed send response.
	 *
	 * @param  {Object|string} data Error payload from the server.
	 * @return {string}        Send error message.
	 */
	var getSendErrorMessage = function( data ) {
		var errorCode;
		var messagesByCode;

		data = data || {};

		// Maybe return a string error payload as-is
		if ( 'string' === typeof data ) {
			return data;
		}

		errorCode = data.error_code || '';
		messagesByCode = {
			rate_limited:         _settings.i18n.rateLimited,
			in_progress:          _settings.i18n.inProgress,
			disabled:             _settings.i18n.disabled,
			empty_payload:        _settings.i18n.emptyPayload,
			ineligible_domain:    _settings.i18n.ineligibleDomain,
			request_failed:       _settings.i18n.requestFailed,
			disabled_by_constant: _settings.i18n.disabledByConstant,
		};

		// Maybe return a mapped error code message
		if ( messagesByCode[ errorCode ] ) {
			return messagesByCode[ errorCode ];
		}

		// Maybe treat HTTP 429 as rate limited
		if ( 429 === parseInt( data.response_code, 10 ) ) {
			return _settings.i18n.rateLimited || 'Usage data was sent recently. Try again later.';
		}

		// Maybe return the server message
		if ( data.message ) {
			return data.message;
		}

		return _settings.i18n.requestFailed || _settings.i18n.sendError || 'The usage data could not be sent. Try again later.';
	};



	/**
	 * Normalize error payload from a failed AJAX response.
	 *
	 * @param  {Object|null}  payload  Parsed JSON response body.
	 * @return {Object}                  Normalized error payload.
	 */
	var getSendErrorDataFromResponse = function( payload ) {
		var data = payload && payload.data ? payload.data : null;

		// Maybe wrap a string payload
		if ( 'string' === typeof data ) {
			return { message: data };
		}

		return data || {};
	};



	/**
	 * POST to admin-ajax.php and parse a JSON response body.
	 *
	 * @param  {Object}  requestData  Form fields for the request.
	 * @return {Promise}               Promise resolving to a parsed payload or null.
	 */
	var postAdminAjax = function( requestData ) {
		var body = new window.URLSearchParams();
		var key;

		for ( key in requestData ) {
			var value;
			var j;

			if ( ! Object.prototype.hasOwnProperty.call( requestData, key ) ) { continue; }

			value = requestData[ key ];

			// Serialize array values like jQuery admin-ajax requests
			if ( Array.isArray( value ) ) {
				for ( j = 0; j < value.length; j++ ) {
					body.append( key + '[]', value[ j ] );
				}
			}
			// Otherwise append a scalar field
			else {
				body.append( key, value );
			}
		}

		return window.fetch( _settings.ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body:        body.toString(),
		} ).then( function( response ) {
			return response.json().catch( function() {
				return null;
			} );
		} ).catch( function() {
			return null;
		} );
	};



	/**
	 * Show feedback below the preview payload or inline actions.
	 *
	 * @param {Element} target  Feedback element.
	 * @param {string}  message Feedback message.
	 * @param {string}  type    Feedback type.
	 */
	var showFeedback = function( target, message, type ) {
		// Bail if target is missing
		if ( ! target ) { return; }

		target.textContent = message;
		target.classList.remove( _settings.isErrorClass, _settings.isSuccessClass, _settings.isHiddenClass );
		target.classList.add( getModifierClass( _settings.feedbackTypeClassTemplate, type ) );
	};



	/**
	 * Clear modal or inline feedback.
	 *
	 * @param {Element} target Feedback element.
	 */
	var clearFeedback = function( target ) {
		target = target || _feedback;

		// Bail if target is missing
		if ( ! target ) { return; }

		target.textContent = '';
		target.classList.remove( _settings.isErrorClass, _settings.isSuccessClass );
		target.classList.add( _settings.isHiddenClass );
	};



	/**
	 * Enable or disable the inline Send now button.
	 * Only the saved enabled state may enable the button — toggling the checkbox alone is not enough.
	 *
	 * @param {boolean} enabled Whether reporting is saved as enabled.
	 */
	var updateInlineSendButtonState = function( enabled ) {
		// Bail if inline send button is not available
		if ( ! _inlineSendButton ) { return; }

		_inlineSendButton.disabled = ! enabled;

		// Maybe clear feedback when the button is disabled
		if ( ! enabled ) {
			clearFeedback( _inlineFeedback );
		}
	};



	/**
	 * Update the primary send action label and visibility.
	 *
	 * @param {boolean} enabled Whether reporting is enabled.
	 */
	var updateSendButton = function( enabled ) {
		var label = enabled
			? ( _settings.i18n.sendNow || 'Send now' )
			: ( _settings.i18n.enableAndSendNow || 'Turn ON and send now' );

		// Bail if usage tracking is turned off in PHP constant
		if ( true === _settings.isDisabledByConstant ) {
			if ( _sendButton ) {
				_sendButton.classList.add( _settings.isHiddenClass );
			}
			return;
		}

		// Bail if send button is missing
		if ( ! _sendButton ) { return; }

		_sendButton.textContent = label;
		_sendButton.classList.remove( _settings.isHiddenClass );
	};



	/**
	 * Show the loading state in the preview area.
	 */
	var setLoadingState = function() {
		// Maybe show the loading message in the preview area
		if ( _payload ) {
			_payload.textContent = _settings.i18n.loading || 'Loading preview...';
		}

		// Maybe hide the modal send button while loading
		if ( _sendButton ) {
			_sendButton.classList.add( _settings.isHiddenClass );
		}
	};



	/**
	 * Open the modal dialog.
	 */
	var openModal = function() {
		var body = document.querySelector( _settings.bodySelector );

		// Bail if modal is missing
		if ( ! _modal ) { return; }

		_modal.classList.add( _settings.isOpenClass );
		_modal.setAttribute( _settings.ariaHiddenAttribute, 'false' );

		// Maybe lock page scroll while the modal is open
		if ( body ) {
			body.classList.add( _settings.modalOpenBodyClass );
		}
	};



	/**
	 * Close the modal dialog.
	 *
	 * @param {Event} _e Optional unused click event.
	 */
	var closeModal = function( _e ) {
		var body = document.querySelector( _settings.bodySelector );

		// Bail if modal is missing
		if ( ! _modal ) { return; }

		_modal.classList.remove( _settings.isOpenClass );
		_modal.setAttribute( _settings.ariaHiddenAttribute, 'true' );

		// Maybe restore page scroll
		if ( body ) {
			body.classList.remove( _settings.modalOpenBodyClass );
		}
	};



	/**
	 * Handle a successful preview AJAX response.
	 *
	 * @param {Object} response  Parsed AJAX response.
	 * @param {Object} formState Form state used for the request.
	 */
	var handlePreviewResponse = function( response, formState ) {
		// Maybe show a preview error
		if ( ! response || ! response.success ) {
			showFeedback( _feedback, getPreviewErrorMessage( response && response.data ), 'error' );

			// Maybe clear the preview payload
			if ( _payload ) {
				_payload.textContent = '';
			}

			updateSendButton( _settings.enabledYesValue === formState.enabled );
			return;
		}

		_isEnabled = !! response.data.is_enabled;

		// Maybe show the preview payload
		if ( _payload ) {
			_payload.textContent = response.data.payload_json || '';
		}

		updateSendButton( _isEnabled );
	};



	/**
	 * Handle a failed preview AJAX request.
	 *
	 * @param {Object|null} response  Parsed JSON response body.
	 * @param {Object}      formState Form state used for the request.
	 */
	var handlePreviewFailure = function( response, formState ) {
		showFeedback( _feedback, getPreviewErrorMessage( getSendErrorDataFromResponse( response ) ), 'error' );

		// Maybe clear the preview payload
		if ( _payload ) {
			_payload.textContent = '';
		}

		updateSendButton( _settings.enabledYesValue === formState.enabled );
	};



	/**
	 * Request the preview payload from the server.
	 */
	var loadPreview = function() {
		var formState = getFormState();

		postAdminAjax( {
			action:      _settings.previewAction,
			nonce:       _settings.nonce,
			enabled:     formState.enabled,
			data_groups: formState.data_groups,
		} ).then( function( response ) {
			// Maybe handle a failed preview request
			if ( ! response ) {
				handlePreviewFailure( response, formState );
				return;
			}

			handlePreviewResponse( response, formState );
		} );
	};



	/**
	 * Open the preview modal and load the report payload.
	 *
	 * @param {Event} e Click event.
	 */
	var handlePreviewClick = function( e ) {
		e.preventDefault();

		clearFeedback();
		setLoadingState();
		openModal();
		loadPreview();
	};



	/**
	 * Apply the saved enabled state to the enable checkbox and Send now controls.
	 *
	 * @param {boolean} enabled Whether reporting is enabled.
	 */
	var applyEnabledState = function( enabled ) {
		var enableCheckbox = document.querySelector( _settings.enableCheckboxSelector );

		// Maybe check the enable checkbox
		if ( enabled && enableCheckbox ) {
			enableCheckbox.checked = true;
		}

		_isEnabled = !! enabled;
		updateSendButton( _isEnabled );
		updateInlineSendButtonState( _isEnabled );
	};



	/**
	 * Handle a successful send-now AJAX response.
	 *
	 * @param {Object} response        Parsed AJAX response.
	 * @param {Element} feedbackTarget Feedback element.
	 */
	var handleSendResponse = function( response, feedbackTarget ) {
		var isEnabled = !!( response && response.data && response.data.is_enabled );

		// Maybe show a send error — still apply enabled state when the option was already saved
		if ( ! response || ! response.success ) {
			if ( isEnabled ) {
				applyEnabledState( true );
			}

			showFeedback(
				feedbackTarget,
				getSendErrorMessage( response && response.data ),
				'error'
			);
			return;
		}

		applyEnabledState( true );
		showFeedback(
			feedbackTarget,
			response.data.message || _settings.i18n.sendSuccess || 'Usage data sent.',
			'success'
		);
	};



	/**
	 * Enable or disable preview buttons while a send request is in flight.
	 *
	 * @param {boolean} disabled Whether preview buttons should be disabled.
	 */
	var setPreviewButtonsDisabled = function( disabled ) {
		var previewButtons = document.querySelectorAll( _settings.previewButtonSelector );
		var i;

		// Iterate preview buttons
		for ( i = 0; i < previewButtons.length; i++ ) {
			previewButtons[ i ].disabled = disabled;
		}
	};



	/**
	 * Restore send/preview button enabled state after a send request.
	 *
	 * @param {Element} trigger Button that triggered the send request.
	 */
	var restoreSendControls = function( trigger ) {
		// Maybe re-enable the trigger button
		if ( trigger ) {
			trigger.disabled = false;
		}

		setPreviewButtonsDisabled( false );
	};



	/**
	 * Send the site report immediately.
	 *
	 * @param {Event}  e       Click event.
	 * @param {string} context Send context: modal or inline.
	 */
	var sendReportNow = function( e, context ) {
		var formState;
		var enableIfDisabled;
		var trigger;
		var feedbackTarget;

		e.preventDefault();

		// Bail if usage tracking is turned off in wp-config.php
		if ( true === _settings.isDisabledByConstant ) { return; }

		context = context || _settings.sendContextModal;
		formState = getFormState();
		enableIfDisabled = _settings.enabledYesValue !== formState.enabled;
		trigger = _settings.sendContextInline === context ? _inlineSendButton : _sendButton;
		feedbackTarget = _settings.sendContextInline === context ? _inlineFeedback : _feedback;

		// Bail if inline send is used while reporting is not saved as enabled
		if ( _settings.sendContextInline === context && ! _isEnabled ) {
			return;
		}

		clearFeedback( feedbackTarget );

		// Disable send controls while the request is in flight
		if ( trigger ) {
			trigger.disabled = true;
		}

		setPreviewButtonsDisabled( true );

		postAdminAjax( {
			action:             _settings.sendNowAction,
			nonce:              _settings.nonce,
			enabled:            formState.enabled,
			data_groups:        formState.data_groups,
			enable_if_disabled: enableIfDisabled ? _settings.enabledYesValue : _settings.enabledNoValue,
		} ).then( function( response ) {
			// Maybe handle a failed send request
			if ( ! response || ! response.success ) {
				var errorData = getSendErrorDataFromResponse( response );

				// Maybe apply enabled state when the option was already saved before the send failed
				if ( errorData && errorData.is_enabled ) {
					applyEnabledState( true );
				}

				showFeedback( feedbackTarget, getSendErrorMessage( errorData ), 'error' );
				return;
			}

			handleSendResponse( response, feedbackTarget );
		} ).finally( function() {
			restoreSendControls( trigger );
		} );
	};



	/**
	 * Handle Send now click from the modal.
	 *
	 * @param {Event} e Click event.
	 */
	var handleModalSendClick = function( e ) {
		sendReportNow( e, _settings.sendContextModal );
	};



	/**
	 * Handle Send now click from the Tools settings page.
	 *
	 * @param {Event} e Click event.
	 */
	var handleInlineSendClick = function( e ) {
		sendReportNow( e, _settings.sendContextInline );
	};



	/**
	 * Route document clicks for preview, send, and close actions.
	 *
	 * @param {Event} e Click event.
	 */
	var handleClick = function( e ) {
		var matchedElement;

		// PREVIEW
		if ( matchedElement = e.target.closest( _settings.previewButtonSelector ) ) {
			handlePreviewClick( e );
		}
		// INLINE SEND
		else if ( matchedElement = e.target.closest( _settings.inlineSendButtonSelector ) ) {
			handleInlineSendClick( e );
		}
		// MODAL SEND
		else if ( matchedElement = e.target.closest( _settings.modalSendButtonSelector ) ) {
			handleModalSendClick( e );
		}
		// CLOSE MODAL
		else if ( matchedElement = e.target.closest( _settings.closeAttributeSelector ) ) {
			e.preventDefault();
			closeModal();
		}
	};



	/**
	 * Close the modal when Escape is pressed.
	 *
	 * @param {KeyboardEvent} e Keyboard event.
	 */
	var handleKeyDown = function( e ) {
		// Bail if Escape was not pressed
		if ( FCUtils.keyboardKeys.ESC !== e.key ) { return; }

		// Bail if modal is not open
		if ( ! _modal || ! _modal.classList.contains( _settings.isOpenClass ) ) { return; }

		closeModal();
	};





	/**
	 * Initialize the site report admin script.
	 *
	 * @param {Object} options Optional settings passed from PHP.
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		_modal = document.querySelector( _settings.modalSelector );
		_inlineSendButton = document.querySelector( _settings.inlineSendButtonSelector );
		_inlineFeedback = document.querySelector( _settings.inlineFeedbackSelector );

		// Bail if modal is not available
		if ( ! _modal ) { return; }

		_payload = _modal.querySelector( _settings.modalPayloadSelector );
		_feedback = _modal.querySelector( _settings.modalFeedbackSelector );
		_sendButton = _modal.querySelector( _settings.modalSendButtonSelector );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'keydown', handleKeyDown, true );

		// Enable Send now only when reporting is already saved as enabled
		_isEnabled = true === _settings.isEnabledSaved || _settings.enabledYesValue === _settings.isEnabledSaved;
		updateInlineSendButtonState( _isEnabled );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
