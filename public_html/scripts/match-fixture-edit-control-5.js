// Currently assumes one control per page
var matchFixtureEditControl = {
	groundAutoChangeEnabled : true,
	groundChanged : false,
	groundAutoChanging : false,
	awayTeamAutoChangeEnabled : true,
	awayTeamChanged : false,
	awayTeamAutoChanging : false,
	homeTeamList : null,
	awayTeamList : null,
	groundList : null,
	contextTeamId : null,
	namingPrefix : '',
	teamData : null,

	HomeTeamUpdatesAway_Change : function(e)
	{
		if (matchFixtureEditControl.awayTeamAutoChangeEnabled && !matchFixtureEditControl.awayTeamChanged
				&& matchFixtureEditControl.homeTeamList != null && matchFixtureEditControl.awayTeamList != null)
		{
			matchFixtureEditControl.awayTeamAutoChanging = true;

			// Get the selected team
			var teamId = matchFixtureEditControl.homeTeamList.options[matchFixtureEditControl.homeTeamList.selectedIndex].value;

			// If it's not the team arranging the fixture, set that team as the
			// away team
			if (teamId != matchFixtureEditControl.contextTeamId)
			{
				var len = matchFixtureEditControl.awayTeamList.options.length;
				for ( var i = 0; i < len; i++)
				{
					if (matchFixtureEditControl.awayTeamList.options[i].value == matchFixtureEditControl.contextTeamId)
					{
						matchFixtureEditControl.awayTeamList.options[i].selected = true;
					}
				}
			}
			matchFixtureEditControl.awayTeamAutoChanging = false;
		}
	},

	HomeTeamUpdatesGround_Change : function(e)
	{
		if (matchFixtureEditControl.groundAutoChangeEnabled && !matchFixtureEditControl.groundChanged && matchFixtureEditControl.homeTeamList != null
				&& matchFixtureEditControl.groundList != null)
		{
			matchFixtureEditControl.groundAutoChanging = true;

			// Get the selected team
			var teamId = matchFixtureEditControl.homeTeamList.options[matchFixtureEditControl.homeTeamList.selectedIndex].value;

			// Get data on home grounds
			if (matchFixtureEditControl.teamData == null)
			{
				matchFixtureEditControl.teamData = [];
				var teamGround = document.getElementById(matchFixtureEditControl.namingPrefix + 'TeamGround');
				if (teamGround)
				{
					teamGround = teamGround.getAttribute('value');
					if (teamGround)
					{
						teamGround = teamGround.split(';');
						var len = teamGround.length;
						for ( var i = 0; i < len; i++)
						{
							var pos = teamGround[i].indexOf(',');
							matchFixtureEditControl.teamData[teamGround[i].substring(0, pos)] = {
								Ground : (teamGround[i].length > pos) ? teamGround[i].substring(pos + 1, teamGround[i].length) : ''
							}
						}
					}
				}
			}

			// Get the home ground for that team
			if (matchFixtureEditControl.teamData[teamId] && matchFixtureEditControl.teamData[teamId].Ground)
			{
				// Select that ground
				var len = matchFixtureEditControl.groundList.options.length;
				for ( var i = 0; i < len; i++)
				{
					if (matchFixtureEditControl.groundList.options[i].value == matchFixtureEditControl.teamData[teamId].Ground)
					{
						matchFixtureEditControl.groundList.options[i].selected = true;
					}
				}
			}

			matchFixtureEditControl.groundAutoChanging = false;
		}
	},

	AwayTeam_Change : function(e)
	{
		if (!matchFixtureEditControl.awayTeamAutoChanging) matchFixtureEditControl.awayTeamChanged = true;
	},

	Ground_Change : function(e)
	{
		// Stop automatic changing of grounds if this is a manual change
	if (!matchFixtureEditControl.groundAutoChanging) matchFixtureEditControl.groundChanged = true;
},

OnInit : function()
{
	if (!document.getElementsByTagName) return;

	var divs = document.getElementsByTagName('div');
	if (divs)
	{
		for ( var i = 0; i < divs.length; i++)
		{
			// Check it's a control to hook up to
	if (divs[i].className.indexOf('MatchFixtureEdit') == -1) continue;

	// Get its naming prefix, if any, and the dropdown lists
	if (divs[i].getAttribute('id') != null) matchFixtureEditControl.namingPrefix = divs[i].getAttribute('id');

	matchFixtureEditControl.homeTeamList = document.getElementById(matchFixtureEditControl.namingPrefix + 'Home');
	if (matchFixtureEditControl.homeTeamList.tagName != 'SELECT') matchFixtureEditControl.homeTeamList = null; // Practice
	matchFixtureEditControl.awayTeamList = document.getElementById(matchFixtureEditControl.namingPrefix + 'Away');
	if (matchFixtureEditControl.awayTeamList.tagName != 'SELECT') matchFixtureEditControl.awayTeamList = null; // Practice
	matchFixtureEditControl.groundList = document.getElementById(matchFixtureEditControl.namingPrefix + 'Ground');

	// Add listener for hours
	var hourList = document.getElementById(matchFixtureEditControl.namingPrefix + 'Start_hour');
	if (hourList && Stoolball && Stoolball.DateControl)
	{
		$(hourList)
		.keydown(Stoolball.DateControl.BlankHourDisablesTime)
		.keyup(Stoolball.DateControl.BlankHourDisablesTime)
		.click(Stoolball.DateControl.BlankHourDisablesTime)
		.change(Stoolball.DateControl.BlankHourDisablesTime);
	}

	// Check whether an away team has been saved - we should leave it alone
	var savedAway = document.getElementById(matchFixtureEditControl.namingPrefix + 'SavedAway');
	if (savedAway)
	{
		matchFixtureEditControl.awayTeamAutoChangeEnabled = false;
	}
	else
	{
		// Check that match is being added for a team, otherwise skip this bit
		var contextTeamField = document.getElementById(matchFixtureEditControl.namingPrefix + 'ContextTeam');
		if (contextTeamField && matchFixtureEditControl.homeTeamList && matchFixtureEditControl.awayTeamList)
		{
			matchFixtureEditControl.contextTeamId = contextTeamField.getAttribute('value');

			// Hook up to home team
			$(matchFixtureEditControl.homeTeamList).change(matchFixtureEditControl.HomeTeamUpdatesAway_Change);

			// Hook up to away team
			$(matchFixtureEditControl.awayTeamList).change(matchFixtureEditControl.AwayTeam_Change);
		}
	}

	// Check whether a ground has been saved - we should leave it alone
	var savedGround = document.getElementById(matchFixtureEditControl.namingPrefix + 'SavedGround');
	if (savedGround)
	{
		matchFixtureEditControl.groundAutoChangeEnabled = false;
	}
	else
		if (matchFixtureEditControl.homeTeamList && matchFixtureEditControl.groundList)
		{
			// Hook up to home team
			$(matchFixtureEditControl.homeTeamList).change(matchFixtureEditControl.HomeTeamUpdatesGround_Change);

			// Hook up to ground
			$(matchFixtureEditControl.groundList).change(matchFixtureEditControl.Ground_Change);

			// Select ground based on initial team selection
			matchFixtureEditControl.HomeTeamUpdatesGround_Change();
		}
}
}

// Now focus on first dropdown list, but only if no naming prefix
// because that means this control is independent of others
if (!matchFixtureEditControl.namingPrefix)
{
var selects = document.getElementsByTagName('select');
if (selects && selects.length > 0) selects[0].focus();
}
}
}
$(document).ready(matchFixtureEditControl.OnInit);