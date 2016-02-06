if (typeof jQuery != 'undefined' && typeof stoolballCharts != "undefined") {
	$(function(){
		"use strict";
		
		// Work out path to data
		var path =  document.location.pathname;
		if (path.substring(path.length -1) === "/"){
			path = path.substring(0, path.length -1);
		}  
		path += ".json?v=2";
		path += document.location.search;
		
		$.getJSON(path, function(data) {
	
			stoolballCharts.displayStackedBar("score-spread-chart", data.scoreSpread,  "Range of batting scores", "Scores", "Innings", "Innings");

			if (data.battingForm.labels.length > 1) {
				stoolballCharts.displayLine("batting-form-chart", data.battingForm, "Batting form (higher is better)", "Runs", "Time", {
					multiTooltipTemplate: "<%=value%> <%if (value==1){%>run<%}else{%>runs<%}%>" 
				});
			}
						
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