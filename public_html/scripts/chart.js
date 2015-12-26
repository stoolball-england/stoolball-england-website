var stoolballCharts =  (function (){

	function addColours(data, propertyName) {
	        
	    var colours = ["#7FAA84","#008044","#002b17","#503C03","#9a854b","#e7d197"];
	    while (colours.length < data.length) {
	    	colours = colours.concat(colours.slice(0));
	    }
		for (var i = 0; i < data.length; i++) {
			data[i][propertyName] = colours[i];
		} 	    
	    return data;
	}
	
	function countValues(data) {
		var value = 0;
		for (var i = 0; i < data.length; i++) {
			value += data[i].value;
		}
		return value;
	}
	
	function createCanvas() {
		var canvas = document.createElement("canvas");
	    if (!canvas.getContext) {
	    	if (typeof G_vmlCanvasManager != "undefined") {
	    		G_vmlCanvasManager.initElement(canvas);
	    		if (!canvas.getContext) {
	    			return ;
	    		}
	    	}
    	}
    	return canvas;
	}
	
	function removeZeroDatasets(data) {
		// Remove any pie Chart datasets where the value is zero, because IE 8 displays 0 as 100%!
		for (var i = data.length -1; i >= 0;i--) {
			if (!data[i].value) {
				data.splice(i, 1);
			} 			
		}
	}
	
	function displayPieChart(elementId, data, title, itemText, itemsText, width, className) {
	    
		var canvas = createCanvas();    
	    if (!canvas) return ;
	     
		addColours(data, "color");
		removeZeroDatasets(data);
	    
	    var total = 0;
	    for (var i = 0; i < data.length; i++) {
	        total += data[i].value;
	    }
	    var subtitle = "Showing " + total +  " " +  ((total === 1) ? itemText : itemsText);
	    
	    elementId = "#" + elementId;
	    var element = $(elementId).removeClass("chart-js-template").addClass("chart-js").addClass("chart-pie");
	    if (className) element.addClass(className);

		// Use the add class method because IE 8 doesn't display the element when the class is typed as part of the JQuery selector
	    $('<h2>').addClass("chart-title").appendTo(element).text(title);	
	    $('<p class="chart-subtitle">').appendTo(element).text(subtitle);      
	
	    canvas.setAttribute('width', width);
	    canvas.setAttribute('height', width);
	    $(canvas).appendTo(element);
	    new Chart(canvas.getContext("2d")).Pie(data, pieOptions);

	    var legend = $('<ul class="chart-legend horizontal">').appendTo(element);
	    for (var i = 0; i < data.length; i++) {
	        if (data[i].value > 0) {
	          $("<li>").addClass("chart-colour-" + (i+1)).text(" " + data[i].label + " (" + data[i].value + ")").appendTo(legend);
	        }
	    }
	}
	
	function displayStackedBar(elementId, data, title, yAxisLabel, xAxisLabel, width, height){
	    
	    // IE 8 doesn't support bar charts, even with the polyfill used for pie charts
		var canvas = document.createElement("canvas");
	    if (!canvas.getContext) return ;
	    	    
	    addColours(data.datasets, "fillColor");

	    var total = 0;
	    for (var i = 0; i < data.datasets.length; i++) {
	    	data.datasets[i].total = 0;
	    	
	        for (var j = 0; j < data.datasets[i].data.length; j++){
    	        total += data.datasets[i].data[j];
    	        data.datasets[i].total += data.datasets[i].data[j];	
	        }
	    }
		yAxisLabel = yAxisLabel + " (" + total + ")";
		
	    elementId = "#" + elementId;
	    var element = $(elementId).removeClass("chart-js-template").addClass("chart-js").addClass("chart-bar");

	    $('<h2 class="chart-title">').appendTo(element).text(title);	    
	    $('<p class="chart-axis-y">').appendTo(element).text(yAxisLabel);

		canvas.setAttribute('width', width);
	    canvas.setAttribute('height', height);
	    $(canvas).appendTo(element);
		new Chart(canvas.getContext("2d")).StackedBar(data, {responsive:true});
	    
	    $('<p class="chart-axis-x">').appendTo(element).text(xAxisLabel);
	        
	    var legend = $('<ul class="chart-legend horizontal">').appendTo(element);
	    for (var i = 0; i < data.datasets.length; i++) {
	        $("<li>").addClass("chart-colour-" + (i+1)).text(" " + data.datasets[i].label + " (" + data.datasets[i].total + ")").appendTo(legend);
        }
	}
	
	var pieOptions = {
		animateRotate: false,
		animateScale: true,
		animationSteps: 50,
		animationEasing: "easeOutExpo", 
		segmentStrokeColor: "#EEE", 
		segmentStrokeWidth: 1,
		responsive: true
	};
	
	return {
		countValues: countValues, 
		displayPieChart: displayPieChart, 
		displayStackedBar: displayStackedBar
	};
})();