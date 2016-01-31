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
	
			stoolballCharts.displayLine("worm-chart", data.worm,  "Total score after each over", "Runs", "Overs", { multiTooltipTemplate: "<%=value%> runs scored" });
			stoolballCharts.displayLine("run-rate-chart", data.runRate,  "Run rate after each over", "Runs per over", "Overs", { multiTooltipTemplate: "<%=value%> runs per over" });
			
			var firstInnings = data.manhattanFirstInnings.datasets[0].data;
			var secondInnings = data.manhattanSecondInnings.datasets[0].data;
			var allOvers = firstInnings.concat(secondInnings);
			var max = Math.max.apply(null, allOvers);
			
			stoolballCharts.displayBar("manhattan-chart-first-innings", data.manhattanFirstInnings,  "", "Overs", "Run", "Runs", [1], max, { tooltipTemplate: "<%= value %> runs scored in over <%=label%>"});
			stoolballCharts.displayBar("manhattan-chart-second-innings", data.manhattanSecondInnings,  "", "Overs", "Run", "Runs", [3], max, { tooltipTemplate: "<%= value %> runs scored in over <%=label%>"});
		});		
	});
}