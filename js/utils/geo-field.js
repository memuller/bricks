jQuery(function($) {
	function make_marker(){
		for(i in markers){ markers[i].setMap(null); }
		marker_position = new google.maps.LatLng($('.geo_lat').val(), $('.geo_lng').val()) ;
		marker = new google.maps.Marker({ position: marker_position, title: '', draggable: true}) ; 
		markers.push(marker) ; marker.setMap(map) ;
		google.maps.event.addListener(marker, 'mouseup', function(event){
			$('.geo_lat').val(event.latLng.lat());
			$('.geo_lng').val(event.latLng.lng());
		});
	}

	var map = new google.maps.Map($('.geo_map')[0], {
		zoom: 13,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		disableDefaultUI: true,
		zoomControl: true,
		center: new google.maps.LatLng($('.geo_lat').val(),$('.geo_lng').val())
	});
	var geocoder = new google.maps.Geocoder();  
	var marker ; var markers =[]; make_marker();
	

	$(function() {
		
		$('.geo').on('keydown', function(event){
			if(event.which == 13){
				make_marker();
				event.preventDefault();
				return false;
			}
			if(event.which == 40){

			}
		});
		
		geo_autocomplete = $(".geo").autocomplete({
			delay: 600, minLength: 12,
			source: function(request, response) { 
				if (geocoder == null){ geocoder = new google.maps.Geocoder(); }
				geocoder.geocode( {'address': request.term }, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {

						var searchLoc = results[0].geometry.location;
						var lat = results[0].geometry.location.lat();
						var lng = results[0].geometry.location.lng();
						$('.geo_lat').val(lat); 
						$('.geo_lng').val(lng);
						
						//map.fitBounds(results[0].geometry.bounds);
						var latlng = new google.maps.LatLng(lat, lng);
						map.setCenter(latlng);
						var bounds = results[0].geometry.bounds;
						geocoder.geocode({'latLng': latlng}, function(results1, status1) {
							if (status1 == google.maps.GeocoderStatus.OK) {
								if (results1[1]) {
									response($.map(results1, function(loc) {
										return {
											label  : loc.formatted_address,
											value  : loc.formatted_address,
											bounds   : loc.geometry.bounds
										}
									}));
								}
							}
						});
					}
				});
			},
			select: function(event,ui){
				make_marker();
				var pos = ui.item.position;
				var lct = ui.item.locType;
				var bounds = ui.item.bounds;
				if (bounds){
					map.fitBounds(bounds);
				}
			}
		});
	});   

});