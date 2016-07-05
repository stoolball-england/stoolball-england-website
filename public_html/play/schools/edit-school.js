"use strict";
if (typeof(jQuery)!=='undefined') {
	$(function(){
		
		var map, marker, geocodeUsing;

		
		function createMap()
		{
			// Get map placeholder
			var mapControl = document.getElementById('map');
			var latControl = document.getElementById('latitude');
			var longControl = document.getElementById('longitude');
			var precisionControl = document.getElementById('geoprecision');
	
			if (mapControl && latControl && longControl && precisionControl)
			{
				// Get coordinates and title
				var lati = latControl.value;
				var longi = longControl.value;
				var precision = precisionControl.value;
	
				// How accurate?
				var zoomLevel = (precision == '4') ? 11 : 14;
	
				// Special case: location not known
				if (lati.length == 0 || longi.length == 0 || precision.length == 0)
				{
					lati = 51.0465744;
					longi = -0.1235961;
					zoomLevel = 9;
				}
	
				// Make the placeholder big enough for a map
				mapControl.style.height = '500px';
	
				// Create the map
				var myLatlng = new google.maps.LatLng(lati, longi);
				var myOptions = {
					zoom : zoomLevel,
					center : myLatlng,
					mapTypeId : google.maps.MapTypeId.ROADMAP
				};
				map = new google.maps.Map(mapControl, myOptions);
	
				// Create marker
				marker = new google.maps.Marker({
					position : myLatlng,
					map : map,
					shadow : Stoolball.Maps.WicketShadow(),
					icon : Stoolball.Maps.WicketIcon(),
					draggable : true,
					zIndex : 1
				});
	
				google.maps.event.addListener(marker, 'dragend', markerMoved);
			}
		}
		
		/**
		 * Update geolocation fields when marker is moved
		 */
		function markerMoved(e)
		{
			var latControl = document.getElementById('latitude');
			var longControl = document.getElementById('longitude');
			var precisionControl = document.getElementById('geoprecision');
	
			if (latControl && longControl && precisionControl && marker)
			{
				var latLong = marker.getPosition();
				latControl.value = latLong.lat();
				longControl.value = latLong.lng();
				precisionControl.value = 1; // 1==Exact
			}
		}
		
		function geocode(e)
		{
			// Get map placeholder
			var latControl = document.getElementById('latitude');
			var longControl = document.getElementById('longitude');
			var precisionControl = document.getElementById('geoprecision');
			var street = document.getElementById('street');
			var locality = document.getElementById('locality');
			var town = document.getElementById('town');
			var county = document.getElementById('county');
			var postcode = document.getElementById('postcode');
	
			if (latControl && longControl && precisionControl)
			{
				// Got coordinates already?
				var lati = latControl.value;
				var longi = longControl.value;
				var precision = precisionControl.value;
	
				if (lati.length == 0 || longi.length == 0 || precision != 1)
				{
					// If not, get best address available and geocode
					var searchText = '';
					if (postcode && postcode.value.length > 0 && postcode.value.match(/^[A-Z]+[0-9 ]+[A-Z][A-Z]$/i))
					{
						searchText = postcode.value;
						geocodeUsing = 2;
					}
					else
					{
						if (street && street.value.length > 0)
						{
							searchText = street.value;
							geocodeUsing = 3;
						}
						if (town && town.value.length > 0)
						{
							if (searchText.length > 0)
							{
								searchText += ', ';
							}
							else
							{
								geocodeUsing = 4;
							}
							searchText += town.value;
						}
						if (county && county.value.length > 0)
						{
							if (searchText.length > 0) searchText += ', ';
							searchText += county.value;
						}
					}
	
					if (searchText.length > 0)
					{
						console.log(searchText);
						var url = "https://maps.googleapis.com/maps/api/geocode/json?key=AIzaSyClPFIxNgcVPTwxHYtvnil7eoZQclZF7XI&address=" + encodeURIComponent(searchText);
						$.getJSON(url).done(geocode_UpdateMap);
					}
				}
			}
		}

		function geocode_UpdateMap(geocodeResult)
		{
			// Get map placeholder
			var latControl = document.getElementById('latitude');
			var longControl = document.getElementById('longitude');
			var precisionControl = document.getElementById('geoprecision');
	
			if (latControl && longControl && precisionControl && geocodeResult.results[0])
			{
				latControl.value = geocodeResult.results[0].geometry.location.lat;
				longControl.value = geocodeResult.results[0].geometry.location.lng;
				precisionControl.value = geocodeUsing;
	
				var mapControl = document.getElementById('map');
				while (mapControl && mapControl.firstChild) {
					mapControl.removeChild(mapControl.firstChild);
				}
				createMap();
			}
		}
		
		createMap();
		$("<p class=\"map-instruction\">Drag the marker to the playing field or sports hall where you play stoolball most often.</p>").insertBefore("#map");
		$('#street,#town').change(geocode);
		
		
		// Autoformat postcode as it's typed
		$("#postcode").keyup(function(e){
			// Only act on alphanumeric keys, to avoid breaking navigation
			if (e.keyCode >= 48 && e.keyCode <= 90 && !e.ctrlKey && !e.altKey) {
				this.value = this.value.toUpperCase();
				this.value = this.value.replace(/^([A-Z]+)([0-9][0-9])([0-9])/, "$1$2 $3");
				this.value = this.value.replace(/^([A-Z]+)([0-9])([0-9])([A-Z])/, "$1$2 $3$4");
			}
			
			// Update map. This should happen onchange as with other fields, 
			// but setting the value in the lines above cancels the change event.
			geocode();
		});
	});
}