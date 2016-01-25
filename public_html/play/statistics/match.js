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
	
			stoolballCharts.displayLine("worm-chart", data.worm,  "Total scores after each over for both teams", "Runs", "Overs", 400, 200, [1,3], "<%=value%> runs scored");
			
			var firstInnings = data.manhattanFirstInnings.datasets[0].data;
			var secondInnings = data.manhattanSecondInnings.datasets[0].data;
			var allOvers = firstInnings.concat(secondInnings);
			var max = Math.max.apply(null, allOvers);
			
			stoolballCharts.displayBar("manhattan-chart-first-innings", data.manhattanFirstInnings,  "", "Runs", "Overs", 400, 200, [1], "<%= value %> runs scored in over <%=label%>", max);
			stoolballCharts.displayBar("manhattan-chart-second-innings", data.manhattanSecondInnings,  "", "Runs", "Overs", 400, 200, [3], "<%= value %> runs scored in over <%=label%>", max);
		});		
	});
}