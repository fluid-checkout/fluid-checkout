jQuery(document).ready( function($) {
	var map = geocoder = marker = map_marker = infowindow = '';
	
	$wcfmmp_user_location_lat = jQuery("#wcfmmp_user_location_lat").val();
	$wcfmmp_user_location_lng = jQuery("#wcfmmp_user_location_lng").val();
	
	function initialize() {
  	
  	if( wcfm_maps.lib == 'google' ) {
  		$('#wcfmmp_user_location').parent().append('<i class="wcfmmmp_locate_icon" style="background-image: url('+wcfmmp_checkout_map_options.locate_svg+')"></i>');
			var latlng = new google.maps.LatLng( wcfmmp_checkout_map_options.default_lat, wcfmmp_checkout_map_options.default_lng, 13 );
			map = new google.maps.Map(document.getElementById("wcfmmp-user-locaton-map"), {
					center: latlng,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					zoom: parseInt( wcfmmp_checkout_map_options.default_zoom )
			});
			var customIcon = {
												url: wcfmmp_checkout_map_options.store_icon,
												scaledSize: new google.maps.Size( wcfmmp_checkout_map_options.icon_width, wcfmmp_checkout_map_options.icon_height ), // scaled size
											};
			marker = new google.maps.Marker({
					map: map,
					position: latlng,
					animation: google.maps.Animation.DROP,
					icon: customIcon,
					draggable: true,
			});
		
			var wcfmmp_user_location_input = document.getElementById("wcfmmp_user_location");
			//map.controls[google.maps.ControlPosition.TOP_LEFT].push(wcfmmp_user_location_input);
			geocoder = new google.maps.Geocoder();
			var autocomplete = new google.maps.places.Autocomplete(wcfmmp_user_location_input);
			autocomplete.bindTo("bounds", map);
			infowindow = new google.maps.InfoWindow();   
		
			autocomplete.addListener("place_changed", function() {
				infowindow.close();
				marker.setVisible(false);
				var place = autocomplete.getPlace();
				if (!place.geometry) {
					window.alert("Autocomplete returned place contains no geometry");
					return;
				}
	
				// If the place has a geometry, then present it on a map.
				if (place.geometry.viewport) {
					map.fitBounds(place.geometry.viewport);
				} else {
					map.setCenter(place.geometry.location);
					map.setZoom( parseInt( wcfmmp_checkout_map_options.default_zoom ) );
				}
	
				marker.setPosition(place.geometry.location);
				marker.setVisible(true);
	
				bindDataToForm(place.formatted_address,place.geometry.location.lat(),place.geometry.location.lng(), false);
				infowindow.setContent(place.formatted_address);
				infowindow.open(map, marker);
				showTooltip(infowindow,marker,place.formatted_address);
		
			});
			google.maps.event.addListener(marker, "dragend", function() {
				geocoder.geocode({"latLng": marker.getPosition()}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						if (results[0]) {        
							bindDataToForm(results[0].formatted_address,marker.getPosition().lat(),marker.getPosition().lng(), true);
							infowindow.setContent(results[0].formatted_address);
							infowindow.open(map, marker);
							showTooltip(infowindow,marker,results[0].formatted_address);
						}
					}
				});
			});
		} else {
			$('#wcfmmp_user_location').replaceWith( '<div id="leaflet_wcfmmp_user_location"></div><input type="hidden" class="wcfm_custom_hide" name="wcfmmp_user_location" id="wcfmmp_user_location" />' );
			
			if( $wcfmmp_user_location_lat && $wcfmmp_user_location_lng ) {
				map = new L.Map( 'wcfmmp-user-locaton-map', {zoom: parseInt( wcfmmp_checkout_map_options.default_zoom ), center: new L.latLng([$wcfmmp_user_location_lat, $wcfmmp_user_location_lng]) });
				map.addLayer(new L.TileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'));	//base layer
				map_marker = L.marker([$wcfmmp_user_location_lat, $wcfmmp_user_location_lng], {draggable: 'true'}).addTo(map).on('click', function() {
					window.open( 'https://www.openstreetmap.org/?mlat='+$wcfmmp_user_location_lat+'&mlon='+$wcfmmp_user_location_lng+'#map=14/'+$wcfmmp_user_location_lat+'/'+$wcfmmp_user_location_lng, '_blank');
				});
			} else {
				map = new L.Map( 'wcfmmp-user-locaton-map', {zoom: parseInt( wcfmmp_checkout_map_options.default_zoom ), center: new L.latLng([wcfmmp_checkout_map_options.default_lat, wcfmmp_checkout_map_options.default_lng]) });
				map.addLayer(new L.TileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'));	//base layer
				map_marker = L.marker([0,0], {draggable: 'true'});
			}
			
			map_marker.on('dragend', function(event) {
				var position = map_marker.getLatLng();
				
				var jsonQuery = "http://nominatim.openstreetmap.org/reverse?format=json&lat=" + position.lat + "&lon=" + position.lng + "&zoom=18&addressdetails=1";
    		
				var address = '';
				
    		$.getJSON(jsonQuery).done( function (result_data) {
    			var road;
    			if(result_data.address.road) {
    				address += result_data.address.road + ", ";
    			} else if (result_data.address.pedestrian) {
    				address += result_data.address.pedestrian + ", ";
    			} else {
    				address = "";
    			}
    			
    			if( result_data.address.house_number ) address += result_data.address.house_number + ", ";
    			if( result_data.address.city_district ) address += result_data.address.city_district + ", ";
    			if( result_data.address.city ) address += result_data.address.city + ", ";
    			if( result_data.address.postcode ) address += result_data.address.postcode;
    			
    			bindDataToForm( address, position.lat, position.lng, true );
    			
    			var popup_text = address;

    			map_marker.bindPopup(popup_text).openPopup();
    		});
			});
		
			var searchControl = new L.Control.Search({
														container: 'leaflet_wcfmmp_user_location',
														url: 'https://nominatim.openstreetmap.org/search?format=json&q={s}',
														jsonpParam: 'json_callback',
														propertyName: 'display_name',
														propertyLoc: ['lat','lon'],
														marker: map_marker,
														moveToLocation: function(latlng, title, map) {
															bindDataToForm( title, latlng.lat, latlng.lng, true );
															map.setView(latlng, parseInt( wcfmmp_checkout_map_options.default_zoom ) ); // access the zoom
														},
														//autoCollapse: true,
														initial: false,
														collapsed:false,
														autoType: false,
														minLength: 2
													});
			
			map.addControl( searchControl );  //inizialize search control
			
			//$('#leaflet_wcfmmp_user_location').find('.search-input').val($('#store_location').val());
			
			setTimeout(function() {
				map.invalidateSize();
			}, 3000 );
		}
	}
	
	function bindDataToForm(address,lat,lng, find_field_refresh) {
		if( find_field_refresh ) {
			if( wcfm_maps.lib == 'google' ) {
			 document.getElementById("wcfmmp_user_location").value = address;
			} else {
				$('#wcfmmp_user_location').val( address );
				$("#leaflet_wcfmmp_user_location").find('.search-input').val( address );
			}
		}
		//document.getElementById("store_location").value = address;
		document.getElementById("wcfmmp_user_location_lat").value = lat;
		document.getElementById("wcfmmp_user_location_lng").value = lng;
		
		$( document.body ).trigger( 'update_checkout' );
	}
	function showTooltip(infowindow,marker,address){
	 google.maps.event.addListener(marker, "click", function() { 
				infowindow.setContent(address);
				infowindow.open(map, marker);
		});
	}
	
	function setUser_CurrentLocation() {
		navigator.geolocation.getCurrentPosition( function( position ) {
			$current_location_fetched = true;
			console.log( position.coords.latitude, position.coords.longitude );
			if( wcfm_maps.lib == 'google' ) {
				geocoder.geocode( {
						location: {
								lat: position.coords.latitude,
								lng: position.coords.longitude
						}
				}, function ( results, status ) {
						if ( 'OK' === status ) {
							bindDataToForm( results[0].formatted_address, position.coords.latitude, position.coords.longitude, true );
							var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
							marker.setPosition(latlng);
							marker.setVisible(true);
							
							infowindow.setContent( results[0].formatted_address );
							infowindow.open( map, marker );
							showTooltip( infowindow, marker, results[0].formatted_address );
						}
				} )
			} else {
				$.get('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='+position.coords.latitude+'&lon='+position.coords.longitude, function(data) {
					bindDataToForm( data.address.road, position.coords.latitude, position.coords.longitude, true );
					
					map_marker.bindPopup(data.address.road).openPopup();
				});
			}
		});
	}
	
	if( jQuery("#wcfmmp_user_location_lat").length > 0 ) {
		setTimeout( function() {
			initialize();
			
			if ( navigator.geolocation ) {
				$('.wcfmmmp_locate_icon').on( 'click', function () {
					setUser_CurrentLocation();
				});
				
				setUser_CurrentLocation();
			}
		}, 1000 );
	}
});