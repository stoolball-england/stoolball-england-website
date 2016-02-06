if (typeof jQuery != 'undefined' && typeof stoolballCharts != "undefined") {
	$(function(){
		"use strict";
		
		// Work out path to data
		var path =  document.location.pathname;
		if (path.substring(path.length -1) === "/"){
			path = path.substring(0, path.length -1);
		}  
		path += "/batting.json";
		path += document.location.search;
		
		$.getJSON(path, function(data) {
	
			stoolballCharts.displayStackedBar("score-spread-chart", data.scoreSpread,  "Range of batting scores", "Scores", "Innings", "Innings");

			if (data.battingForm.labels.length > 1) {
				stoolballCharts.displayLine("batting-form-chart", data.battingForm, "Batting form (higher is better)", "Runs", "Time", {
					multiTooltipTemplate: "<%=value%> <%if (value==1){%>run<%}else{%>runs<%}%>" 
				});
			}

			stoolballCharts.displayPieChart("dismissals-chart", data.dismissals, "How this player gets out", "innings", "innings",  200);
		});		
	});
}