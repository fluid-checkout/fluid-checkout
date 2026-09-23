/**
 * Admin site report preview and send-now actions.
 */

( function( $ ) {
	'use strict';

	var _settings = window.fcAdminTelemetrySettings || {};
	var _i18n = _settings.i18n || {};
	var $modal;
	var $payload;
	var $feedback;
	var $sendButton;
	var $inlineSendButton;
	var $inlineFeedback;
	var isEnabled = false;



	/**
	 * Initialize the site report admin UI.
	 */
	var init = function() {
		$modal = $( '#fc-telemetry-modal' );
		$inlineSendButton = $( '.fc-telemetry-send-now-button' );
		$inlineFeedback = $( '.fc-telemetry-enable-actions__feedback' );

		// Bail if modal is not available
		if ( ! $modal.length ) { return; }

		$payload = $modal.find( '.fc-telemetry-modal__payload' );
		$feedback = $modal.find( '.fc-telemetry-modal__feedback' );
		$sendButton = $modal.find( '.fc-telemetry-modal__send-button' );

		$( document ).on( 'click', '.fc-telemetry-preview-button', openPreview );
		$( document ).on( 'click', '.fc-telemetry-send-now-button', sendReportNowInline );
		$( document.body ).on( 'change', '#fc_telemetry_enabled', handleEnableCheckboxChange );
		$modal.on( 'click', '[data-fc-telemetry-close]', closeModal );
		$sendButton.on( 'click', sendReportNowModal );
		$( document ).on( 'keydown', handleKeydown );

		updateInlineSendButtonVisibility( $( '#fc_telemetry_enabled' ).is( ':checked' ) );
	};



	/**
	 * Collect the current site report form state.
	 */
	var getFormState = function() {
		var enabled = $( '#fc_telemetry_enabled' ).is( ':checked' );
		var groups = [ 'basic_environment' ];

		if ( enabled ) {
			$( 'input[name="fc_telemetry_data_groups[]"]:checked' ).each( function() {
				var value = $( this ).val();

				if ( value && groups.indexOf( value ) === -1 ) {
					groups.push( value );
				}
			} );
		}

		return {
			enabled: enabled ? 'yes' : 'no',
			data_groups: groups,
		};
	};



	/**
	 * Open the preview modal and load the report payload.
	 *
	 * @param {Event} event Click event.
	 */
	var openPreview = function( event ) {
		event.preventDefault();

		clearFeedback();
		setLoadingState();
		openModal();
		loadPreview();
	};



	/**
	 * Request the preview payload from the server.
	 */
	var loadPreview = function() {
		var formState = getFormState();

		$.post(
			_settings.ajaxUrl,
			{
				action: 'fc_telemetry_preview',
				nonce: _settings.nonce,
				enabled: formState.enabled,
				data_groups: formState.data_groups,
			}
		).done( function( response ) {
			if ( ! response || ! response.success ) {
				showFeedback( $feedback, getPreviewErrorMessage( response && response.data ), 'error' );
				$payload.text( '' );
				updateSendButton( formState.enabled === 'yes' );
				return;
			}

			isEnabled = !! response.data.is_enabled;
			$payload.text( response.data.payload_json || '' );
			updateSendButton( isEnabled );
		} ).fail( function( xhr ) {
			showFeedback( $feedback, getPreviewErrorMessage( getSendErrorDataFromXhr( xhr ) ), 'error' );
			$payload.text( '' );
			updateSendButton( formState.enabled === 'yes' );
		} );
	};



	/**
	 * Get user-facing message for a failed preview response.
	 *
	 * @param {Object} data Error payload from the server.
	 */
	var getPreviewErrorMessage = function( data ) {
		data = data || {};

		if ( 'string' === typeof data ) {
			return data;
		}

		if ( data.message ) {
			return data.message;
		}

		return _i18n.loadError || 'Could not load the site report preview. Try again.';
	};



	/**
	 * Get user-facing message for a failed send response.
	 *
	 * @param {Object} data Error payload from the server.
	 */
	var getSendErrorMessage = function( data ) {
		data = data || {};

		if ( 'string' === typeof data ) {
			return data;
		}

		var errorCode = data.error_code || '';
		var messagesByCode = {
			rate_limited: _i18n.rateLimited,
			in_progress: _i18n.inProgress,
			disabled: _i18n.disabled,
			empty_payload: _i18n.emptyPayload,
			ineligible_domain: _i18n.ineligibleDomain,
			request_failed: _i18n.requestFailed,
		};

		if ( messagesByCode[ errorCode ] ) {
			return messagesByCode[ errorCode ];
		}

		if ( 429 === parseInt( data.response_code, 10 ) ) {
			return _i18n.rateLimited || 'A site report was sent recently. Try again later.';
		}

		if ( data.message ) {
			return data.message;
		}

		return _i18n.requestFailed || _i18n.sendError || 'The site report could not be sent. Try again later.';
	};



	/**
	 * Normalize error payload from an AJAX failure response.
	 *
	 * @param {jqXHR} xhr jQuery XHR object.
	 */
	var getSendErrorDataFromXhr = function( xhr ) {
		var data = xhr && xhr.responseJSON ? xhr.responseJSON.data : null;

		if ( 'string' === typeof data ) {
			return { message: data };
		}

		return data || {};
	};



	/**
	 * Send the site report immediately from the modal.
	 *
	 * @param {Event} event Click event.
	 */
	var sendReportNowModal = function( event ) {
		sendReportNow( event, 'modal' );
	};



	/**
	 * Send the site report immediately from the Tools settings page.
	 *
	 * @param {Event} event Click event.
	 */
	var sendReportNowInline = function( event ) {
		sendReportNow( event, 'inline' );
	};



	/**
	 * Send the site report immediately.
	 *
	 * @param {Event}  event   Click event.
	 * @param {string} context Send context: modal or inline.
	 */
	var sendReportNow = function( event, context ) {
		event.preventDefault();

		context = context || 'modal';

		var formState = getFormState();
		var enableIfDisabled = formState.enabled !== 'yes';
		var $trigger = 'inline' === context ? $inlineSendButton : $sendButton;
		var $feedbackTarget = 'inline' === context ? $inlineFeedback : $feedback;

		if ( 'inline' === context && formState.enabled !== 'yes' ) {
			return;
		}

		clearFeedback( $feedbackTarget );
		$trigger.prop( 'disabled', true );
		$( '.fc-telemetry-preview-button' ).prop( 'disabled', true );

		$.post(
			_settings.ajaxUrl,
			{
				action: 'fc_telemetry_send_now',
				nonce: _settings.nonce,
				enabled: formState.enabled,
				data_groups: formState.data_groups,
				enable_if_disabled: enableIfDisabled ? 'yes' : 'no',
			}
		).done( function( response ) {
			if ( ! response || ! response.success ) {
				showFeedback(
					$feedbackTarget,
					getSendErrorMessage( response && response.data ),
					'error'
				);
				return;
			}

			if ( enableIfDisabled ) {
				$( '#fc_telemetry_enabled' ).prop( 'checked', true );
			}

			isEnabled = true;
			updateSendButton( true );
			updateInlineSendButtonVisibility( true );
			showFeedback(
				$feedbackTarget,
				response.data.message || _i18n.sendSuccess || 'Site report sent successfully.',
				'success'
			);
		} ).fail( function( xhr ) {
			showFeedback( $feedbackTarget, getSendErrorMessage( getSendErrorDataFromXhr( xhr ) ), 'error' );
		} ).always( function() {
			$trigger.prop( 'disabled', false );
			$( '.fc-telemetry-preview-button' ).prop( 'disabled', false );
		} );
	};



	/**
	 * Handle enable checkbox changes and update the inline Send now button.
	 */
	var handleEnableCheckboxChange = function() {
		updateInlineSendButtonVisibility( $( '#fc_telemetry_enabled' ).is( ':checked' ) );
	};



	/**
	 * Show or hide the inline Send now button.
	 *
	 * @param {boolean} enabled Whether the enable checkbox is checked.
	 */
	var updateInlineSendButtonVisibility = function( enabled ) {
		// Bail if inline send button is not available
		if ( ! $inlineSendButton.length ) { return; }

		if ( enabled ) {
			$inlineSendButton.removeClass( 'is-hidden' );
			return;
		}

		$inlineSendButton.addClass( 'is-hidden' );
		clearFeedback( $inlineFeedback );
	};



	/**
	 * Update the primary send action label and visibility.
	 *
	 * @param {boolean} enabled Whether reporting is enabled.
	 */
	var updateSendButton = function( enabled ) {
		$sendButton
			.text( enabled ? ( _i18n.sendNow || 'Send now' ) : ( _i18n.enableAndSendNow || 'Enable and send now' ) )
			.removeClass( 'is-hidden' );
	};



	/**
	 * Show the loading state in the preview area.
	 */
	var setLoadingState = function() {
		$payload.text( _i18n.loading || 'Loading report preview...' );
		$sendButton.addClass( 'is-hidden' );
	};



	/**
	 * Show feedback below the preview payload or inline actions.
	 *
	 * @param {jQuery} $target  Feedback element.
	 * @param {string} message  Feedback message.
	 * @param {string} type     Feedback type.
	 */
	var showFeedback = function( $target, message, type ) {
		$target
			.text( message )
			.removeClass( 'is-error is-success is-hidden' )
			.addClass( 'is-' + type );
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
			.removeClass( 'is-error is-success' )
			.addClass( 'is-hidden' );
	};



	/**
	 * Open the modal dialog.
	 */
	var openModal = function() {
		$modal.addClass( 'is-open' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fc-telemetry-modal-open' );
	};



	/**
	 * Close the modal dialog.
	 *
	 * @param {Event} event Optional click event.
	 */
	var closeModal = function( event ) {
		if ( event ) { event.preventDefault(); }

		$modal.removeClass( 'is-open' ).attr( 'aria-hidden', 'true' );
		$( 'body' ).removeClass( 'fc-telemetry-modal-open' );
	};



	/**
	 * Close the modal when Escape is pressed.
	 *
	 * @param {KeyboardEvent} event Keyboard event.
	 */
	var handleKeydown = function( event ) {
		if ( 'Escape' !== event.key ) { return; }
		if ( ! $modal.hasClass( 'is-open' ) ) { return; }

		closeModal();
	};



	$( init );

} )( jQuery );
