/**
 * Admin image uploader setting field — media library select and clear.
 *
 * DEPENDS ON:
 * - jQuery  // wp.media frame events
 * - wp.media
 */
(function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminImageUploader = factory( root );
	}
})( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		selectButtonSelector:                 '.image-upload-select-button',
		clearButtonSelector:                  '.image-upload-clear-button',

		uploadedImageListClass:               'uploaded-image-list',
		uploadedImageClass:                   'uploaded-image',

		dialogTitleAttribute:                 'data-dialog-title',
		dialogButtonTextAttribute:            'data-dialog-button-text',
		libraryTypeAttribute:                 'data-library-type',
		previewIdAttribute:                   'data-preview-id',
		controlIdAttribute:                   'data-control-id',
		multipleAttribute:                    'data-multiple',
		messageAttribute:                     'data-message',

		imageLibraryType:                     'image',
	};

	var _mediaFrame = null;
	var _activeSelectButton = null;



	/**
	 * METHODS
	 */



	/**
	 * Get a data attribute value from an element.
	 *
	 * @param  {Element} element   Element with data attributes.
	 * @param  {string}  attribute Attribute name from settings.
	 * @return {string}  Attribute value, or empty string.
	 */
	var getDataAttribute = function( element, attribute ) {
		// Bail if element missing
		if ( ! element ) { return ''; }

		return element.getAttribute( attribute ) || '';
	};



	/**
	 * Update the hidden control and preview after media selection.
	 */
	var handleImagesSelected = function() {
		var controlId = getDataAttribute( _activeSelectButton, _settings.controlIdAttribute );
		var previewId = getDataAttribute( _activeSelectButton, _settings.previewIdAttribute );
		var control = document.getElementById( controlId );
		var preview = document.getElementById( previewId );
		var attachments;
		var libraryType;
		var ids = '';
		var list;
		var i;
		var attachment;
		var listItem;
		var image;

		// Bail if control or preview missing
		if ( ! control || ! preview || ! _mediaFrame ) { return; }

		attachments = _mediaFrame.state().get( 'selection' );
		libraryType = _mediaFrame.options.library && _mediaFrame.options.library.type ? _mediaFrame.options.library.type : '';

		list = document.createElement( 'ul' );
		list.className = _settings.uploadedImageListClass;

		// Iterate selected attachments
		for ( i = 0; i < attachments.models.length; i++ ) {
			attachment = attachments.models[ i ];

			// Maybe render an image preview for image library selections
			if ( _settings.imageLibraryType === libraryType ) {
				listItem = document.createElement( 'li' );
				listItem.className = _settings.uploadedImageClass;

				image = document.createElement( 'img' );
				image.src = attachment.attributes.url;
				listItem.appendChild( image );
				list.appendChild( listItem );
			}

			ids += attachment.attributes.id;

			// Maybe append a comma between attachment IDs
			if ( i < attachments.models.length - 1 ) {
				ids += ',';
			}
		}

		control.value = ids;
		preview.textContent = '';
		preview.appendChild( list );
	};



	/**
	 * Open the media library for the Select button.
	 *
	 * @param {Event}   e      Click event.
	 * @param {Element} button Select button.
	 */
	var handleSelectClick = function( e, button ) {
		var dialogTitle = getDataAttribute( button, _settings.dialogTitleAttribute );
		var buttonText = getDataAttribute( button, _settings.dialogButtonTextAttribute );
		var libraryType = getDataAttribute( button, _settings.libraryTypeAttribute );
		var multiple = button.getAttribute( _settings.multipleAttribute );

		e.preventDefault();

		// Bail if wp.media is unavailable
		if ( ! window.wp || ! window.wp.media ) { return; }

		_activeSelectButton = button;

		_mediaFrame = window.wp.media.frames.file_frame = window.wp.media( {
			title: dialogTitle,
			button: {
				text: buttonText,
			},
			library: {
				type: libraryType,
			},
			multiple: !! multiple,
		} );

		_mediaFrame.on( 'select', handleImagesSelected );
		_mediaFrame.open();
	};



	/**
	 * Clear the selected image for the Clear button.
	 *
	 * @param {Event}   e      Click event.
	 * @param {Element} button Clear button.
	 */
	var handleClearClick = function( e, button ) {
		var controlId = getDataAttribute( button, _settings.controlIdAttribute );
		var previewId = getDataAttribute( button, _settings.previewIdAttribute );
		var message = getDataAttribute( button, _settings.messageAttribute );
		var control = document.getElementById( controlId );
		var preview = document.getElementById( previewId );

		e.preventDefault();

		// Bail if control or preview missing
		if ( ! control || ! preview ) { return; }

		control.value = '';
		preview.textContent = '';
		preview.appendChild( document.createTextNode( message ) );
	};



	/**
	 * Route click events.
	 *
	 * @param {Event} e Click event.
	 */
	var handleClick = function( e ) {
		var matchedElement;

		// SELECT IMAGE
		if ( matchedElement = e.target.closest( _settings.selectButtonSelector ) ) {
			handleSelectClick( e, matchedElement );
		}
		// CLEAR IMAGE
		else if ( matchedElement = e.target.closest( _settings.clearButtonSelector ) ) {
			handleClearClick( e, matchedElement );
		}
	};





	/**
	 * Initialize the admin image uploader script.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Bail if jQuery is unavailable
		if ( ! _hasJQuery ) { return; }

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
