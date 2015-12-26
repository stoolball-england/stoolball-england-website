Stoolball.Page =
{
	Load : function()
	{
		if (document.getElementById && document.getElementsByTagName)
		{
			// Get map placeholder
			var geoControl = document.getElementById('geoGround');
			if (geoControl)
			{
				var latLong = geoControl.getElementsByTagName('span');
				if (latLong && latLong.length >= 3)
				{
					// Get coordinates and title
					var lati = latLong[0].firstChild.nodeValue;
					var longi = latLong[1].firstChild.nodeValue;
					var mapTitle = latLong[2].firstChild.nodeValue;
					var howExact = latLong[2].className;

					// How accurate?
					var exactMessage = '';
					var zoomLevel = 14;
					switch (howExact)
					{
						case 'postcode':
						exactMessage = 'Note: This map shows the nearest postcode. The ground should be nearby.';
						break;
						case 'street':
						exactMessage = 'Note: This map shows the nearest road. The ground should be nearby.';
						break;
						case 'town':
						exactMessage = 'Note: This map shows the town or village. Contact the home team to find the ground.';
						zoomLevel = 11;
						break;
					}

					// Make the placeholder big enough for a map
					var mapControl = geoControl.getElementsByTagName('div')[0];
					mapControl.style.width = '100%';
					mapControl.style.height = '500px';

					// Create the map
					var myLatlng = new google.maps.LatLng(lati, longi);
					var myOptions = {
						zoom: zoomLevel,
						center: myLatlng,
						mapTypeId: google.maps.MapTypeId.ROADMAP
					};
					var map = new google.maps.Map(mapControl, myOptions);

					var marker = new google.maps.Marker({
						position: myLatlng,
						map: map,
						shadow: Stoolball.Maps.WicketShadow(),
						icon: Stoolball.Maps.WicketIcon(),
						title: $('h1').html(),
						zIndex: 1
					});


					// Add a heading to introduce the map
					var mapHeading = document.createElement('h2');
					mapHeading.appendChild(document.createTextNode('Map of ' + mapTitle));
					geoControl.parentNode.insertBefore(mapHeading, geoControl);

					if (exactMessage.length > 0)
					{
						var exactInfo = document.createElement('p');
						exactInfo.appendChild(document.createTextNode(exactMessage));
						geoControl.parentNode.insertBefore(exactInfo, geoControl);
					}

				}
			}
		}
	}
}
$(document).ready(Stoolball.Page.Load);