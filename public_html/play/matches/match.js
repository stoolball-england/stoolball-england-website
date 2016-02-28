if (typeof(jQuery) !== 'undefined') {
	"use strict";
	
	$(function(){
		$(".bowling-scorecard.overs").each(function(){
			var table = $(this);
			table.hide();
			$('<a href="#">Show over-by-over bowling</a>').click(function(e){
				e.preventDefault();
				$(this).parent().hide();
				table.show();
			}).wrap('<p>').parent().insertAfter(this);
		});
	});
}
