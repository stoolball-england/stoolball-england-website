if (typeof jQuery != 'undefined' && typeof stoolballCharts != "undefined") {
	$(function(){
		"use strict";

		// Work out path to data
		var path =  document.location.pathname;
		if (path.substring(path.length -1) === "/"){
			path = path.substring(0, path.length -1);
		}  

		if (path.substring(path.length -11) === "/statistics"){
			path = path.substring(0, path.length -11);
		}
		
		path += ".json";
		path += document.location.search;

		$.getJSON(path, function(data) {
	
			stoolballCharts.displayLine("worm-chart", data.worm,  "Total scores after each over for both teams", "Runs", "Overs", 400, 200);
		});		
	});
}