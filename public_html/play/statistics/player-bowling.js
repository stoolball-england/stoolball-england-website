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
	
			if (data.economy.labels.length > 1) {
				stoolballCharts.displayLine("economy-chart", data.economy, "Bowling form – economy (lower is better)", "Runs conceded per over", "Time", {
					multiTooltipTemplate: "<%=value%> <%if (value==1){%>run<%}else{%>runs<%}%>" 
				});
			}
			
			if (data.bowlingAverage.labels.length > 1) {
				stoolballCharts.displayLine("bowling-average-chart", data.bowlingAverage, "Bowling form – average (lower is better)", "Runs conceded per wicket", "Time", {
					multiTooltipTemplate: "<%=value%> <%if (value==1){%>run<%}else{%>runs<%}%>" 
				});
			}
			
			for (var i = 0; i < data.wickets.length; i++) {
				if (data.wickets[i].value) {
					stoolballCharts.displayPieChart("wickets-chart", data.wickets, "How this player takes wickets", "wickets", "wickets",  200);
				}
				break;
			}
		});		
	});
}