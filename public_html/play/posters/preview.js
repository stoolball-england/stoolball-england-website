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
	});
}
