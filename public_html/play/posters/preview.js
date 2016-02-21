if (typeof(jQuery)!=='undefined') {
	$(function(){
		
		function wireUpPreview(fieldSelector, previewSelector){
			var field = $(fieldSelector);
			function updatePreview() {
				$(previewSelector).html(field.val().replace(/\n/g, '<br />'));
			}
			field.keyup(updatePreview);
			updatePreview();
		}
		
		wireUpPreview("#title", "#preview-title");
		wireUpPreview("#teaser", "#preview-teaser");
		wireUpPreview("#name", "#preview-name");
		wireUpPreview("#details", "#preview-details");
	});
}
