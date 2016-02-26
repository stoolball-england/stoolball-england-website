if (typeof(jQuery)!=='undefined') {
	$(function(){
		
		function wireUpPreview(fieldSelector, previewSelector){
			var field = $(fieldSelector);
			function updatePreview() {
				$(previewSelector).html(stripHTML(field.val()).replace(/\n/g, '<br />'));
			}
			field.keyup(updatePreview);
			updatePreview();
		}
		
		function stripHTML(dirty) {
		  var container = document.createElement('div');
		  var text = document.createTextNode(dirty);
		  container.appendChild(text);
		  return container.innerHTML; 
		}
		
		wireUpPreview("#title", "#preview-title");
		wireUpPreview("#slogan", "#preview-slogan");
		wireUpPreview("#name", "#preview-name");
		wireUpPreview("#details", "#preview-details");
		
		// Responsive text size
		$("#preview-title").fitText(.44, { minFontSize: 40, maxFontSize: 67 });
		$("#preview-slogan").fitText(.25, { minFontSize: 30, maxFontSize: 45 });
		$("#preview-name").fitText(1.69);
		$("#preview-details").fitText(3.1);
		
		// Wire up Google Analytics event tracking
		if (typeof(_gaq) !== 'undefined') {
			$('#download').click(function() {
				_trackEvent('pdf', 'download', 'poster', 1);
			});
		}
		
		// Cycle through designs
		var designs = [];
		designs.push({
			id: "connie",
			preview: "./designs/connie-preview.jpg",
			alt: "A female player in blue and yellow celebrates a catch",
			maxLengthTitle: 18,
			maxLengthSlogan: 27,
			maxLengthName: 80,
			maxLengthDetails: 300
		});
		designs.push({
			id: "connie-this-girl-can",
			preview: "./designs/connie-this-girl-can-preview.jpg",
			alt: "A female player in blue and yellow celebrates a catch. Also features 'This Girl Can' branding.",
			maxLengthTitle: 18,
			maxLengthSlogan: 27,
			maxLengthName: 50,
			maxLengthDetails: 300
		});
		
		
		var nav = $("<nav>").insertBefore("#poster-preview").wrap("<div>");
		nav.append($('<a href="#" class="action-button back">Previous design</a>').click(function(e){
			e.preventDefault();
			
			// Work out which design is next, cycling backwards to the end when we reach the start
			var preview = $("#poster-preview");
			var currentDesignId = parseInt(preview.data("design"));
			currentDesignId--;
			if (currentDesignId < 0) {
				currentDesignId = designs.length -1;
			}

			showNextDesign(currentDesignId);
		}));
		
		nav.append($('<a href="#" class="action-button forward">Next design</a>').click(function(e){
			e.preventDefault();
			
			// Work out which design is next, cycling back to the start when we reach the end
			var preview = $("#poster-preview");
			var currentDesignId = parseInt(preview.data("design"));
			currentDesignId++;
			if (currentDesignId >= designs.length) {
				currentDesignId = 0;
			}

			showNextDesign(currentDesignId);
		}));
		
		function showNextDesign(designIndex) {

			var preview = $("#poster-preview");
			preview.fadeTo(300, 0.1, function(){
				var design = designs[designIndex];
				preview.removeClass().addClass(design.id);
				preview.data("design", designIndex);
				
				// Update the design id to submit
				$("#design").val(design.id);
				
				// Update the image
				$("img", preview).attr("src", design.preview).attr("alt", "Poster preview: " + design.alt);
				
				// Update the maxlengths on the data entry fields
				$("#title").attr("maxlength", design.maxLengthTitle);
				$("#slogan").attr("maxlength", design.maxLengthSlogan);
				$("#name").attr("maxlength", design.maxLengthName);
				$("#details").attr("maxlength", design.maxLengthDetails);				
				
			
			}).fadeTo(300, 1);
		}
	});
}
