var stoolballCharts =  (function (){
	"use strict";
	
    var allColours = ["#7FAA84","#008044","#002b17","#503C03","#9a854b","#e7d197"];
    var similarColours = [0,1,2,3,4,5];
    var contrastingColours = [1,3];

	function addColours(data, propertyName, allowedColours) {
	    
	    // Make a copy of the colours array, and expand it if needed so that there are at least as many colours as datasets
	    var dataColours = allowedColours.slice(0);
	    while (dataColours.length < data.length) {
	    	dataColours = dataColours.concat(dataColours.slice(0));
	    }
		for (var i = 0; i < data.length; i++) {
			data[i][propertyName] = allColours[allowedColours[i]];
		} 	    
	    return data;
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
	
	function displayPieChart(elementId, data, title, itemText, itemsText, width, className, chartOptions) {
	    
		var canvas = createCanvas();
	    if (!canvas) return ;
	    canvas.setAttribute('width', width);
	    canvas.setAttribute('height', width);
	     
		addColours(data, "color", similarColours);
		removeZeroDatasets(data);
	    	    
		for (var i = 0; i < data.length; i++) {
			data[i].label += " (" + data[i].value + ")";
		}

	    var total = calculateTotal(data);
	    var subtitle = "Showing " + total +  " " +  ((total === 1) ? itemText : itemsText);

	    var element = prepareTargetElement(elementId, ["chart-pie", className]);
	    element.append(createTitle(title));	    
		element.append(createSubTitle(subtitle));      
	    element.append(canvas);
	    element.append(createLegend(data, similarColours));


		var pieDefaults = {
			animateRotate: false,
			animateScale: true,
			animationSteps: 50,
			animationEasing: "easeOutExpo", 
			segmentStrokeColor: "#EEE", 
			segmentStrokeWidth: 1,
			responsive: true
		};
	
		new Chart(canvas.getContext("2d")).Pie(data, $.extend(pieDefaults, chartOptions));
	}
		
	function displayStackedBar(elementId, data, title, xAxisLabel, yAxisLabelSingular, yAxisLabelPlural, chartOptions){
	    
	    // IE 8 doesn't support bar charts, even with the polyfill used for pie charts
		var canvas = document.createElement("canvas");
	    if (!canvas.getContext) return ;
	    	    
	    addColours(data.datasets, "fillColor", similarColours);

	    var total = calculateTotals(data.datasets);
	    addTotalsToLabels(data.datasets, yAxisLabelSingular, yAxisLabelPlural);
		yAxisLabelPlural = yAxisLabelPlural + " (" + total + ")";
		
	    var element = prepareTargetElement(elementId, ["chart-bar"]);
	    element.append(createTitle(title));    
	    element.append(createYAxisLabel(yAxisLabelPlural));
	    element.append(canvas);
	    element.append(createXAxisLabel(xAxisLabel));	        
	    element.append(createLegend(data.datasets, similarColours));

		var stackedBarDefaults = { responsive:true };
		new Chart(canvas.getContext("2d")).StackedBar(data, $.extend(stackedBarDefaults, chartOptions));	    
	}
	
	function displayBar(elementId, data, title, xAxisLabel, yAxisLabelSingular, yAxisLabelPlural, barColours, max, chartOptions){
	    
	    // IE 8 doesn't support bar charts, even with the polyfill used for pie charts
		var canvas = document.createElement("canvas");
	    if (!canvas.getContext) return ;
	    	    
	    addColours(data.datasets, "fillColor", barColours);

	    calculateTotals(data.datasets);
	    addTotalsToLabels(data.datasets, yAxisLabelSingular, yAxisLabelPlural);
		
	    var element = prepareTargetElement(elementId, ["chart-bar"]);
	    element.append(createTitle(title));	    
	    element.append(createYAxisLabel(yAxisLabelPlural));
	    element.append(canvas);
	    element.append(createXAxisLabel(xAxisLabel));	        	        
	    element.append(createLegend(data.datasets, barColours));

	    var stepWidth = (Math.floor(max/10)+1);
	   	var scaleSteps = Math.ceil(max /stepWidth);
	    
	    var barDefaults = {
			responsive:true, 
			scaleOverride: true,
    		scaleStartValue: 0,
		    scaleStepWidth: stepWidth,
			scaleSteps: scaleSteps
		};
	    
		new Chart(canvas.getContext("2d")).Bar(data, $.extend(barDefaults, chartOptions));	    
	}
	
	function displayLine(elementId, data, title, yAxisLabel, xAxisLabel, lineColours, chartOptions) {
	    
		var canvas = document.createElement("canvas");
	    if (!canvas.getContext) return ;
	    	    
	    addColours(data.datasets, "strokeColor", lineColours);
	    addColours(data.datasets, "pointColor", lineColours);

	    var element = prepareTargetElement(elementId, ["chart-line"]);
	    element.append(createTitle(title, ["line-chart-title"]));		    
	    element.append(createYAxisLabel(yAxisLabel));
	    element.append(canvas);
	    element.append(createXAxisLabel(xAxisLabel));	        
	    element.append(createLegend(data.datasets, lineColours));

		var lineDefaults = {
			responsive: true,
			datasetFill: false,
			pointDot: false
		};
		new Chart(canvas.getContext("2d")).Line(data, $.extend(lineDefaults, chartOptions));
	}
	
	function prepareTargetElement(elementId, classesToApply) {
	    elementId = "#" + elementId;
	    var element = $(elementId).removeClass("chart-js-template").addClass("chart-js");
	    element = applyClasses(element, classesToApply); 
		return element;
	}
	
	function calculateTotal(data) {
		var total = 0;
		for (var i = 0; i < data.length; i++) {
			total += data[i].value;
		}
		return total;
	}

	function calculateTotals(datasets) {
	    var total = 0;
	    for (var i = 0; i < datasets.length; i++) {
	    	datasets[i].total = 0;
	    	
	        for (var j = 0; j < datasets[i].data.length; j++){
    	        total += datasets[i].data[j];
    	        datasets[i].total += datasets[i].data[j];
    	    }
	    }
	    return total;
	}
	
	function addTotalsToLabels(datasets, totalLabelSingular, totalLabelPlural) {
	    for (var i = 0; i < datasets.length; i++) {
	    	datasets[i].label += " (" + datasets[i].total + " " + (datasets[i].total == 1 ? totalLabelSingular.toLowerCase() : totalLabelPlural.toLowerCase()) + ")";
	    }
	}
	
	function createTitle(title, classesToApply) {
		var h2 = null;
		if (title) {
	    		// Use the add class method because IE 8 doesn't display the element when the class is typed as part of the JQuery selector
			h2 = $('<h2>').text(title).addClass("chart-title");
	    }
	    h2 = applyClasses(h2, classesToApply);
    	return h2;
	}
	
	function createSubTitle(subtitle) {
		return $('<p class="chart-subtitle">').text(subtitle);
	}
	
	function createXAxisLabel(label) {
	    return $('<p class="chart-axis-x">').text(label);
	}

	function createYAxisLabel(label) {
	    return $('<p class="chart-axis-y">').text(label);
	}
	
	function createLegend(datasets, colours) {
	    var legend = $('<ul class="chart-legend horizontal">');
	    for (var i = 0; i < datasets.length; i++) {
	        $("<li>").addClass("chart-colour-" + (colours[i]+1)).text(" " + datasets[i].label).appendTo(legend);
        }
        return legend;
	}
	
	function applyClasses(element, classesToApply) {
	    if (element && classesToApply) {
		    for (var i = 0; i < classesToApply.length; i++) {
		    	if (classesToApply[i]) {
		    		element.addClass(classesToApply[i]);
		    	}
	    	}
	    }
	    return element;
	}
		
	return {
		calculateTotal: calculateTotal, 
		displayPieChart: displayPieChart, 
		displayBar: displayBar,
		displayStackedBar: displayStackedBar,
		displayLine: displayLine
	};
})();