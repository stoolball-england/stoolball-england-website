Stoolball.Page = {
	Map : null,
	Marker : null,
	GeocodeUsing : 0,

	Load : function()
	{
		if (document.getElementById && document.getElementsByTagName)
		{
			Stoolball.Page.CreateMap();

			$('#lat').change(Stoolball.Page.UpdateMap);
			$('#long').change(Stoolball.Page.UpdateMap);

			var precisionControl = document.getElementById('geoprecision');
			if (precisionControl)
			{
				$(precisionControl).change(Stoolball.Page.UpdateMap);

				// Create button container
				var buttonPart = document.createElement('div');
				buttonPart.className = 'formPart';
				var buttonControl = document.createElement('div');
				buttonControl.className = 'formControl';
				buttonPart.appendChild(buttonControl);
				precisionControl.parentNode.parentNode.insertBefore(buttonPart, precisionControl.parentNode.nextSibling);

				// Create geocode button
				var geocode = document.createElement('input');
				geocode.setAttribute('type', 'button');
				geocode.setAttribute('value', 'Geocode');
				buttonControl.appendChild(geocode);
				$(geocode).click(Stoolball.Page.Geocode);

				// Create postcode button
				var postcode = document.createElement('input');
				postcode.setAttribute('type', 'button');
				postcode.setAttribute('value', 'Get postcode');
				buttonControl.appendChild(postcode);
				$(postcode).click(Stoolball.Page.GeocodePostcode);

				// Reset button
				var resetMap = document.createElement('input');
				resetMap.setAttribute('type', 'button');
				resetMap.setAttribute('value', 'Reset map');
				buttonControl.appendChild(resetMap);
				$(resetMap).click(Stoolball.Page.ResetMap);

			}

			$('#streetDescriptor').change(Stoolball.Page.Geocode);
			$('#town').change(Stoolball.Page.Geocode);
			$('#postcode').change(Stoolball.Page.Geocode);
		}
	},

	CreateMap : function()
	{
		// Get map placeholder
		var mapControl = document.getElementById('map');
		var latControl = document.getElementById('lat');
		var longControl = document.getElementById('long');
		var precisionControl = document.getElementById('geoprecision');

		if (mapControl && latControl && longControl && precisionControl)
		{
			// Get coordinates and title
			var lati = latControl.value;
			var longi = longControl.value;
			var precision = precisionControl.options[precisionControl.selectedIndex].value;

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
			Stoolball.Page.Map = new google.maps.Map(mapControl, myOptions);

			// Create marker
			Stoolball.Page.Marker = new google.maps.Marker({
				position : myLatlng,
				map : Stoolball.Page.Map,
				shadow : Stoolball.Maps.WicketShadow(),
				icon : Stoolball.Maps.WicketIcon(),
				draggable : true,
				zIndex : 1
			});

			google.maps.event.addListener(Stoolball.Page.Marker, 'dragend', Stoolball.Page.MarkerMoved);
		}
	},

	/**
	 * Change event handler for address fields
	 */
	UpdateMap : function()
	{
		var mapControl = document.getElementById('map');
		while (mapControl && mapControl.firstChild)
			mapControl.removeChild(mapControl.firstChild);
		Stoolball.Page.CreateMap();
	},

	/**
	 * Map is wrong, reset to Mid Sussex
	 */
	ResetMap : function()
	{
		var latControl = document.getElementById('lat');
		var longControl = document.getElementById('long');
		var precisionControl = document.getElementById('geoprecision');

		if (latControl && longControl && precisionControl)
		{
			latControl.value = '';
			longControl.value = '';
			precisionControl.options[precisionControl.selectedIndex].selected = false;
			precisionControl.options[0].selected = true;

			Stoolball.Page.UpdateMap();
		}
	},

	/**
	 * Update geolocation fields when marker is moved
	 */
	MarkerMoved : function(event)
	{
		var latControl = document.getElementById('lat');
		var longControl = document.getElementById('long');
		var precisionControl = document.getElementById('geoprecision');

		if (latControl && longControl && precisionControl && Stoolball.Page.Marker)
		{
			latLong = Stoolball.Page.Marker.getPosition();
			latControl.value = latLong.lat();
			longControl.value = latLong.lng();

			// Select 'exact' precision
			precisionControl.options[precisionControl.selectedIndex].selected = false;
			precisionControl.options[1].selected = true;
		}
	},

	Geocode : function()
	{
		// Get map placeholder
		var latControl = document.getElementById('lat');
		var longControl = document.getElementById('long');
		var precisionControl = document.getElementById('geoprecision');
		var streetDescriptor = document.getElementById('streetDescriptor');
		var locality = document.getElementById('locality');
		var town = document.getElementById('town');
		var county = document.getElementById('county');
		var postcode = document.getElementById('postcode');

		if (latControl && longControl && precisionControl)
		{
			// Got coordinates already?
			var lati = latControl.value;
			var longi = longControl.value;
			var precision = precisionControl.options[precisionControl.selectedIndex].value;

			if (lati.length == 0 || longi.length == 0 || precision.value != 1)
			{
				// If not, get best address available and geocode
				var searchText = '';
				if (postcode && postcode.value.length > 0)
				{
					searchText = postcode.value;
					Stoolball.Page.GeocodeUsing = 2;
				}
				else
				{
					if (streetDescriptor && streetDescriptor.value.length > 0)
					{
						searchText = streetDescriptor.value;
						Stoolball.Page.GeocodeUsing = 3;
					}
					if (town && town.value.length > 0)
					{
						if (searchText.length > 0)
						{
							searchText += ', ';
						}
						else
						{
							Stoolball.Page.GeocodeUsing = 4;
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
					var url = "https://maps.googleapis.com/maps/api/geocode/json?key=AIzaSyClPFIxNgcVPTwxHYtvnil7eoZQclZF7XI&address=" + encodeURIComponent(searchText);
					$.getJSON(url).done(Stoolball.Page.Geocode_UpdateMap);
				}
			}
		}
	},

	Geocode_UpdateMap : function(geocodeResult)
	{
		// Get map placeholder
		var latControl = document.getElementById('lat');
		var longControl = document.getElementById('long');
		var precisionControl = document.getElementById('geoprecision');

		if (latControl && longControl && precisionControl && geocodeResult.results[0])
		{
			latControl.value = geocodeResult.results[0].geometry.location.lat;
			longControl.value = geocodeResult.results[0].geometry.location.lng;

			// Select 'exact' precision
			precisionControl.options[precisionControl.selectedIndex].selected = false;
			precisionControl.options[Stoolball.Page.GeocodeUsing].selected = true;

			Stoolball.Page.UpdateMap();
		}
	},

	GeocodePostcode : function()
	{
		// Check there's no postcode already
		var whereBox = document.getElementById("postcode");
		if (whereBox.value.length > 0) return;

		// Reverse geocode current location using Google
		var geocoder = new google.maps.Geocoder();
		var latlng = new google.maps.LatLng($('#lat').val(), $('#long').val());

		geocoder.geocode({
			'latLng' : latlng
		}, function(results, status)
		{

			if (status == google.maps.GeocoderStatus.OK)
			{
				if (results[0] && whereBox)
				{
					var hLen = results.length;
					for ( var h = 0; h < hLen; h++)
					{
						// First geocode result is the most accurate.
						// Look through address components for the postcode
						var iLen = results[h].address_components.length;
						for ( var i = 0; i < iLen; i++)
						{

							var addr = results[h].address_components[i];
							var jLen = addr.types.length;

							for ( var j = 0; j < jLen; j++)
							{
								if (addr.types[j] == "postal_code" && addr.long_name.length >6)
								{
									// Populate the location search box
									whereBox.value = addr.long_name;
									return;
								}
							}
						}
					}
				}
			}

		});

	}
};

$(Stoolball.Page.Load);