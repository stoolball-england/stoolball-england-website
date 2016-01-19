if (typeof jQuery != 'undefined' && typeof stoolballCharts != "undefined") {
	$(function(){
		"use strict";
		
		// Work out path to data
		var path =  document.location.pathname;
		if (path.substring(path.length -1) === "/"){
			path = path.substring(0, path.length -1);
		}  
		path += ".json";
		path += document.location.search;
		
		$.getJSON(path, function(data) {
	
			stoolballCharts.displayStackedBar("score-spread-chart", data.scoreSpread,  "Range of batting scores", "Innings", "Scores", 400, 200);
			
			// Show pie charts for results
			stoolballCharts.displayPieChart("dismissals-chart", data.dismissals, "How this player gets out", "innings", "innings",  200);
			
			for (var i = 0; i < data.wickets.length; i++) {
				if (data.wickets[i].value) {
					stoolballCharts.displayPieChart("wickets-chart", data.wickets, "How this player takes wickets", "wickets", "wickets",  200);
				}
				break;
			}
		});		
	});
}