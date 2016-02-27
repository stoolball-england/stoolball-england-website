if (typeof(jQuery)!=='undefined') {
	$(function(){
		
		// Define the available designs
		var designs = [];
		designs.push({
			id: "maria-this-girl-can",
			preview: "./designs/maria-this-girl-can-preview.jpg",
			alt: "A female player with a bat at the wicket, laughing. Also features 'This Girl Can' branding.",
			maxLengthTitle: 18,
			maxLengthSlogan: 27,
			maxLengthName: 80,
			maxLengthDetails: 300,
			fontSizeMultiplierTitle: .44,
			fontSizeMinTitle: 40,
			fontSizeMaxTitle: 70,
			fontSizeMultiplierSlogan: .9,
			fontSizeMinSlogan: 20,
			fontSizeMaxSlogan: 37,
			fontSizeMultiplierName: 1.1,
			fontSizeMinName: null,
			fontSizeMaxName: null,
			fontSizeMultiplierDetails: 3.1,
			fontSizeMinDetails: null,
			fontSizeMaxDetails: null
		});
		designs.push({
			id: "ashurst",
			preview: "./designs/ashurst-preview.jpg",
			alt: "A young male player bowling to an older male",
			maxLengthTitle: 18,
			maxLengthSlogan: 27,
			maxLengthName: 50,
			maxLengthDetails: 300,
			fontSizeMultiplierTitle: .44,
			fontSizeMinTitle: 40,
			fontSizeMaxTitle: 70,
			fontSizeMultiplierSlogan: .9,
			fontSizeMinSlogan: 20,
			fontSizeMaxSlogan: 37,
			fontSizeMultiplierName: 1.425,
			fontSizeMinName: null,
			fontSizeMaxName: null,
			fontSizeMultiplierDetails: 2.75,
			fontSizeMinDetails: null,
			fontSizeMaxDetails: null
		});
		designs.push({
			id: "connie-this-girl-can",
			preview: "./designs/connie-this-girl-can-preview.jpg",
			alt: "A female player in blue and yellow celebrates a catch. Also features 'This Girl Can' branding.",
			maxLengthTitle: 18,
			maxLengthSlogan: 27,
			maxLengthName: 50,
			maxLengthDetails: 300,
			fontSizeMultiplierTitle: .44,
			fontSizeMinTitle: 40,
			fontSizeMaxTitle: 67,
			fontSizeMultiplierSlogan: .25,
			fontSizeMinSlogan: 30,
			fontSizeMaxSlogan: 45,
			fontSizeMultiplierName: 1.1,
			fontSizeMinName: null,
			fontSizeMaxName: null,
			fontSizeMultiplierDetails: 3.1,
			fontSizeMinDetails: null,
			fontSizeMaxDetails: null
		});
		designs.push({
			id: "georgie",
			preview: "./designs/georgie-preview.jpg",
			alt: "A female player fielding a ball with the caption 'I don't accept boundaries' and 'This Girl Can' branding.",
			maxLengthTitle: 0,
			maxLengthSlogan: 0,
			maxLengthName: 80,
			maxLengthDetails: 300,
			fontSizeMultiplierName: 1.69,
			fontSizeMinName: null,
			fontSizeMaxName: null,
			fontSizeMultiplierDetails: 3.1,
			fontSizeMinDetails: null,
			fontSizeMaxDetails: null
		});
		designs.push({
			id: "long-shots",
			preview: "./designs/long-shots-preview.jpg",
			alt: "A female batter with the caption 'Sometimes the long shots are worth it' and 'This Girl Can' branding.",
			maxLengthTitle: 0,
			maxLengthSlogan: 0,
			maxLengthName: 80,
			maxLengthDetails: 300,
			fontSizeMultiplierName: 1.69,
			fontSizeMinName: null,
			fontSizeMaxName: null,
			fontSizeMultiplierDetails: 3.1,
			fontSizeMinDetails: null,
			fontSizeMaxDetails: null
		});
		designs.push({
			id: "male-batsman",
			preview: "./designs/male-batsman-preview.jpg",
			alt: "A young male batsman",
			maxLengthTitle: 18,
			maxLengthSlogan: 27,
			maxLengthName: 80,
			maxLengthDetails: 300,
			fontSizeMultiplierTitle: .44,
			fontSizeMinTitle: 40,
			fontSizeMaxTitle: 67,
			fontSizeMultiplierSlogan: .25,
			fontSizeMinSlogan: 25,
			fontSizeMaxSlogan: 39,
			fontSizeMultiplierName: 1.69,
			fontSizeMinName: null,
			fontSizeMaxName: null,
			fontSizeMultiplierDetails: 3.1,
			fontSizeMinDetails: null,
			fontSizeMaxDetails: null
		});
		designs.push({
			id: "maria",
			preview: "./designs/maria-preview.jpg",
			alt: "A female player with a bat at the wicket, laughing",
			maxLengthTitle: 18,
			maxLengthSlogan: 27,
			maxLengthName: 80,
			maxLengthDetails: 300,
			fontSizeMultiplierTitle: .44,
			fontSizeMinTitle: 40,
			fontSizeMaxTitle: 70,
			fontSizeMultiplierSlogan: .9,
			fontSizeMinSlogan: 20,
			fontSizeMaxSlogan: 37,
			fontSizeMultiplierName: 1.69,
			fontSizeMinName: null,
			fontSizeMaxName: null,
			fontSizeMultiplierDetails: 3.1,
			fontSizeMinDetails: null,
			fontSizeMaxDetails: null
		});
		designs.push({
			id: "connie",
			preview: "./designs/connie-preview.jpg",
			alt: "A female player in blue and yellow celebrates a catch",
			maxLengthTitle: 18,
			maxLengthSlogan: 27,
			maxLengthName: 80,
			maxLengthDetails: 300,
			fontSizeMultiplierTitle: .44,
			fontSizeMinTitle: 40,
			fontSizeMaxTitle: 67,
			fontSizeMultiplierSlogan: .25,
			fontSizeMinSlogan: 30,
			fontSizeMaxSlogan: 45,
			fontSizeMultiplierName: 1.69,
			fontSizeMinName: null,
			fontSizeMaxName: null,
			fontSizeMultiplierDetails: 3.1,
			fontSizeMinDetails: null,
			fontSizeMaxDetails: null
		});
						
		// Provide a way to update the preview with a new design
		function showPreviewOfDesign(designIndex, withFade) {

			var preview = $("#poster-preview");
			preview.fadeTo(withFade ? 300 : 0, 0.1, function(){
				var design = designs[designIndex];
				preview.removeClass().addClass(design.id);
				preview.data("design", designIndex);
				
				// Update the design id to submit
				$("#design").val(design.id);
				
				// Update the image
				$("img", preview).attr("src", design.preview).attr("alt", "Poster preview: " + design.alt);
				
				// Update the maxlengths on the data entry fields
				maxLengthOrDisabled("#title", design.maxLengthTitle);
				maxLengthOrDisabled("#slogan", design.maxLengthSlogan);
				maxLengthOrDisabled("#name", design.maxLengthName);
				maxLengthOrDisabled("#details", design.maxLengthDetails);
				
				// Responsive text size
				responsiveTextSize("#preview-title", design.fontSizeMultiplierTitle, design.fontSizeMinTitle, design.fontSizeMaxTitle);
				responsiveTextSize("#preview-slogan", design.fontSizeMultiplierSlogan, design.fontSizeMinSlogan, design.fontSizeMaxSlogan);
				responsiveTextSize("#preview-name", design.fontSizeMultiplierName, design.fontSizeMinName, design.fontSizeMaxName);
				responsiveTextSize("#preview-details", design.fontSizeMultiplierDetails, design.fontSizeMinDetails, design.fontSizeMaxDetails);
			
			}).fadeTo(withFade ? 300 : 0, 1);
		}
		
		function maxLengthOrDisabled(elementSelector, maxlength) {
			if (maxlength) {
				$(elementSelector).attr("maxlength", maxlength).removeAttr("disabled");
			} else {
				$(elementSelector).attr("disabled", "disabled");
			}
		}
		
		function responsiveTextSize(elementSelector, multiplier, minimumSize, maximumSize) {
			
			if (minimumSize || maximumSize) {
				var options = {};
				if (minimumSize) {
					options.minFontSize = minimumSize;
				}
				if (maximumSize) {
					options.maxFontSize = maximumSize;
				}
				$(elementSelector).fitText(multiplier, options);
			} 
			else if (multiplier) {
				$(elementSelector).fitText(multiplier);
			}
		}

		// By default, show a preview of the first design
		$('<div id="poster-preview">' +
		      '<img width="100%" />' +
		      '<p id="preview-title"></p>' +
		      '<p id="preview-slogan"></p>' +
		      '<p id="preview-name"></p>' +
		      '<p id="preview-details"></p>' +
	       '</div>').insertBefore("form.poster");
		showPreviewOfDesign(0, false);
		
		// Wire up dynamic updating of the preview, starting with reflecting text as it's typed
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
		
		// Buttons to cycle through designs
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

			showPreviewOfDesign(currentDesignId, true);
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

			showPreviewOfDesign(currentDesignId, true);
		}));
		
		// Wire up Google Analytics event tracking
		if (typeof(_gaq) !== 'undefined') {
			$('#download').click(function() {
				_trackEvent('pdf', 'download', 'poster', 1);
			});
		}
		
		// Preload previews
		$.each(designs,function(){(new Image).src=this.preview;});
	});
}
