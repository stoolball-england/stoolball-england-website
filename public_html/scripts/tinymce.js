$(function() {
	$('textarea').tinymce({
		// Location of TinyMCE script
		script_url : '/scripts/tiny_mce/tiny_mce.js',

		// General options
		theme : "advanced",
		plugins : "autolink,lists",

		// Theme options
		theme_advanced_buttons1 : "bold,italic,bullist,numlist,link,unlink",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "", 
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Set appearance of text within editor
		content_css : "/css/tinymce.css"
			});
});
