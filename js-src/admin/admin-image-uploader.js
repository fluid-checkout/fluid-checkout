/**
 * File admin-image-uploader.js
 *
 * Handles custom upload fields.
 *
 * Author: Diego Versiani
 * Contact: https://diegoversiani.me/
 */
( function( $ ) {

	'use strict';

	var custom_uploader;
	var dialog_title, button_text, library_type, preview_id, control_id, multiple = false;


	jQuery( document ).ready( function() {
		jQuery('.image-upload-select-button').on( 'click', select_image_button_click );
		jQuery('.image-upload-clear-button').on( 'click', clear_image_button_click );
	});



	function select_image_button_click() {
		event.preventDefault();

		dialog_title = $(this).data('dialog-title');
		button_text = $(this).data('dialog-button-text');
		library_type = $(this).data('library-type');
		preview_id = $(this).data('preview-id');
		control_id = $(this).data('control-id');
		multiple = $(this).data('multiple');

		//Extend the wp.media object
		custom_uploader = wp.media.frames.file_frame = wp.media({
		title: dialog_title,
		button: {
			text: button_text
		},
		library : { type : library_type },
		multiple: multiple
		});

		// When a file is selected
		// - grab the IDs and set it as the text field's value
		// - update image previews
		custom_uploader.on('select', handle_images_selected);

		//Open the uploader dialog
		custom_uploader.open();
	}



	function clear_image_button_click() {
		event.preventDefault();

		var preview_id, control_id, message;
		preview_id = $(this).data('preview-id');
		control_id = $(this).data('control-id');
		message = $(this).data('message');

		jQuery('#' + control_id).val('');
		jQuery('#' + preview_id).empty().append(message);
	}



	function handle_images_selected() {
		var ids = '';
		var attachments = custom_uploader.state().get('selection');

		var html = '<ul class="uploaded-image-list">';

		for (var i = 0; i < attachments.models.length; i++) {
		var attachment = attachments.models[i];

		if ( custom_uploader.options.library.type == 'image' ) {
			html += '<li class="uploaded-image"><img src="' + attachment.attributes.url + '"></li>';
		}

		ids += attachment.attributes.id;
		if ( i < attachments.models.length-1 ) {
			ids += ',';
		}
		}

		html += '</ul>';

		jQuery('#' + control_id).val(ids);
		jQuery('#' + preview_id).empty();
		jQuery('#' + preview_id).append(html);
	}



	function get_extension( url ){
		return url.substr((url.lastIndexOf('.') + 1));
	}

} )( jQuery );
