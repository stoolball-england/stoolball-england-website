$(function()
{
	// Show/hide player of match fields
	$("#PlayerOptions input").click(showPlayerFields);
	showPlayerFields(true);

	function showPlayerFields(instant)
	{
		var selectedOption = $("input:checked", $("#PlayerOptions")).attr("value");
		switch (selectedOption) {

		case "1":
			if (instant == true)
			{
				$("div.multiPlayer").hide();
			}
			else
			{
				if ($("div.multiPlayer").is(":visible"))
				{
					$("div.multiPlayer").stop(true, true).slideUp("fast");
					setTimeout(function(){ $("#OnePlayer").stop(true, true).slideDown();}, 300);
				}
				else
				{
					$("#OnePlayer").stop(true, true).slideDown("fast");
				}
			}
			break;
		case "2":
			if (instant == true)
			{
				$("#OnePlayer").hide();
			}
			else
			{
				if ($("#OnePlayer").is(":visible"))
				{
					$("#OnePlayer").stop(true, true).slideUp("fast");
					setTimeout(function(){ $("div.multiPlayer").stop(true, true).slideDown("fast");}, 300);
				}
				else
				{
					$("div.multiPlayer").stop(true, true).slideDown("fast");
				}
			}
			break;
		default:
			if (instant == true)
			{
				$("#OnePlayer, div.multiPlayer").hide();
			}
			else
			{
				$("#OnePlayer, div.multiPlayer").stop(true, true).slideUp("fast");
				
				// Do the same again because if you're too quick with keyboard navigation,
				// this option can be selected before the slide down for another option has started, 
				// and that other option is still revealed. This compensates for it.
				setTimeout(function(){ $("#OnePlayer, div.multiPlayer").stop(true, true).hide();}, 200);
			}
			break;
		}
	}
	
	// Select first field
	$("select, input[type='text']:not(:disabled)")[0].focus();
});
