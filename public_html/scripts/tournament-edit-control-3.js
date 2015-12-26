var tournamentEditControl = {
	OnInit : function()
	{
		// Add listener for hours
		var hourList = document.getElementById('Start_hour');
		if (hourList && Stoolball && Stoolball.DateControl)
		{
			$(hourList)
			.keydown(Stoolball.DateControl.BlankHourDisablesTime)
			.keyup(Stoolball.DateControl.BlankHourDisablesTime)
			.click(Stoolball.DateControl.BlankHourDisablesTime)
			.change(Stoolball.DateControl.BlankHourDisablesTime);
		}
		// Set initial focus
		var matchTitle = document.getElementById('Title');
		if (matchTitle) matchTitle.select();
	}
}
$(document).ready(tournamentEditControl.OnInit);