$(function()
{
	// Make the placeholder big enough for a map
	var mapControl = document.getElementById("map");
	mapControl.style.width = '100%';
	mapControl.style.height = '400px';

	// Create the map
	var myLatlng = new google.maps.LatLng(51.07064141136184, -0.31114161014556885);
	var myOptions = {
		zoom : $(window).width() > 400 ? 9 : 8,
		center : myLatlng,
		mapTypeId : google.maps.MapTypeId.ROADMAP
	};
	var map = new google.maps.Map(mapControl, myOptions);

	new google.maps.Marker({ position : new google.maps.LatLng(51.07064141136184, -0.31114161014556885), map : map, title : "Kay Price, Sussex County Stoolball Association" });
	new google.maps.Marker({ position : new google.maps.LatLng(50.924040, -0.140333), map : map, title : "Trevor Parsons, Mid Sussex" });
	new google.maps.Marker({ position : new google.maps.LatLng(50.790485, 0.2655421), map : map, title : "Trina Perry, Eastbourne" });
	new google.maps.Marker({ position : new google.maps.LatLng(50.9612347, 0.3018266), map : map, title : "Toni Wheatley, East Sussex" });
	new google.maps.Marker({ position : new google.maps.LatLng(51.2103987, -0.3243005), map : map, title : "Julie Oliver, Surrey" }); // approx
	new google.maps.Marker({ position : new google.maps.LatLng(51.161365, 0.262368), map : map, title : "Jenny Keates, Kent" });
});