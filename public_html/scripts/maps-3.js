Stoolball.Maps =
{
	/**
	* Creates a icon for a Google maps marker
	*/
	WicketIcon : function()
	{
		return new google.maps.MarkerImage('/images/features/map-marker.gif',
		new google.maps.Size(21, 57),
		new google.maps.Point(0,0), // origin
		new google.maps.Point(10, 53)); // anchor
	},

	/**
	* Creates a shadow for a Google maps marker
	*/
	WicketShadow : function()
	{
		return new google.maps.MarkerImage('/images/features/map-marker-shadow.png',
		new google.maps.Size(45, 57),
		new google.maps.Point(0,0),
		new google.maps.Point(10, 53));
	}
}