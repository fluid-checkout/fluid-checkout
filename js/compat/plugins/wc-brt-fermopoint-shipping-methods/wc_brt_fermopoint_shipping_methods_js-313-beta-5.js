jQuery(document).ready(function(){
	// checkPudableCart();
});

// jQuery(window).load(function(){
jQuery(window).on('load', function(){ // messo on per fix problema segnalato nel tk 12736. Se non dovesse risolvere, provare a caricare jquery migrate (https://code.jquery.com/jquery-migrate-1.4.1.min.js) e usare il classico .load 

	if( jQuery('input[name^="shipping_method"]').length > 0 ){
		if( isSelectedBrtFermopointShippingMethod() ){
			checkPudableCartNew();
			// disableCodPayment();
			// disableCheckShippingAddress();
			// showMapOrList();
			// checkGeolocation();
			// updateShippingAddressWithPudo(null);
		} else {
			enablePudoShippingMethodNew();
			enableCodPayment();
			// enableCheckShippingAddress();
			hideMap();
			updateShippingAddressWithPudo(null);
		}
	}

});

jQuery( 'body' ).on( 'change', 'input[name^="shipping_method"]', function() {

	if( jQuery(this).val().startsWith('wc_brt_fermopoint_shipping_methods_custom') ){
		checkPudableCartNew();
		// disableCodPayment();
		// disableCheckShippingAddress();
		// showMapOrList();
		// checkGeolocation();
		// updateShippingAddressWithPudo(null);
	} else {
		enablePudoShippingMethodNew();
		enableCodPayment();
		// enableCheckShippingAddress();
		hideMap();
		updateShippingAddressWithPudo(null);
	}

});

jQuery( 'body' ).on( 'click', 'img.updatePudo', function(){
	if( isSelectedBrtFermopointShippingMethod() ){
		checkPudableCartNew();
	}
});

jQuery( 'body' ).on( 'focusout', 'input#billing_city, input#billing_postcode, input#shipping_city, input#shipping_postcode', function(){
	if( isSelectedBrtFermopointShippingMethod() ){
		checkPudableCartNew();
	}
});

jQuery( 'body' ).on( 'change', 'select#billing_country, select#shipping_country', function() {
	if( isSelectedBrtFermopointShippingMethod() ){
		checkPudableCartNew();
	}
});

jQuery( 'body' ).on( 'focusout', 'input#billing_phone, input#billing_email', function(){
	if( isSelectedBrtFermopointShippingMethod() ){
		checkPudableCartNew();
	}
});

jQuery( 'body' ).on( 'change', 'input#ship-to-different-address-checkbox', function() {
	if( isSelectedBrtFermopointShippingMethod() ){
		checkPudableCartNew();
	}
});

var map;
var markers = [];
var listaPudo = [];
function initMap() {
	if(ajax_object.use_google_map == 'yes'){
		map = new google.maps.Map(document.getElementById('wc_brt_fermopoint_shipping_methods_custom-map'), {
			center: { lat: 44.493977, lng: 11.3430258 },
			zoom: 11,
			mapTypeControl: false,
			scaleControl: false,
			streetViewControl: false,
			rotateControl: false,
			fullscreenControl: false,
		});
	}
}

function loading(isLoading) {
	if(isLoading)
		jQuery('#wc_brt_fermopoint_shipping_methods_custom-list_container, #wc_brt_fermopoint_shipping_methods_custom-map_container').addClass('loading');
	else 
		jQuery('#wc_brt_fermopoint_shipping_methods_custom-list_container, #wc_brt_fermopoint_shipping_methods_custom-map_container').removeClass('loading');
}

function disableCodPayment() {
	jQuery(document).ajaxComplete(function(){
		jQuery(document).ready(function(){
			if(jQuery('.wc_payment_method.payment_method_cod')) {
				jQuery('.wc_payment_method.payment_method_cod').css('opacity', '0.5').find('input').attr('disabled', 'disabled');
				if( isSelectedBrtFermopointShippingMethod() )
					jQuery('.wc_payment_method.payment_method_cod input').prop('checked', false);
			}
		});
	});
}
function enableCodPayment() {
	jQuery(document).ajaxComplete(function(){
		jQuery(document).ready(function(){
			if(jQuery('.wc_payment_method.payment_method_cod'))
				jQuery('.wc_payment_method.payment_method_cod').css('opacity', '1').find('input').removeAttr('disabled');
				// jQuery('wc_payment_method.payment_method_cod').removeAttr('disabled');
		});

	});
}

function disableCheckShippingAddress() {
	jQuery(document).ajaxComplete(function(){
		jQuery(document).ready(function(){
			if( jQuery('.woocommerce-shipping-fields').length > 0 ) {
				jQuery('.woocommerce-shipping-fields').hide().find('input[name=ship_to_different_address]').attr('disabled', 'disabled');
			}			
		});
	});
}
function enableCheckShippingAddress() {
	jQuery(document).ajaxComplete(function(){
		jQuery(document).ready(function(){
			if( jQuery('.woocommerce-shipping-fields').length > 0 ) {
				jQuery('.woocommerce-shipping-fields').show().find('input[name=ship_to_different_address]').removeAttr('disabled');
			}
		});

	});
}

function showMapOrList() {
	if( isSelectedBrtFermopointShippingMethod() ){
		jQuery(document).ajaxComplete(function(){
			jQuery('#wc_brt_fermopoint_shipping_methods_custom-tr_container, #wc_brt_fermopoint_shipping_methods_custom-div_container').show();
		});
	}
}

function hideMap() {
	jQuery(document).ajaxComplete(function(){
		jQuery('#wc_brt_fermopoint_shipping_methods_custom-tr_container, #wc_brt_fermopoint_shipping_methods_custom-div_container').hide();
	});
}

function isSelectedBrtFermopointShippingMethod(){
	if( jQuery('input[name^="shipping_method"]:checked').length > 0 && jQuery('input[name^="shipping_method"]:checked').val().startsWith('wc_brt_fermopoint_shipping_methods_custom') ){
		return true;
	} else if( jQuery('input[name^="shipping_method"]').length == 1 && jQuery('input[name^="shipping_method"]').val().startsWith('wc_brt_fermopoint_shipping_methods_custom') ) {
		return true;
	}

	return false;
}

function isShippingDifferentAddressChecked(){
	var checkShipToDifferentAddress = jQuery('input[name=ship_to_different_address]').prop('checked');
	console.log("checkShipToDifferentAddress", checkShipToDifferentAddress);
	if( checkShipToDifferentAddress ){
		return true;
	}

	return false;
}

function checkGeolocation() {
	if(ajax_object.use_geolocation == 'yes'){
		getCoordsFromGeolocation();
	} else {
		getPudoFromAddress();
	}
}

function getCoordsFromGeolocation() {
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition((position) => {
			if(ajax_object.use_google_map == 'yes'){
				const center = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
				map.panTo(center);
			}
			getPudoFromCoords(position.coords);
		}, (err) => {
			console.log("err: "+err);
			getPudoFromAddress();
		});
	} else {
		console.log("Geolocation is not supported by this browser.");
		getPudoFromAddress();
	}
}

function getShippingAddress(){
	var returnObj = {};	
	
	if(isShippingDifferentAddressChecked()) {
		returnObj.city = jQuery('form .woocommerce-shipping-fields #shipping_city').val();
		returnObj.country = jQuery('form .woocommerce-shipping-fields #shipping_country').val();
		returnObj.cap = jQuery('form .woocommerce-shipping-fields #shipping_postcode').val();
	} else {
		returnObj.city = jQuery('form .woocommerce-billing-fields #billing_city').val();
		returnObj.country = jQuery('form .woocommerce-billing-fields #billing_country').val();
		returnObj.cap = jQuery('form .woocommerce-billing-fields #billing_postcode').val();		
	}
	return returnObj;
}

function getPudoFromCoords(coords) {
	if( isSelectedBrtFermopointShippingMethod() ){

		loading(true);

		jQuery.ajax({
			type: "POST",
			url : ajax_object.ajaxurl,
			data: {
				action: 'get_pudo_by_lat_lng',
				security: ajax_object.security,
				coords: coords,
			},
			success: function( data, textStatus, jqXHR ) {
				if(data.pudo && data.pudo.length > 0){
					if(ajax_object.use_google_map == 'yes'){
						addPudoToMap(data.pudo);
					} else {
						listaPudo = data.pudo;
						addPudoToList(data.pudo);
					}

					enablePudoShippingMethodNew();
					showMapOrList();
					// CHANGE: Removed call to function `updateShippingAddressWithPudo` as it was resetting the selected pudo.

				} else {

					var why_not_pudable = 'no_pudo_found';
					if( data.why_not_pudable ) {
						why_not_pudable = data.why_not_pudable;
					}
					disablePudoShippingMethodNew(why_not_pudable);
					hideMap();
					// enableCodPayment();
					// enableCheckShippingAddress();
					updateShippingAddressWithPudo(null);

				}
				loading(false);
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				console.log("err");
				console.log(textStatus);
				console.log(errorThrown);
				disablePudoShippingMethodNew('generic_error');
				enableCodPayment();
				// enableCheckShippingAddress();
				updateShippingAddressWithPudo(null);
				loading(false);
			}
		});
	} else {
		hideMap();
		enableCodPayment();
		// enableCheckShippingAddress();
		updateShippingAddressWithPudo(null);
	}
}

function getPudoFromAddress() {
	if( isSelectedBrtFermopointShippingMethod() ){

		loading(true);

		var shipping_address = getShippingAddress();
		jQuery.ajax({
			type: "POST",
			url : ajax_object.ajaxurl,
			data: {
				action: 'get_pudo_by_address',
				security: ajax_object.security,
				shipping_address: shipping_address
			},
			success: function( data, textStatus, jqXHR ) {
				if(data.pudo && data.pudo.length > 0){
					if(ajax_object.use_google_map == 'yes'){
						addPudoToMap(data.pudo);
					} else {
						listaPudo = data.pudo;
						addPudoToList(data.pudo);
					}

					enablePudoShippingMethodNew();
					showMapOrList();
					// CHANGE: Removed call to function `updateShippingAddressWithPudo` as it was resetting the selected pudo.
					
				} else {

					var why_not_pudable = 'no_pudo_found';
					if( data.why_not_pudable ) {
						why_not_pudable = data.why_not_pudable;
					}
					disablePudoShippingMethodNew(why_not_pudable);
					hideMap();
					// enableCodPayment();
					// enableCheckShippingAddress();
					updateShippingAddressWithPudo(null);

				}			
				loading(false);
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				console.log("err");
				console.log(textStatus);
				console.log(errorThrown);
				disablePudoShippingMethodNew('generic_error');
				enableCodPayment();
				// enableCheckShippingAddress();
				updateShippingAddressWithPudo(null);
				loading(false);
			}
		});
	} else {
		hideMap();
		enableCodPayment();
		// enableCheckShippingAddress();
		updateShippingAddressWithPudo(null);
	}
}

function addPudoToMap(pudoArray) {
	clearMarkers();
	var bounds = new google.maps.LatLngBounds();
	var infowindow = new google.maps.InfoWindow();
	for (var i = 0; i < pudoArray.length; i++) {
		// var latlngMarker = new google.maps.LatLng(pudoArray[i].latitude, pudoArray[i].longitude);
		const marker = new google.maps.Marker({
			position: new google.maps.LatLng(pudoArray[i].latitude, pudoArray[i].longitude),
			icon: ajax_object.plugin_url+"/includes/images/icon-gmap.png",
			map: map,
		});
		const infowindowContent = getInfoWindowContent(pudoArray[i]);
		const pudo = pudoArray[i];
		google.maps.event.addListener(marker, 'click', function(e) {
			infowindow.setContent(infowindowContent);
			infowindow.open(map, this);
			updateShippingAddressWithPudo(pudo);
			jQuery('#wc_brt_fermopoint_shipping_methods_custom-tr_container h3.pudo-label, #wc_brt_fermopoint_shipping_methods_custom-div_container h3.pudo-label').text("Hai selezionato il punto di ritiro: "+pudo.pointName);
		});
		bounds.extend(new google.maps.LatLng(pudoArray[i].latitude, pudoArray[i].longitude));

		markers.push(marker);
	}
	map.fitBounds(bounds);
}

function clearMarkers() {
	if(markers.length > 0){
		for (var i = 0; i < markers.length; i++) {
			markers[i].setMap(null);
		}
		markers = [];
	}
}

function getInfoWindowContent(pudo) {
	const days = ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica"];
	const id = pudo.pudoId;
	const title = pudo.pointName ? "<p><strong class='title'>"+ pudo.pointName +"</strong></p>" : "";
	const address = pudo.fullAddress ? "<p class='address'>"+ pudo.fullAddress +"</p>" : "";

	var orariHtml = "";
	orariHtml = "<ul class='orari'>";
	for(var key in pudo.orariOk){
		orariHtml += "<li>";
		orariHtml += "<strong>"+days[key]+"</strong>: ";
		orariHtml += pudo.orariOk[key].filter(Boolean).join(" / ");
		orariHtml += "</li>";
	}
	orariHtml += "</ul>";
	
	return "<div class='infowindow-brt-fermopoint'>"+title+address+orariHtml+"</div>";
}

function updateShippingAddressWithPudo(pudo) {
	jQuery(document).ready(function(){
		if( jQuery('form #wc_brt_fermopoint-custom_checkout_fields') ){
			if(pudo != null && pudo != undefined) {
				if(typeof(pudo) == 'object') {
					jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-selected_pudo').val(JSON.stringify(pudo));
					jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-pudo_id').val(pudo.pudoId);
				} else if(typeof(pudo) == 'number') {
					jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-selected_pudo').val(JSON.stringify(listaPudo[pudo]));
					jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-pudo_id').val(listaPudo[pudo].pudoId);
				} else {
					jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-selected_pudo').val("");
					jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-pudo_id').val("");
				}
			} else {
				jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-selected_pudo').val("");
				jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-pudo_id').val("");
			}
		}
	});
}

function addPudoToList(pudoArray) {
	jQuery(document).ajaxComplete(function(){
		var listContainer = jQuery('#wc_brt_fermopoint_shipping_methods_custom-list_container .pudo-list-scrollable');
		var appendHtml = "";
		const days = ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica"];

		// CHANGE: Get the id of the previously selected PUDO
		var selectedPudoId = jQuery('form #wc_brt_fermopoint-custom_checkout_fields #wc_brt_fermopoint-pudo_id').val();

		if(pudoArray.length > 0){
			appendHtml += "<ul class='lista_pudo'>";
			for (var i = 0; i < pudoArray.length; i++) {

				const pudo = pudoArray[i];
				const id = pudo.pudoId;
				const title = pudo.pointName ? "<h3>"+ pudo.pointName +"</h3>" : "";
				const address = pudo.fullAddress ? "<h5>"+ pudo.fullAddress +"</h5>" : "";
				const distance = pudo.distanceFromPoint ? "<h5>"+ (pudo.distanceFromPoint/1000) +" Km</h5>" : "";

				var orariHtml = "";
				orariHtml = "<ul>";
				for(var key in pudo.orariOk){
					orariHtml += "<li>";
					orariHtml += "<strong>"+days[key]+"</strong>: ";
					orariHtml += pudo.orariOk[key].filter(Boolean).join(" / ");
					orariHtml += "</li>";
				}
				orariHtml += "</ul>";

				// CHANGE: Maybe set additional classes
				var additionalClasses = selectedPudoId == id ? 'selected' : '';

				appendHtml += '<li class="pudo '+additionalClasses+'" onclick="selectPudo(this, '+i+')">';
					appendHtml += '<div class="row m-0">';
					// appendHtml += "<div class='accordion-container'>";
						// appendHtml += "<div class='accordion-title'>";
							appendHtml += "<div class='d-none d-lg-block col-lg-1 p-0 icona'><img src='"+ajax_object.plugin_url+"/includes/images/icon-gmap.png' /></div>";
							appendHtml += "<div class='col-9 col-sm-10 col-md-7 titolo'>"+ title + address +"</div>";
							appendHtml += "<div class='col-3 col-sm-2 col-lg-1 p-0 info'>";
								appendHtml += "<img width='25' height='25' src='"+ajax_object.plugin_url+"/includes/images/icon-info.png' />";
								appendHtml += "<div class='popup orari'>"+ orariHtml +"</div>";
							appendHtml += "</div>";
							appendHtml += "<div class='col-12 col-md-3 distanza'>"+ distance +"</div>";
						// appendHtml += "</div>";
						// appendHtml += "<div class='accordion-body'>";
						// 	if(orariHtml){
						// 		appendHtml += "<div class='popup orari'>"+ orariHtml +"</div>";
						// 	}
						// appendHtml += "</div>";
					appendHtml += "</div>";
					appendHtml += "<div class='clear'><!-- vuoto --></div>";
				appendHtml += "</li>";
			}
			appendHtml += "</ul>";
		}
		listContainer.html(appendHtml);

		// CHANGE: Maybe unset selected pudo if it is not in the list anymore
		var selectedPudo = jQuery( '#wc_brt_fermopoint_shipping_methods_custom-list_container li.pudo.selected' );
		if ( ! selectedPudo || 0 === selectedPudo.length ) {
			updateShippingAddressWithPudo(null);
		}
	});
}

function selectPudo(el, index) {
	jQuery('#wc_brt_fermopoint_shipping_methods_custom-list_container .pudo-list-scrollable ul.lista_pudo li.pudo').removeClass('selected');
	jQuery(el).addClass('selected');
	updateShippingAddressWithPudo(index);
}

function getShippingData(){
	var returnObj = {};	
	returnObj.country = jQuery('form .woocommerce-billing-fields #billing_country').val();
	returnObj.email = jQuery('form .woocommerce-billing-fields #billing_email').val();
	returnObj.phone = jQuery('form .woocommerce-billing-fields #billing_phone').val();
	return returnObj;
}

function checkPudableCartNew() {

	if( isSelectedBrtFermopointShippingMethod() ){
		var shipping_data = getShippingData();

		jQuery.ajax({
			type: "POST",
			url : ajax_object.ajaxurl,
			data: {
				action: 'check_pudable_cart',
				security: ajax_object.security,
				shipping_data: shipping_data
			},
			success: function( data, textStatus, jqXHR ) {
				if(data.pudable){
					enablePudoShippingMethodNew();

					disableCodPayment();
					// disableCheckShippingAddress();
					showMapOrList();
					checkGeolocation();
					// CHANGE: Removed call to function `updateShippingAddressWithPudo` as it was resetting the selected pudo.

				} else {
					var why_not_pudable = "generic_error";
					if( data.why_not_pudable )
						why_not_pudable = data.why_not_pudable;
						
					disablePudoShippingMethodNew(why_not_pudable);
					hideMap();
					enableCodPayment();
					// enableCheckShippingAddress();
					updateShippingAddressWithPudo(null);
				}
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				console.log("err");
				console.log(textStatus);
				console.log(errorThrown);
				disablePudoShippingMethodNew('generic_error');
				hideMap();
				enableCodPayment();
				// enableCheckShippingAddress();
				updateShippingAddressWithPudo(null);
			}
		});
	} else {
		hideMap();
		enableCodPayment();
		// enableCheckShippingAddress();
		updateShippingAddressWithPudo(null);
	}
}
// CHANGE: Added call to function `checkPudableCartNew` on `updated_checkout` event.
jQuery( 'body' ).on( 'updated_checkout', checkPudableCartNew );

function disablePudoShippingMethodNew(idError) {
	jQuery(document).ajaxComplete(function(){
		jQuery(document).ready(function(){
			if( jQuery('.woocommerce-shipping-methods input[id*="wc_brt_fermopoint_shipping_methods_custom"]').length > 0 ) {
				if(jQuery('#wc_brt_fermopoint_shipping_methods_custom-tr_alert-' + idError ).length > 0){
					jQuery('.wc_brt_tr_alert').hide();
					jQuery('#wc_brt_fermopoint_shipping_methods_custom-tr_alert-' + idError ).show();
				}
				if(jQuery('#wc_brt_fermopoint_shipping_methods_custom-div_alert-' + idError ).length > 0){
					jQuery('.wc_brt_div_alert').hide();
					jQuery('#wc_brt_fermopoint_shipping_methods_custom-div_alert-' + idError ).show();
				}
			}
		});
	});
}

function enablePudoShippingMethodNew() {
	jQuery(document).ajaxComplete(function(){
		jQuery(document).ready(function(){
			if( jQuery('.woocommerce-shipping-methods input[id*="wc_brt_fermopoint_shipping_methods_custom"]').length > 0 ) {
				if(jQuery('.wc_brt_tr_alert').length > 0){
					jQuery('.wc_brt_tr_alert').hide();
				}
				if(jQuery('.wc_brt_div_alert').length > 0){
					jQuery('.wc_brt_div_alert').hide();
				}
			}
		});
	});
}
