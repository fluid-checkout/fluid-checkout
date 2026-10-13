/**
 * Admin site report preview and send-now actions.
 *
 * DEPENDS ON:
 * - jQuery  // AJAX helpers
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

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

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

		ajaxUrl:                              '',
		nonce:                                '',
		i18n: {
			loading:                          'Loading report preview...',
			loadError:                        'Could not load the site report preview. Try again.',
			sendError:                        'Could not send the site report. Try again.',
			sendSuccess:                      'Site report sent successfully.',
			sendNow:                          'Send now',
			enableAndSendNow:                 'Enable and send now',
			inProgress:                       'A site report request is already in progress. Try again in a moment.',
			disabled:                         'Site environment reporting is disabled.',
			emptyPayload:                     'No site report data is available to send.',
			ineligibleDomain:                 'This site domain cannot send environment reports (local or development domains are excluded).',
			rateLimited:                      'A site report was sent recently. Try again later.',
			requestFailed:                    'The site report could not be sent. Try again later.',
		},
	};

	var _isEnabled = false;
	var $modal;
	var $payload;
	var $feedback;
	var $sendButton;
	var $inlineSendButton;
	var $inlineFeedback;



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
		var enabled = $( _settings.enableCheckboxSelector ).is( ':checked' );
		var groups = [ _settings.basicEnvironmentGroup ];

		// Maybe collect checked data groups when reporting is enabled
		if ( enabled ) {
			// Iterate checked data group checkboxes
			$( _settings.dataGroupsCheckboxSelector ).each( function() {
				var value = $( this ).val();

				// Maybe add a new group value
				if ( value && -1 === groups.indexOf( value ) ) {
					groups.push( value );
				}
			} );
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

		return _settings.i18n.loadError || 'Could not load the site report preview. Try again.';
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
			rate_limited:      _settings.i18n.rateLimited,
			in_progress:       _settings.i18n.inProgress,
			disabled:          _settings.i18n.disabled,
			empty_payload:     _settings.i18n.emptyPayload,
			ineligible_domain: _settings.i18n.ineligibleDomain,
			request_failed:    _settings.i18n.requestFailed,
		};

		// Maybe return a mapped error code message
		if ( messagesByCode[ errorCode ] ) {
			return messagesByCode[ errorCode ];
		}

		// Maybe treat HTTP 429 as rate limited
		if ( 429 === parseInt( data.response_code, 10 ) ) {
			return _settings.i18n.rateLimited || 'A site report was sent recently. Try again later.';
		}

		// Maybe return the server message
		if ( data.message ) {
			return data.message;
		}

		return _settings.i18n.requestFailed || _settings.i18n.sendError || 'The site report could not be sent. Try again later.';
	};



	/**
	 * Normalize error payload from an AJAX failure response.
	 *
	 * @param  {jqXHR}  xhr jQuery XHR object.
	 * @return {Object} Normalized error payload.
	 */
	var getSendErrorDataFromXhr = function( xhr ) {
		var data = xhr && xhr.responseJSON ? xhr.responseJSON.data : null;

		// Maybe wrap a string payload
		if ( 'string' === typeof data ) {
			return { message: data };
		}

		return data || {};
	};



	/**
	 * Show feedback below the preview payload or inline actions.
	 *
	 * @param {jQuery} $target Feedback element.
	 * @param {string} message Feedback message.
	 * @param {string} type    Feedback type.
	 */
	var showFeedback = function( $target, message, type ) {
		$target
			.text( message )
			.removeClass( _settings.isErrorClass + ' ' + _settings.isSuccessClass + ' ' + _settings.isHiddenClass )
			.addClass( getModifierClass( _settings.feedbackTypeClassTemplate, type ) );
	};



	/**
	 * Clear modal or inline feedback.
	 *
	 * @param {jQuery} $target Feedback element.
	 */
	var clearFeedback = function( $target ) {
		$target = $target || $feedback;

		$target
			.text( '' )
			.removeClass( _settings.isErrorClass + ' ' + _settings.isSuccessClass )
			.addClass( _settings.isHiddenClass );
	};



	/**
	 * Show or hide the inline Send now button.
	 *
	 * @param {boolean} enabled Whether the enable checkbox is checked.
	 */
	var updateInlineSendButtonVisibility = function( enabled ) {
		// Bail if inline send button is not available
		if ( ! $inlineSendButton.length ) { return; }

		// Maybe show the inline send button
		if ( enabled ) {
			$inlineSendButton.removeClass( _settings.isHiddenClass );
			return;
		}

		$inlineSendButton.addClass( _settings.isHiddenClass );
		clearFeedback( $inlineFeedback );
	};



	/**
	 * Update the primary send action label and visibility.
	 *
	 * @param {boolean} enabled Whether reporting is enabled.
	 */
	var updateSendButton = function( enabled ) {
		var label = enabled
			? ( _settings.i18n.sendNow || 'Send now' )
			: ( _settings.i18n.enableAndSendNow || 'Enable and send now' );

		$sendButton
			.text( label )
			.removeClass( _settings.isHiddenClass );
	};



	/**
	 * Show the loading state in the preview area.
	 */
	var setLoadingState = function() {
		$payload.text( _settings.i18n.loading || 'Loading report preview...' );
		$sendButton.addClass( _settings.isHiddenClass );
	};



	/**
	 * Open the modal dialog.
	 */
	var openModal = function() {
		$modal.addClass( _settings.isOpenClass ).attr( _settings.ariaHiddenAttribute, 'false' );
		$( _settings.bodySelector ).addClass( _settings.modalOpenBodyClass );
	};



	/**
	 * Close the modal dialog.
	 *
	 * @param {Event} _e Optional unused click event.
	 */
	var closeModal = function( _e ) {
		$modal.removeClass( _settings.isOpenClass ).attr( _settings.ariaHiddenAttribute, 'true' );
		$( _settings.bodySelector ).removeClass( _settings.modalOpenBodyClass );
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
			showFeedback( $feedback, getPreviewErrorMessage( response && response.data ), 'error' );
			$payload.text( '' );
			updateSendButton( _settings.enabledYesValue === formState.enabled );
			return;
		}

		_isEnabled = !! response.data.is_enabled;
		$payload.text( response.data.payload_json || '' );
		updateSendButton( _isEnabled );
	};



	/**
	 * Handle a failed preview AJAX request.
	 *
	 * @param {jqXHR}  xhr       jQuery XHR object.
	 * @param {Object} formState Form state used for the request.
	 */
	var handlePreviewFailure = function( xhr, formState ) {
		showFeedback( $feedback, getPreviewErrorMessage( getSendErrorDataFromXhr( xhr ) ), 'error' );
		$payload.text( '' );
		updateSendButton( _settings.enabledYesValue === formState.enabled );
	};



	/**
	 * Request the preview payload from the server.
	 */
	var loadPreview = function() {
		var formState = getFormState();

		$.post(
			_settings.ajaxUrl,
			{
				action:      _settings.previewAction,
				nonce:       _settings.nonce,
				enabled:     formState.enabled,
				data_groups: formState.data_groups,
			}
		).done( function( response ) {
			handlePreviewResponse( response, formState );
		} ).fail( function( xhr ) {
			handlePreviewFailure( xhr, formState );
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
	 * Handle a successful send-now AJAX response.
	 *
	 * @param {Object}  response        Parsed AJAX response.
	 * @param {jQuery}  $feedbackTarget Feedback element.
	 * @param {boolean} enableIfDisabled Whether the request asked to enable reporting.
	 */
	var handleSendResponse = function( response, $feedbackTarget, enableIfDisabled ) {
		// Maybe show a send error
		if ( ! response || ! response.success ) {
			showFeedback(
				$feedbackTarget,
				getSendErrorMessage( response && response.data ),
				'error'
			);
			return;
		}

		// Maybe check the enable checkbox after enabling via send-now
		if ( enableIfDisabled ) {
			$( _settings.enableCheckboxSelector ).prop( 'checked', true );
		}

		_isEnabled = true;
		updateSendButton( true );
		updateInlineSendButtonVisibility( true );
		showFeedback(
			$feedbackTarget,
			response.data.message || _settings.i18n.sendSuccess || 'Site report sent successfully.',
			'success'
		);
	};



	/**
	 * Restore send/preview button enabled state after a send request.
	 *
	 * @param {jQuery} $trigger Button that triggered the send request.
	 */
	var restoreSendControls = function( $trigger ) {
		$trigger.prop( 'disabled', false );
		$( _settings.previewButtonSelector ).prop( 'disabled', false );
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
		var $trigger;
		var $feedbackTarget;

		e.preventDefault();

		context = context || _settings.sendContextModal;
		formState = getFormState();
		enableIfDisabled = _settings.enabledYesValue !== formState.enabled;
		$trigger = _settings.sendContextInline === context ? $inlineSendButton : $sendButton;
		$feedbackTarget = _settings.sendContextInline === context ? $inlineFeedback : $feedback;

		// Bail if inline send is used while reporting is disabled
		if ( _settings.sendContextInline === context && _settings.enabledYesValue !== formState.enabled ) {
			return;
		}

		clearFeedback( $feedbackTarget );
		$trigger.prop( 'disabled', true );
		$( _settings.previewButtonSelector ).prop( 'disabled', true );

		$.post(
			_settings.ajaxUrl,
			{
				action:             _settings.sendNowAction,
				nonce:              _settings.nonce,
				enabled:            formState.enabled,
				data_groups:        formState.data_groups,
				enable_if_disabled: enableIfDisabled ? _settings.enabledYesValue : _settings.enabledNoValue,
			}
		).done( function( response ) {
			handleSendResponse( response, $feedbackTarget, enableIfDisabled );
		} ).fail( function( xhr ) {
			showFeedback( $feedbackTarget, getSendErrorMessage( getSendErrorDataFromXhr( xhr ) ), 'error' );
		} ).always( function() {
			restoreSendControls( $trigger );
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
	 * Handle enable checkbox changes and update the inline Send now button.
	 */
	var handleEnableCheckboxChange = function() {
		updateInlineSendButtonVisibility( $( _settings.enableCheckboxSelector ).is( ':checked' ) );
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
	 * Route change events for the enable checkbox.
	 *
	 * @param {Event} e Change event.
	 */
	var handleChange = function( e ) {
		// ENABLE CHECKBOX
		if ( e.target.closest( _settings.enableCheckboxSelector ) ) {
			handleEnableCheckboxChange();
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
		if ( ! $modal.hasClass( _settings.isOpenClass ) ) { return; }

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

		// Bail if jQuery is unavailable
		if ( ! _hasJQuery ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		$modal = $( _settings.modalSelector );
		$inlineSendButton = $( _settings.inlineSendButtonSelector );
		$inlineFeedback = $( _settings.inlineFeedbackSelector );

		// Bail if modal is not available
		if ( ! $modal.length ) { return; }

		$payload = $modal.find( _settings.modalPayloadSelector );
		$feedback = $modal.find( _settings.modalFeedbackSelector );
		$sendButton = $modal.find( _settings.modalSendButtonSelector );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'change', handleChange, true );
		document.addEventListener( 'keydown', handleKeyDown, true );

		updateInlineSendButtonVisibility( $( _settings.enableCheckboxSelector ).is( ':checked' ) );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
