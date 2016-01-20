var matchEdit =
{
	teamData : null,
	teamIds : null,
	seasonIds : null,

	/*
	* Hook up events and perform initial check on page load
	*/
	Load : function()
	{
		if (document.getElementById && document.getElementsByTagName)
		{
			// Filter teams to just those from the relevant season, unless a tournament match or friendly
			// in which case just leave them alone
			var season = $('#SeasonSeason');
			var matchType = $('#MatchFixtureEditControlMatchType').val();
			if (season.length > 0 && matchType != 2 && matchType != 4)
			{
				season.change(matchEdit.PopulateFromSeason_Change);
				matchEdit.PopulateFromSeason_Change();
			}

			var defaultTitle = document.getElementById('defaultTitle');
			var startDay = document.getElementById('MatchFixtureEditControlStart_day');
			var regenerateUrl = document.getElementById('RegenerateUrl');

			if (defaultTitle) defaultTitle.onclick = matchEdit.DefaultTitle_Click;
			if (regenerateUrl) $(regenerateUrl).click(matchEdit.RegenerateUrl_Click);

			matchEdit.DefaultTitle_Click();
			if (regenerateUrl)
			{
				regenerateUrl.click();
				regenerateUrl.click(); // It's ticked by default, click it twice to leave it ticked but also disable the textbox
			}
		}
	},

	RegenerateUrl_Click : function(e)
	{
		var shortUrl = document.getElementById('ShortUrl');
		if (!shortUrl) return;
		if ($('#RegenerateUrl').is(':checked'))
		{
			// Use readonly because we still want the data submitted
			shortUrl.setAttribute('readonly', 'readonly');
		}
		else
		{
			shortUrl.removeAttribute('readonly');
		}
	},

	/*
	* When a season is selected, limit the available teams
	*/
	PopulateFromSeason_Change : function()
	{
		var season = document.getElementById('SeasonSeason');
		var homeTeam = document.getElementById('MatchFixtureEditControlHome');
		var awayTeam = document.getElementById('MatchFixtureEditControlAway');

		if (season && homeTeam && awayTeam)
		{
			matchEdit.CacheData();

			// remember current selections
			var homeSelected = homeTeam.options[homeTeam.selectedIndex].getAttribute('value');
			var awaySelected = awayTeam.options[awayTeam.selectedIndex].getAttribute('value');

			// clear team lists
			while (homeTeam.firstChild != null) homeTeam.removeChild(homeTeam.firstChild);
			while (awayTeam.firstChild != null) awayTeam.removeChild(awayTeam.firstChild);

			// remember selected ground, clear ground list, remember options because only re-arranging
			var ground = document.getElementById('MatchFixtureEditControlGround');
			if (ground.tagName == 'SELECT')
			{
				var groundSelected = ground.options[ground.selectedIndex].getAttribute('value');
				var groundGroups = ground.getElementsByTagName('optgroup');
				var groundOptions = new Array();
				var homeGrounds = new Array();
				var len = groundGroups.length;
				for (var i = 0; i < len; i++)
				{
					while (groundGroups[i].firstChild != null)
					{
						var groundId = groundGroups[i].firstChild.getAttribute('value');
						groundOptions[groundId] = groundGroups[i].firstChild.firstChild.nodeValue + '###' + groundId;
						groundGroups[i].removeChild(groundGroups[i].firstChild);
					}
				}
			}

			// cycle through teams
			var currentSeason = season.options[season.selectedIndex].getAttribute('value');
			var pos = currentSeason.indexOf('###');
			if (pos > -1) currentSeason = currentSeason.substring(0,pos);
			len = matchEdit.teamIds.length;
			var lenSeasons = matchEdit.seasonIds.length;
			for (var i = 0; i < len; i++)
			{
				// get the team id and track whether to add this team
				var teamId = matchEdit.teamIds[i];
				var addTeam = false;

				// if no season show all teams
				if (season.selectedIndex == 0 && lenSeasons == 0) addTeam = true;

				// if season selected in dropdown, show its teams
				if (!addTeam && matchEdit.teamData[teamId] && matchEdit.in_array(currentSeason, matchEdit.teamData[teamId].Seasons, true)) addTeam = true;

				// if season already selected in SeasonIdEditor, show its teams too
				for (var j = 0; j < lenSeasons; j++)
				{
					if (!addTeam && matchEdit.teamData[teamId] && matchEdit.in_array(matchEdit.seasonIds[j], matchEdit.teamData[teamId].Seasons, true)) addTeam = true;
				}

				// if decided to show team, add it to the lists
				if (addTeam)
				{
					matchEdit.AddTeamToList(teamId, homeTeam, homeSelected);
					matchEdit.AddTeamToList(teamId, awayTeam, awaySelected);

					// look for ground id if it hasn't already been added to the home grounds list (might be home to multiple teams)
					if (ground.tagName == 'SELECT')
					{
						if (matchEdit.teamData[teamId] && matchEdit.teamData[teamId].Ground && !homeGrounds[groundId])
						{
							var groundId = matchEdit.teamData[teamId].Ground;
							homeGrounds[groundId] = groundOptions[groundId];
							groundOptions[groundId] = null;
						}
					}
				}
			}

			// re-add grounds
			if (ground.tagName == 'SELECT')
			{
				homeGrounds.sort();
				groundOptions.sort();
				for (var i = 0; i < homeGrounds.length; i++) matchEdit.AddGroundToList(homeGrounds[i], groundGroups[0], groundSelected);
				for (var i = 0; i < groundOptions.length; i++) matchEdit.AddGroundToList(groundOptions[i], groundGroups[1], groundSelected);
			}
		}
	},

	/*
	* Helper to add a stoolball team to a dropdown list
	*/
	AddTeamToList : function(teamId, listElement, teamToSelect)
	{
		if (teamId && listElement)
		{
			var opt = document.createElement('option');
			opt.setAttribute('value', teamId);
			if (teamId == teamToSelect) opt.setAttribute('selected', 'selected');
			opt.appendChild(document.createTextNode(matchEdit.teamData[teamId].Name));
			listElement.appendChild(opt);
		}
	},

	/*
	* Helper to add a stoolball ground to a dropdown list
	*/
	AddGroundToList : function(groundData, listElement, groundToSelect)
	{
		if (groundData && listElement)
		{
			var dividerPos = groundData.indexOf('###');
			var groundId = groundData.substr(dividerPos + 3);
			var groundName = groundData.substr(0,dividerPos);

			var opt = document.createElement('option');
			opt.setAttribute('value', groundId);
			if (groundId == groundToSelect) opt.setAttribute('selected', 'selected');
			opt.appendChild(document.createTextNode(groundName));
			listElement.appendChild(opt);
		}
	},

	/**
	* Read data from page - format documented in MatchEditControl
	*/
	CacheData : function()
	{
		if (matchEdit.teamData == null)
		{
			matchEdit.teamData = [];
			matchEdit.teamIds = [];

			// Get all teams from the home team list
			var homeTeams = document.getElementById('MatchFixtureEditControlHome');
			if (!homeTeams) return;

			homeTeams = homeTeams.getElementsByTagName('option');
			if (!homeTeams) return;

			var len = homeTeams.length;
			for (var i = 0; i < len; i++)
			{
				matchEdit.teamData[homeTeams[i].getAttribute('value')] = { Name : homeTeams[i].firstChild.nodeValue, Seasons : [], Ground : '' };
				matchEdit.teamIds.push(homeTeams[i].getAttribute('value'));
			}

			// Data on seasons teams play in
			var teamSeason = document.getElementById('TeamSeason');
			if (teamSeason)
			{
				teamSeason = teamSeason.getAttribute('value');
				if (teamSeason)
				{
					// split into teams
					teamSeason = teamSeason.split(';');
					len = teamSeason.length;
					for (var i = 0; i < len; i++)
					{
						var pos = teamSeason[i].indexOf(',');
						if (pos > -1) matchEdit.teamData[teamSeason[i].substring(0,pos)].Seasons = teamSeason[i].substring(pos+1,teamSeason[i].length).split(',');
					}
				}
			}

			// Get data on home grounds
			var teamGround = document.getElementById('MatchFixtureEditControlTeamGround');
			if (teamGround)
			{
				teamGround = teamGround.getAttribute('value');
				if (teamGround)
				{
					teamGround = teamGround.split(';');
					len = teamGround.length;
					for (var i = 0; i < len; i++)
					{
						var pos = teamGround[i].indexOf(',');
						if (teamGround[i].length > pos)
						{
							var teamId = teamGround[i].substring(0,pos);
							matchEdit.teamData[teamId].Ground = teamGround[i].substring(pos+1, teamGround[i].length);
						};
					}
				}
			}
		}

		// Read the seasons already selected in the SeasonIdEditor
		if (matchEdit.seasonIds == null)
		{
			matchEdit.seasonIds = [];
			var seasonEditor = document.getElementById('Season');
			if (seasonEditor)
			{
				var inputs = seasonEditor.getElementsByTagName('input');
				var reg = new RegExp(/SeasonRelatedId[0-9]+/);
				var numInputs = inputs.length;
				for (var i = 0; i < numInputs; i++)
				{
					if (reg.exec(inputs[i].getAttribute('id')))
					{
						matchEdit.seasonIds[matchEdit.seasonIds.length] = inputs[i].getAttribute('value');
					}
				}
			}
		}
	},

	/*
	* Enables or disables title field based on checkbox
	*/
	DefaultTitle_Click : function()
	{
		var defaultTitle = document.getElementById('defaultTitle');
		var title = document.getElementById('title');

		if (!defaultTitle || !title) return;

		if (defaultTitle.checked)
		{
			title.setAttribute('disabled', 'disabled');
		}
		else
		{
			title.removeAttribute('disabled');
		}
	},

	in_array : function(needle, haystack, strict) {
		// http://kevin.vanzonneveld.net
		// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// *     example 1: in_array('van', ['Kevin', 'van', 'Zonneveld']);
		// *     returns 1: true

		var found = false, key, strict = !!strict;

		for (key in haystack) {
			if ((strict && haystack[key] === needle) || (!strict && haystack[key] == needle)) {
				found = true;
				break;
			}
		}

		return found;
	}
}
$(matchEdit.Load);
