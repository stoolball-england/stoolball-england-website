"use strict";
if (typeof(jQuery)!=='undefined') {
	$(function(){
		
		var suggestions = $(".suggestions");
		var suggestions_header = '<h2><span><span><span>Have we got your school already?</span></span></span></h2>';
		
		$(".searchable").keyup(function(e) {
			
			var name = $("#school-name").val();
			if (name.length < 2) name = '';
			var town = $("#town").val();
			if (town.length < 2) town = '';
			
			// If no search term entered, just clear the results
			if (!name && !town) {
				suggestions.html('').removeClass("panel");
				return;
			}
			
			// Search for matching schools, and display the results
			$.getJSON('/schools.json', { name: name, town: town }, function(data) {
				
				var html = '';
				
				if (data && data.length) {
					html = suggestions_header + "<ol>";
					for (var i = 0; i < data.length; i++) {
						html += '<li><a href="' + data[i].url + '/edit">' + data[i].name + '</a></li>';
					}
					html += "</ol>";
				}
				
				if (html) {
					suggestions.html(html).addClass("panel");
				}
				else {
					suggestions.html('').removeClass("panel");
				}
			});
		});
	});
}
