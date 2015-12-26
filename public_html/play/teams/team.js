if (typeof jQuery != 'undefined' && typeof stoolballCharts != "undefined") {
	$(function(){
		"use strict";
		
		if ($(window).width() < 800) return;
		
		// Work out path to data
		var path =  document.location.pathname;
		if (path.substring(path.length -1) === "/"){
			path = path.substring(0, path.length -1);
		}  
		var json = path + "/statistics.json";

		// Show pie charts for results
		$.getJSON(json, function(data) {
			
			stoolballCharts.displayPieChart("all-results-chart", data.all, "All results", "match", "matches", 185, "large");
		});
	});
}