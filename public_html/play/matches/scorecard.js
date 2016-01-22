$(function()
{
	function enableHowOut() {
		
		var howOut = $(this.parentNode.nextSibling.firstChild);
		if (this.value) {
			howOut.removeAttr("disabled");
		}  else {
			howOut.attr("disabled","disabled");
		}
	}

	function howOutEnableDetails()
	{
		var enableOutBy = true;
		var enableBowler = true;
		var enableRuns = true;
		switch (this.selectedIndex)
		{
		case 0: // did not bat
		case 8: // timed out
			enableOutBy = false;
			enableBowler = false;
			enableRuns = false;
			break;
		case 1: // not out
		case 9: // retired
		case 10: // retired hurt
		case 11: // unknown dismissal
			enableOutBy = false;
			enableBowler = false;
			break;
		case 3: // bowled
		case 4: // c&b
		case 6: // bbw
		case 7: // hit ball twice
			enableOutBy = false;
			break;
		case 5: // run-out
			enableBowler = false;
			break;
		}

		var outBy = $(this.parentNode.nextSibling.firstChild);
		if (enableOutBy)
		{
			outBy.removeAttr("disabled");
		}
		else
		{
			outBy.attr("disabled", "disabled");
		}

		var bowler = $(this.parentNode.nextSibling.nextSibling.firstChild);
		if (enableBowler)
		{
			bowler.removeAttr("disabled");
		}
		else
		{
			bowler.attr("disabled", "disabled");
		}

		var runs = $(this.parentNode.nextSibling.nextSibling.nextSibling.firstChild);
		var balls = $(this.parentNode.nextSibling.nextSibling.nextSibling.nextSibling.firstChild);
		if (enableRuns)
		{
			runs.removeAttr("disabled");
			balls.removeAttr("disabled");
		}
		else
		{
			runs.attr("disabled", "disabled");
			balls.attr("disabled", "disabled");
		}
	}

	// Calculate batting total
	var batTotal = $("#batTotal");
	if (batTotal.length > 0 && batTotal[0].value.length == 0)
	{
		batTotal[0].calculateRuns = true;
		batTotal.blur(function()
		{
			if ($.trim(this.value).length > 0) this.calculateRuns = false;
		});
		calculateRuns({
			target : batTotal[0]
		});
		$("input.runs").blur(calculateRuns);
	}

	function calculateRuns(e)
	{
		// Determine whether we want to calculate runs automatically
		if (typeof (e.target.calculateRuns) != 'undefined' || $(e.target).hasClass("runs"))
		{
			var batTotal = document.getElementById("batTotal");
			if (!batTotal.calculateRuns) return;

			// Add up the runs in all the run boxes, including extras
			var calculatedTotal = 0;
			$(".batting input.runs:not(:disabled)").each(function()
			{
				var runs = parseInt(this.value);
				if (!isNaN(runs)) calculatedTotal += runs;
			});

			// Update the total, with an animation of the calculation
			var currentTotal = parseInt(batTotal.value);
			if (isNaN(currentTotal)) currentTotal = 0;
			if (calculatedTotal == 0)
			{
				batTotal.value = '';
			}
			else
				if (calculatedTotal > currentTotal)
				{
					var timeOut = 0;
					for ( var i = currentTotal + 1; i <= calculatedTotal; i++)
					{
						currentTotal++;
						setTimeout("document.getElementById('batTotal').value = " + currentTotal, timeOut);
						timeOut += 15;
					}
				}
				else
					if (calculatedTotal < currentTotal)
					{
						var timeOut = 0;
						for ( var i = currentTotal - 1; i >= calculatedTotal; i--)
						{
							currentTotal--;
							setTimeout("document.getElementById('batTotal').value = " + currentTotal, timeOut);
							timeOut += 15;
						}
					}
		}
	}

	// Calculate wickets total
	var batWickets = $("#batWickets");
	if (batWickets.length > 0 && batWickets[0].selectedIndex == 0)
	{
		batWickets[0].calculateWickets = true;
		batWickets.blur(function()
		{
			if (this.selectedIndex > 0) this.calculateWickets = false;
		});
		calculateWickets({
			target : batWickets[0]
		});
		$("select.howOut").blur(calculateWickets);
	}

	function calculateWickets(e)
	{
		// Determine whether we want to calculate wickets automatically
		if (typeof (e.target.calculateWickets) != 'undefined' || $(e.target).hasClass("howOut"))
		{
			var batWickets = document.getElementById("batWickets");
			if (!batWickets.calculateWickets) return;

			// Set wickets taken, but if 0 set to unknown to avoid risk of
			// stating 0 when that's not known
			var wickets = $("select.howOut :selected[value=9],select.howOut :selected[value=10],select.howOut :selected[value=4],select.howOut :selected[value=5],select.howOut :selected[value=6],select.howOut :selected[value=7],select.howOut :selected[value=8]").length;
			if (wickets > 0)
			{
				// If wickets == number of batsmen-1, that's all out (in outdoor
				// stoolball)
				var allOut = batWickets.parentNode.parentNode.parentNode.childNodes.length - 7;
				if (wickets == allOut)
				{
					$(batWickets).val(-1);
				}
				else
				{
					$(batWickets).val(wickets);
				}
			}
			else
			{
				batWickets.selectedIndex = 0;
			}
		}
	}

	// Suggest defaults for bowling stats
	function suggestBowlingDefaults(e) {

		var thisField = $(e.target);
		
		// Is this field empty?
		if ($.trim(e.target.value).length > 0)
		{
			thisField.select();
			return ;
		} 

		// Is there a player?
		var tableRow = e.target.parentNode.parentNode;
		var playerField = tableRow.firstChild.firstChild;
		if ($.trim(playerField.value).length == 0) return;
		
		// If this over was bowled, previous overs must have been, so set balls bowled to 8 if missing
		var previous = tableRow.previousSibling, previousPlayer, previousBalls;
		while (previous) {
			previousPlayer = previous.firstChild.firstChild;
			if ($.trim(previousPlayer.value).length > 0) {
				previousBalls = previous.childNodes[1].firstChild;
				if ($.trim(previousBalls.value).length == 0) {
					previousBalls.value = 8;
				}
			}
			previous = previous.previousSibling;
		}

		// Apply defaults to current field and previous fields (previous fields useful for touch screens)
		var ballsField = tableRow.childNodes[1].firstChild;
		var noballsField = tableRow.childNodes[2].firstChild;
		var widesField = tableRow.childNodes[3].firstChild;
		
		// Balls bowled defaults to 8, extras default to 0
		if (thisField.hasClass("balls"))
		{
			e.target.value = 8;
		}
		else if (thisField.hasClass("no-balls"))
		{
			if ($.trim(ballsField.value).length == 0)
			{
				ballsField.value = 8;
			} 
			e.target.value = 0;
		}
		else if (thisField.hasClass("wides"))
		{
			if ($.trim(ballsField.value).length == 0)
			{
				ballsField.value = 8;
			}
			if ($.trim(noballsField.value).length == 0)
			{
				noballsField.value = 0;
			} 
			e.target.value = 0;
		}
		else if (thisField.hasClass("runs"))
		{
			if ($.trim(ballsField.value).length == 0)
			{
				ballsField.value = 8;
			}
			if ($.trim(noballsField.value).length == 0)
			{
				noballsField.value = 0;
			}
			if ($.trim(widesField.value).length == 0)
			{
				widesField.value = 0;
			} 
		}
		thisField.select();

	};
	
	function replaceBowlingDefaults(e) {
		
		// Best fix for bug which means Mobile Safari doesn't select text.
		// This replaces default value when a number is typed, even if it's not selected.
		if (e.keyCode >= 49 && e.keyCode <= 57)
		{
			if (e.target.value == "0") e.target.value = "" ;
		}
	};
	
	// More often than not the same bowler bowled two overs ago, so copy the name
	function suggestDefaultBowler()
	{
		// Is this field empty?
		if ($.trim(this.value).length > 0) return;

		// Get player name from two rows above
		var tableRow = this.parentNode.parentNode;
		tableRow = tableRow.previousSibling;
		if (!tableRow) return ;
		tableRow = tableRow.previousSibling;
		if (!tableRow) return ;
		var twoUp = tableRow.firstChild.firstChild.value;

		// If four rows above is different, the bowling is probably shared around
		var fourUp;
		tableRow = tableRow.previousSibling;
		if (tableRow) {
			tableRow = tableRow.previousSibling;
			if (tableRow) {
				fourUp = tableRow.firstChild.firstChild.value;
			}
		}
		
		if (!fourUp || fourUp === twoUp) { 
			this.value = twoUp;
			this.select();
		}
	};
	
	function enableBowling() {
		
		var fields = $("input[type=number]", this.parentNode.parentNode);
		if (this.value) {
			fields.removeAttr("disabled");
		}  else {
			fields.attr("disabled", "disabled");
		}
	}

	// Auto enable/disable scorecard fields
	// .change event fires when the field is clicked in Chrome
	// .each is used to setup the fields on page load

	$(".batsman .player").keyup(enableHowOut).click(enableHowOut).change(enableHowOut).each(enableHowOut);
	$("select.howOut").keyup(howOutEnableDetails).click(howOutEnableDetails).change(howOutEnableDetails).each(howOutEnableDetails);
	
	$(".bowler .player").keyup(enableBowling).click(enableBowling).change(enableBowling).each(enableBowling);
	$("input.numeric", ".scorecardPage table.bowling").focus(suggestBowlingDefaults).keydown(replaceBowlingDefaults);
	$("input.player", ".scorecardPage table.bowling").focus(suggestDefaultBowler);

	// Select first field
	$("select, input[type='text']:not(:disabled)")[0].focus();
	
	
	// Add batsman button
	$("tr.extras:first th").attr("colspan", "2")
		.before('<td colspan="2" class="add-one-container"><a href="#" class="add-one">Add a batsman</a></td>')
		.siblings().children(".add-one").click(function(e) {
			e.preventDefault();
			var lastRow = $(this.parentNode.parentNode.previousSibling);
			var newRow = lastRow.clone().insertAfter(lastRow);
			var batsman = parseInt($('input:first', newRow).attr("id").substring(7));
			var replaceBatsman = function(index, value) { return value.substring(0, value.indexOf(batsman)) + (batsman+1);};
			$('input', newRow).attr("id", replaceBatsman).attr("name", replaceBatsman).attr("value", "");
			if (typeof stoolballAutoSuggest != "undefined") $("input.player", newRow).each(stoolballAutoSuggest.enablePlayerSuggestions);
			$("select.howOut", newRow).keyup(howOutEnableDetails).click(howOutEnableDetails).change(howOutEnableDetails).each(howOutEnableDetails);
			$('input:first', newRow)[0].focus();
	});
	
	// Add over button
	$('<a href="#" class="add-one">Add an over</a>').insertAfter(".bowling").click(function(e) {
		e.preventDefault();
		var lastRow = $('.bowling tbody tr:last-child');
		var newRow = lastRow.clone().insertAfter(lastRow);
		var over = parseInt($('input:first', newRow).attr("id").substring(10));
		var replaceOver = function(index, value) { return value.substring(0, value.indexOf(over)) + (over +1);};
		$('input', newRow).attr("id", replaceOver).attr("name", replaceOver).attr("value", "");
		$("input.numeric", newRow).focus(suggestBowlingDefaults).keydown(replaceBowlingDefaults);
		if (typeof stoolballAutoSuggest != "undefined") $("input.player", newRow).each(stoolballAutoSuggest.enablePlayerSuggestions);
		$('input:first', newRow)[0].focus();
	});
});