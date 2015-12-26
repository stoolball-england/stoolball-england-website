<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');

class CurrentPage extends Page
{
	private $team_ids;

	public function OnPageInit()
	{
		# This is a JavaScript file
		if (!headers_sent()) header("Content-Type: text/javascript; charset=utf-8");

		# First, check that one or more teams have been requested
		if (!isset($_GET['team']) or !$_GET['team']) die();

		$this->team_ids = explode(",",$_GET['team']);
		foreach ($this->team_ids as $team_id)
		{
			if (!is_numeric($team_id)) die();
		}

	}

	public function OnLoadPageData()
	{
		# Read all the players from all specified teams
		require_once("stoolball/player-manager.class.php");
		$player_manager = new PlayerManager($this->GetSettings(), $this->GetDataConnection());
		$player_manager->ReadPlayersForAutocomplete($this->team_ids);
		$players = $player_manager->GetItems();
		unset($player_manager);

		if (count($players))
		{
			# Sort player names into team-by-team array
			$player_data_by_team = array();
			$team_by_player_name = array();
			foreach ($players as $player)
			{
				/* @var $player Player */

				# Remember the teams each name is in
				$escaped_name = str_replace("'", "\'",Html::Encode($player->GetName())); # escape single quotes because this will become JS string
				if (!array_key_exists($escaped_name, $team_by_player_name)) $team_by_player_name[$escaped_name] = array();
				$team_by_player_name[$escaped_name][] = $player->Team()->GetId();

				# Build up the data to display in the autocomplete dropdown
				if (!array_key_exists($player->Team()->GetId(), $player_data_by_team)) $player_data_by_team[$player->Team()->GetId()] = array();

				$prompt_start = " (";
				if ($player->GetTotalMatches() == 1) $prompt_start .= "1 match";
				else if ($player->GetTotalMatches() > 1) $prompt_start .= Html::Encode($player->GetTotalMatches() . " matches");
				$prompt_team = Html::Encode(" for " . $player->Team()->GetName());
				$prompt_end = "";
				if ($player->GetFirstPlayedDate())
				{
					$first_year = gmdate("Y", $player->GetFirstPlayedDate());
					$last_year = gmdate("Y", $player->GetLastPlayedDate());
					$prompt_end .= " " . $first_year;
					if ($first_year != $last_year)
					{
						$prompt_end .= ("&#8211;" . gmdate("y", $player->GetLastPlayedDate()));
					}
				}
				$prompt_end .= ")";

				$player_data_by_team[$player->Team()->GetId()][] = "{label:\"" . $escaped_name . "\",dates:\"" . str_replace("'", "\'", $prompt_start . $prompt_end) . "\",team:\"" . str_replace("'", "\'", $prompt_start . $prompt_team . $prompt_end) . "\"}"; # escape single quotes for JS string
			}

			# Write those names as a JS array
			?>
var stoolballAutoSuggest = (function() {

	var playersByTeam = [<?php echo implode(",",$this->team_ids);?>];
	var teamsByPlayer = [];
<?php
	foreach ($player_data_by_team as $team_id => $player_data)
	{
		echo "\tplayersByTeam[$team_id] = [" . implode(",",$player_data) . "];\n";
	}
	foreach ($team_by_player_name as $player_name => $team_ids)
	{
		echo "\tteamsByPlayer['$player_name'] = ['" . implode("','",$team_ids) . "'];\n";
	}
	?>

	function enableSuggestions()
	{
		// find out which team(s) the player could come from
		var classes = this.className.split(" ");
		var suggestions = [];
		var showTeam = false;

		for (key in classes)
		{
			if (classes[key].match(/^team[0-9]+$/))
			{
				var players = playersByTeam[classes[key].substring(4)];
				if (typeof players == 'undefined') continue;

				if (suggestions.length > 0) showTeam = true;
				suggestions = suggestions.concat(players);
			}
		}

		// hook up the autocomplete to work with just the player's name, not their supporting info
		var input = $(this);
		if (!input.hasClass("autocomplete"))
		{
			input.autocomplete({source:suggestions}).data( "autocomplete" )._renderItem = function( ul, item ) {
				return $( "<li></li>" )
					.data( "item.autocomplete", item )
					.append(showTeam ? "<a>" + item.label + " " + item.team + "</a>" : "<a>" + item.label + " " + item.dates + "</a>" )
					.appendTo( ul )};
			input.change(selectTeamForPlayer).blur(addPlayerSuggestion);
		}
		else
		{
			input.autocomplete("option","source",suggestions);
		}
	}

	function selectTeamForPlayer(e)
   	{
   		// first, get value from "this" while it refers to the textbox
		var playerName = $.trim(this.value);

   		// if the next control selects the player's team (ie: player of the match), try to match it automatically.
		$("select.playerTeam", this.parentNode).each(function()
		{
			// expecting "don't know", "home" and "away"
			if (this.options.length == 3)
			{
				if (playerName.length == 0)
				{
					this.options[0].selected = true;
					return;
				}

				// see if there's a matching player name
				var teams = teamsByPlayer[playerName];
				if (typeof teams == 'undefined') return;

				// are they in the available teams?
				var couldBeHome = false;
				var couldBeAway = false;
				for (key in teams)
				{
					if (teams[key] == this.options[1].value) couldBeHome = true;
					if (teams[key] == this.options[2].value) couldBeAway = true;
				}

				// select only if sure which one it is
				if (couldBeHome && !couldBeAway) this.options[1].selected = true;
				if (!couldBeHome && couldBeAway) this.options[2].selected = true;
			}
		});
   	}

   	function addPlayerSuggestion(e)
   	{
		// if box blank, no new player
		this.value = $.trim(this.value);
		if (this.value.length == 0) return;

		// Identify teams associated with this box
		var classes = this.className.split(" ");
		var teamIds = [];

		for (key in classes)
		{
			if (classes[key].match(/^team[0-9]+$/))
			{
				teamIds[teamIds.length] = classes[key].substring(4);
			}
		}

		// see if any of those teams do not currently contain this player
		for (var i = 0; i < teamIds.length; i++)
		{
			// get array of players for the team
			var players = playersByTeam[teamIds[i]];
			if (typeof players == 'undefined') players = playersByTeam[teamIds[i]] = [];

			var newPlayer = true;
			var len = players.length;
			for (var j = 0; j < len; j++)
			{
				if (players[j].label == this.value)
				{
					newPlayer = false;
					break;
				}
			}

			if (newPlayer)
			{
				// Add the new player
				playersByTeam[teamIds[i]].unshift({label: this.value, dates:" (this match)",team:" (this match)"});
				teamsByPlayer[players[j].value] = [teamIds[i]];

				// Remove and re-connect autocomplete
				$("input.player.team" + teamIds[i], "form").each(enableSuggestions);
			}
		}
   	}
   	
   	return { enablePlayerSuggestions: enableSuggestions };
})();
$(function() {
    
    // find anywhere a player's name should be entered
    $("input.player", "form").each(stoolballAutoSuggest.enablePlayerSuggestions);

});
			<?php
		}
		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::EDIT_MATCH, false);
?>