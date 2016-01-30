if (typeof jQuery != 'undefined' && typeof stoolballCharts != "undefined") {
	$(function(){
		"use strict";
		
		// Work out path to data
		var path =  document.location.pathname;
		var pathEnd = path.lastIndexOf("/statistics") + 11; 
		var json =  path.substring(0, pathEnd)  + ".json";
		var season =  path.substring(pathEnd).replace(/[^0-9-]/gi, "");
		var scope = "";
		if (season)  {
			json  += "?season=" + season;
			scope = " in " + season.replace("-", "/");
		} 
		
		// Show pie charts for results
		$.getJSON(json, function(data) {
			
			stoolballCharts.displayPieChart("all-results-chart", data.all, "All results" + scope, "match", "matches", 200);
			
			// Show separate charts only if there is data for both, and if it's a summer season (winter seasons are all in one place)
			var homeMatchCount = stoolballCharts.calculateTotal(data.home);
			var awayMatchCount = stoolballCharts.calculateTotal(data.away);
			if (homeMatchCount && awayMatchCount && (!scope || scope.indexOf("/") === -1))
			{
			    stoolballCharts.displayPieChart("home-results-chart", data.home, "Home results" + scope, "match", "matches", 200);
			    stoolballCharts.displayPieChart("away-results-chart", data.away, "Away results" + scope, "match", "matches", 200);
			}

			stoolballCharts.displayStackedBar("opponents-chart", data.opponents, "Results against all opponents", "Opponent (matches played)", "Matches", "Matches");			
		});		
	});
}