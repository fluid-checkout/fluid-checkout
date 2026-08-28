/**
 * Admin site report preview and send-now actions.
 */

( function( $ ) {
	'use strict';

	var _settings = window.fcAdminSiteReportSettings || {};
	var _i18n = _settings.i18n || {};
	var $modal;
	var $payload;
	var $feedback;
	var $sendButton;
	var isEnabled = false;



	/**
	 * Initialize the site report admin UI.
	 */
	var init = function() {
		$modal = $( '#fc-site-report-modal' );

		// Bail if modal is not available
		if ( ! $modal.length ) { return; }

		$payload = $modal.find( '.fc-site-report-modal__payload' );
		$feedback = $modal.find( '.fc-site-report-modal__feedback' );
		$sendButton = $modal.find( '.fc-site-report-modal__send-button' );

		$( document ).on( 'click', '.fc-site-report-preview-button', openPreview );
		$modal.on( 'click', '[data-fc-site-report-close]', closeModal );
		$sendButton.on( 'click', sendReportNow );
		$( document ).on( 'keydown', handleKeydown );
	};



	/**
	 * Collect the current site report form state.
	 */
	var getFormState = function() {
		var enabled = $( '#fc_enable_site_report' ).is( ':checked' );
		var groups = [ 'basic_environment' ];

		if ( enabled ) {
			$( 'input[name="fc_site_report_data_groups[]"]:checked' ).each( function() {
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
				action: 'fc_site_report_preview',
				nonce: _settings.nonce,
				enabled: formState.enabled,
				data_groups: formState.data_groups,
			}
		).done( function( response ) {
			if ( ! response || ! response.success ) {
				showFeedback( response && response.data && response.data.message ? response.data.message : ( _i18n.loadError || 'Could not load the site report preview. Try again.' ), 'error' );
				$payload.text( '' );
				updateSendButton( formState.enabled === 'yes' );
				return;
			}

			isEnabled = !! response.data.is_enabled;
			$payload.text( response.data.payload_json || '' );
			updateSendButton( isEnabled );
		} ).fail( function() {
			showFeedback( _i18n.loadError || 'Could not load the site report preview. Try again.', 'error' );
			$payload.text( '' );
			updateSendButton( formState.enabled === 'yes' );
		} );
	};



	/**
	 * Send the site report immediately.
	 *
	 * @param {Event} event Click event.
	 */
	var sendReportNow = function( event ) {
		event.preventDefault();

		var formState = getFormState();
		var enableIfDisabled = formState.enabled !== 'yes';

		clearFeedback();
		$sendButton.prop( 'disabled', true );

		$.post(
			_settings.ajaxUrl,
			{
				action: 'fc_site_report_send_now',
				nonce: _settings.nonce,
				enabled: formState.enabled,
				data_groups: formState.data_groups,
				enable_if_disabled: enableIfDisabled ? 'yes' : 'no',
			}
		).done( function( response ) {
			if ( ! response || ! response.success ) {
				showFeedback( response && response.data && response.data.message ? response.data.message : ( _i18n.sendError || 'Could not send the site report. Try again.' ), 'error' );
				return;
			}

			if ( enableIfDisabled ) {
				$( '#fc_enable_site_report' ).prop( 'checked', true ).trigger( 'change' );
			}

			isEnabled = true;
			updateSendButton( true );
			showFeedback( response.data.message || _i18n.sendSuccess || 'Site report sent successfully.', 'success' );
		} ).fail( function( xhr ) {
			var message = _i18n.sendError || 'Could not send the site report. Try again.';

			if ( xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ) {
				message = xhr.responseJSON.data.message;
			}

			showFeedback( message, 'error' );
		} ).always( function() {
			$sendButton.prop( 'disabled', false );
		} );
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
	 * Show feedback below the preview payload.
	 *
	 * @param {string} message Feedback message.
	 * @param {string} type    Feedback type.
	 */
	var showFeedback = function( message, type ) {
		$feedback
			.text( message )
			.removeClass( 'is-error is-success is-hidden' )
			.addClass( 'is-' + type );
	};



	/**
	 * Clear modal feedback.
	 */
	var clearFeedback = function() {
		$feedback
			.text( '' )
			.removeClass( 'is-error is-success' )
			.addClass( 'is-hidden' );
	};



	/**
	 * Open the modal dialog.
	 */
	var openModal = function() {
		$modal.addClass( 'is-open' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fc-site-report-modal-open' );
	};



	/**
	 * Close the modal dialog.
	 *
	 * @param {Event} event Optional click event.
	 */
	var closeModal = function( event ) {
		if ( event ) { event.preventDefault(); }

		$modal.removeClass( 'is-open' ).attr( 'aria-hidden', 'true' );
		$( 'body' ).removeClass( 'fc-site-report-modal-open' );
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
